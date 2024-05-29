<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */
namespace Crmeb\Yihaotong;

use Crmeb\Yihaotong\Util\Cache;
use Crmeb\Yihaotong\Util\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tuoluojiang\BaiduBce\Exception\BaiduBceException;

/**
 *  获取鉴权参数.
 */
class AccessToken
{
    protected Cache|CacheInterface $cache;

    private string $tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';

    private bool   $verify = false;

    private Client $client;

    private string $cache_prefix = 'baidubce_';

    /**
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct(protected string $apiKey, protected string $secretKey, CacheInterface $cache = null)
    {
        $this->client = new Client(['verify' => $this->verify, 'timeout' => 10]);
        $this->cache  = $cache ?: new Cache($this->config['redis'] ?? []);
    }

    /**
     * 获取缓存.
     * @return Cache|CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * 设置缓存.
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * 设置.
     * @return $this
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function setConfig(string $accessKey, string $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * 从缓存中获取token.
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @return null|int|mixed|string
     */
    public function accessToken()
    {
        $key = md5($this->cache_prefix . $this->apiKey . $this->secretKey);
        if ($this->cache->has($key)) {
            $accessToken = $this->cache->get($key);
        } else {
            $token       = $this->getToken();
            $accessToken = $token['access_token'];
            $this->cache->set($key, $token['access_token'], $token['expires_in'] ?? $this->config['expires'] ?? 3600);
        }
        return $accessToken;
    }

    /**
     * 删除token.
     * @throws InvalidArgumentException
     * @return bool|int
     */
    public function removeToken()
    {
        $key = md5($this->cache_prefix . $this->apiKey . $this->secretKey);
        return $this->cache->delete($key);
    }

    /**
     * 请求
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function request(string $uri, string $method, array $options = [], array $header = [])
    {
        $header = [
            'Content-Type' => 'application/json',
        ];

        if (! empty($options['phone']) && ! Str::checkPhone($options['phone'])) {
            throw new BaiduBceException('手机号格式错误');
        }

        $response = $this->client->request($method, $this->replaceUrl($this->baseUrl($uri)), [
            'headers' => $header,
            'json'    => $options,
        ]);

        if ($response->getStatusCode() != 200) {
            throw new BaiduBceException('请求失败，HTTP状态码为：' . $response->getStatusCode());
        }

        $body = $response->getBody();

        return json_decode($body->getContents(), true);
    }

    /**
     * 获取token.
     * @throws GuzzleException
     * @return mixed
     */
    protected function getToken()
    {
        $params = [
            'api_key'    => $this->apiKey,
            'secret_key' => $this->secretKey,
        ];
        if (! $this->accessKey || ! $this->secretKey) {
            throw new BaiduBceException('缺少获取token参数!');
        }
        $response = $this->client->post($this->tokenUrl, ['json' => $params, 'headers' => [
            'Accept' => 'application/json',
        ]]);
        $response = json_decode($response->getBody()->getContents(), true);
        if (! $response) {
            throw new BaiduBceException('获取token失败');
        }
        if ($response['status'] === 200) {
            return $response['data'];
        }
        throw new BaiduBceException($response['msg']);
    }
}
