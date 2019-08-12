<?php

namespace App\Resources;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Auth\GuardHelpers;

class OauthResources
{
    use GuardHelpers;

    const ACCESS_TOKEN_EXPIRES_IN = 3600;
    const REFRESH_TOKEN_EXPIRES_IN = 7200;
    const MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS = 10;

    /**
     * 创建access_token
     *
     * @param $userIdentifier
     * @return array
     * @throws \Exception
     */
    public static function issueAccessToken($userIdentifier)
    {
        $accessTokenId = self::generateUniqueIdentifier('access_tokens');
        $expireTime = time() + self::ACCESS_TOKEN_EXPIRES_IN;

        $accessTokenKey = CachekeyResources::getKey('access_tokens', $accessTokenId);
        $userAccountKey = CachekeyResources::getKey('user_tokens', $userIdentifier);

        Redis::multi()
            ->hMSet($accessTokenKey, [
                'user_id' => $userIdentifier,
                'expire_time' => $expireTime,
            ])
            ->hSet($userAccountKey, $accessTokenId, serialize(array(
                'name' => 'access_tokens',
                'expire_time' => $expireTime,
            )))
            ->expireAt($accessTokenKey, $expireTime)
            ->exec();

        return [
            'access_token_id' => $accessTokenId,
            'user_id' => $userIdentifier,
            'expire_time' => $expireTime,
        ];
    }

    /**
     * 创建refresh_token
     *
     * @param $accessToken
     * @return array
     * @throws \Exception
     */
    public static function issueRefreshToken($accessToken)
    {
        $refreshTokenData = [
            'refresh_token_id' => self::generateUniqueIdentifier('refresh_tokens'),
            'access_token_id' => $accessToken['access_token_id'],
            'user_id' => $accessToken['user_id'],
            'expire_time' => time() + self::REFRESH_TOKEN_EXPIRES_IN,
        ];
        $refreshTokenKey = CachekeyResources::getKey('refresh_tokens', $refreshTokenData['refresh_token_id']);
        $userAccountKey = CachekeyResources::getKey('user_tokens', $refreshTokenData['user_id']);

        $result = Redis::multi()
            ->hMSet($refreshTokenKey, $refreshTokenData)
            ->hSet($userAccountKey, $refreshTokenData['refresh_token_id'], serialize(array(
                'name' => 'refresh_tokens',
                'expire_time' => $refreshTokenData['expire_time'],
            )))
            ->expireAt($userAccountKey, $refreshTokenData['expire_time'])
            ->expireAt($refreshTokenKey, $refreshTokenData['expire_time'])
            ->exec();
        if (!$result[0] || !$result[1] || !$result[2] || !$result[3]) {
            throw new \Exception(__('message.token_create_fail'));
        }

        return $refreshTokenData;
    }

    /**
     * 创建token
     *
     * @param $uid
     * @return array
     * @throws \Exception
     */
    public static function buildToken($uid)
    {
        $accessToken = self::issueAccessToken($uid);
        $refreshToken = self::issueRefreshToken($accessToken);

        return [
            'expires_at' => $accessToken['expire_time'],
            'access_token' => encrypt($accessToken),
            'refresh_token' => encrypt($refreshToken),
        ];
    }

    /**
     * 根据refresh_token获取access_token
     *
     * @param $refreshToken
     * @return array
     * @throws \Exception
     */
    public static function getAccessTokenByRefresh($refreshToken)
    {
        try {
            $refreshTokenData = decrypt($refreshToken);
        } catch (DecryptException $e) {
            throw new \Exception(__('message.token_decrypt_error'));
        }
        // 判断refresh_token是否过期
        $refreshTokenId = CachekeyResources::getKey('refresh_tokens', $refreshTokenData['refresh_token_id']);
        if (!Redis::exists($refreshTokenId)) {
            throw new \Exception(__('message.refresh_token_error'));
        }
        // 设置token失效
        self::revokeAccessToken($refreshTokenData['access_token_id']);
        self::revokeRefreshToken($refreshTokenData['refresh_token_id']);

        return self::buildToken($refreshTokenData['user_id']);
    }

    /**
     * 删除access_token
     *
     * @param $tokenId
     * @return mixed
     */
    public static function revokeAccessToken($tokenId)
    {
        return Redis::delete(CachekeyResources::getKey('access_tokens', $tokenId));
    }

    /**
     * 删除refresh_token
     *
     * @param $tokenId
     * @return mixed
     */
    public static function revokeRefreshToken($tokenId)
    {
        return Redis::delete(CachekeyResources::getKey('refresh_tokens', $tokenId));
    }

    /**
     * Generate a new unique identifier
     *
     * @param string $type
     * @param int $length
     * @return string
     * @throws \Exception
     */
    protected static function generateUniqueIdentifier($type = 'access_tokens', $length = 40)
    {
        $maxGenerationAttempts = self::MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS;
        while ($maxGenerationAttempts-- > 0) {
            $tokenId = bin2hex(random_bytes($length));
            if (!Redis::exists(CachekeyResources::getKey($type, $tokenId))) {
                return $tokenId;
            }
            if ($maxGenerationAttempts === 0) {
                throw new \Exception(__('message.token_create_fail'));
            }
        }
    }

    /**
     * 验证用户
     *
     * @param $accessToken
     * @return bool
     */
    public static function user($accessToken)
    {
        try {
            $accessToken = decrypt($accessToken);
        } catch (DecryptException $e) {
            return false;
        }
        $accessTokenId = CachekeyResources::getKey('access_tokens', $accessToken['access_token_id']);
        $accessTokenInfo = Redis::hgetAll($accessTokenId);
        if (empty($accessTokenInfo)) {
            return false;
        }
        // 查询用户是否存在
        $user = UserResources::findById($accessTokenInfo['user_id']);
        if (empty($user)) {
            return false;
        }

        return $user;
    }

    /**
     * 清除token
     *
     * @param $uid
     */
    public static function clearToken($uid)
    {
        $userTokensKey = CachekeyResources::getKey('user_tokens', intval($uid));
        $tokens = Redis::hGetAll($userTokensKey);

        foreach (array_chunk($tokens, 100, true) as $chunkTokens) {
            $delToken = [];
            foreach ($chunkTokens as $token => $tokenInfo) {
                $tokenInfo = unserialize($tokenInfo);
                if ($tokenInfo['expire_time'] < time()) {
                    continue;
                }
                $delToken[] = CachekeyResources::getKey($tokenInfo['name'], $token);
            }
            Redis::delete($delToken);
        }

        Redis::delete($userTokensKey);
    }
}
