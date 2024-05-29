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
namespace Tuoluojiang\Baidubce\Base;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\SimpleCache\CacheInterface;
use Tuoluojiang\Baidubce\Base\Util\HttpUtils;
use Tuoluojiang\Baidubce\Exception\BaiduBceException;
use Tuoluojiang\Baidubce\Util\BceV1Signer;
use Tuoluojiang\Baidubce\Util\Cache;

class Chat
{
    //ERNIE-4.0-8K
    protected const ERNIE_4 = '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro';

    private const TYPE_TOKEN = false;

    private const TYPE_ASK = true;

    protected string $chatUrl = 'https://aip.baidubce.com';

    protected array  $commonHeader = [
        'Content-Type' => 'application/json',
    ];

    protected array $configs;

    private bool    $verify = false;

    private CacheInterface   $cache;

    private Client $client;

    private string $tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';

    private string $cache_prefix = 'baidubce_';

    private string $apiKey;

    private string $secretKey;

    public function __construct(protected array $config, CacheInterface $cache = null, protected bool $types = self::TYPE_ASK)
    {
        $this->client = new Client(['verify' => $this->verify, 'timeout' => 10]);
        $this->cache  = $cache ?: new Cache($this->config['redis'] ?? []);
        if ($types) {
            $this->configs = [
                'credentials' => [
                    'ak' => $this->config['ak'],
                    'sk' => $this->config['sk'],
                ],
                'endpoint' => $this->chatUrl,
            ];
        } else {
            $this->apiKey    = $this->config['api_key'];
            $this->secretKey = $this->config['secret_key'];
        }
    }

    /**
     * 从缓存中获取token.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @return mixed
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
     * 获取token.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    protected function getToken()
    {
        $params = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->apiKey,
            'client_secret' => $this->secretKey,
        ];
        $response = $this->client->post($this->tokenUrl, ['json' => http_build_query($params), 'headers' => $this->commonHeader]);
        $response = json_decode($response->getBody()->getContents(), true);
        if (! $response) {
            throw new BaiduBceException('获取token失败');
        }
        if ($response['status'] === 200) {
            return $response['data'];
        }
        throw new BaiduBceException($response['msg']);
    }

    /**
     * 发送请求.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @return mixed
     */
    protected function request(string $path, string $body = '', array $params = [], string $method = 'POST', array $headers = [])
    {
        if ($this->types) {
            if (! $headers) {
                $headers = $this->commonHeader;
            }
            $headers['User-Agent'] = sprintf(
                'bce-sdk-php/%s/%s/%s',
                Bce::SDK_VERSION,
                php_uname(),
                phpversion()
            );
            [$hostUrl, $hostHeader] = HttpUtils::parseEndpointFromConfig($this->configs);
            $headers['Host']        = $hostHeader;
            $url                    = $hostUrl . HttpUtils::urlEncodeExceptSlash($path);
            $queryString            = HttpUtils::getCanonicalQueryString($params, false);
            if ($queryString !== '') {
                $url .= "?{$queryString}";
            }
            $headers['Content-Type']  = 'application/json';
            $now                      = new \DateTime();
            $headers['x-bce-date']    = $now->format('Y-m-d\TH:i:s\Z');
            $signer                   = new BceV1Signer();
            $headers['Authorization'] = $signer->sign(
                $this->configs['credentials'],
                $method,
                $path,
                $headers,
                $params
            );
            $request  = new Request($method, $url, $headers, $body);
            $response = $this->client->send($request);
            $response = json_decode($response->getBody()->getContents(), true);
            if (! $response) {
                throw new BaiduBceException('无响应', 500);
            }
            if (isset($response['error_code'])) {
                throw new BaiduBceException($response['error_msg'], $response['error_code']);
            }
            return $response;
        }
        $response = $this->client->post($this->chatUrl . $path . "?access_token={$this->accessToken()}", ['json' => $body, 'headers' => $this->commonHeader]);
        $response = json_decode($response->getBody()->getContents(), true);
        if (! $response) {
            throw new BaiduBceException('获取token失败');
        }
        if (isset($response['error_code'])) {
            throw new BaiduBceException($response['error_msg'], $response['error_code']);
        }
        return $response;
    }
}
