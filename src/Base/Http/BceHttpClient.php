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
namespace Tuoluojiang\Baidubce\Base\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Psr\Log\LoggerInterface;
use Tuoluojiang\Baidubce\Base\Auth\SignerInterface;
use Tuoluojiang\Baidubce\Base\Bce;
use Tuoluojiang\Baidubce\Base\BceClientConfigOptions;
use Tuoluojiang\Baidubce\Base\Exception\BceClientException;
use Tuoluojiang\Baidubce\Base\Exception\BceServiceException;
use Tuoluojiang\Baidubce\Base\Log\LogFactory;
use Tuoluojiang\Baidubce\Base\Util\DateUtils;
use Tuoluojiang\Baidubce\Base\Util\HttpUtils;

/**
 * Standard Http request of BCE.
 */
class BceHttpClient
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->guzzleClient = new Client();
        $this->logger       = LogFactory::getLogger(get_class($this));
    }

    /**
     * Send request to BCE.
     *
     * @param string $httpMethod the Http request method, uppercase
     * @param string $path the resource path
     * @param resource|string $body the Http request body
     * @param array $headers the extra Http request headers
     * @param array $params the extra Http url query strings
     * @param SignerInterface $signer this function will generate authorization header
     * @param resource|string $outputStream write the Http response to this stream
     * @param mixed $options
     * @throws BceClientException
     * @throws BceServiceException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return array
     */
    public function sendRequest(
        array $config,
        $httpMethod,
        $path,
        $body,
        array $headers,
        array $params,
        SignerInterface $signer,
        $outputStream = null,
        $options = []
    ) {
        $headers[HttpHeaders::USER_AGENT] = sprintf(
            'bce-sdk-php/%s/%s/%s',
            Bce::SDK_VERSION,
            php_uname(),
            phpversion()
        );
        if (! isset($headers[HttpHeaders::BCE_DATE])) {
            $now = new \DateTime();
            $now->setTimezone(DateUtils::$UTC_TIMEZONE);
            $headers[HttpHeaders::BCE_DATE] = DateUtils::formatAlternateIso8601Date($now);
        }
        [$hostUrl, $hostHeader]     = HttpUtils::parseEndpointFromConfig($config);
        $headers[HttpHeaders::HOST] = $hostHeader;
        $url                        = $hostUrl . HttpUtils::urlEncodeExceptSlash($path);
        $queryString                = HttpUtils::getCanonicalQueryString($params, false);
        if ($queryString !== '') {
            $url .= "?{$queryString}";
        }

        if (! isset($headers[HttpHeaders::CONTENT_LENGTH])) {
            $headers[HttpHeaders::CONTENT_LENGTH] = $this->guessContentLength($body);
        }
        $entityBody = null;
        if ($headers[HttpHeaders::CONTENT_LENGTH] == 0) {
            //if passing a stream and content length is 0, guzzle will remove
            //"Content-Length:0" from header, to work around this, we have to
            //set body to a empty string
            $entityBody = '';
        } elseif (is_resource($body)) {
            $offset     = ftell($body);
            $original   = new Stream($body);
            $entityBody = new LimitStream($original, $headers[HttpHeaders::CONTENT_LENGTH], $offset);
        } else {
            $entityBody = $body;
        }

        $credentials = $config[BceClientConfigOptions::CREDENTIALS];
        // if the request is send through the STS certification
        if (array_key_exists(BceClientConfigOptions::SESSION_TOKEN, $credentials)) {
            $headers[HttpHeaders::BCE_SESSION_TOKEN] = $credentials[BceClientConfigOptions::SESSION_TOKEN];
        }
        $headers[HttpHeaders::AUTHORIZATION] = $signer->sign(
            $credentials,
            $httpMethod,
            $path,
            $headers,
            $params,
            $options
        );

        if (LogFactory::isDebugEnabled()) {
            $this->logger->debug('HTTP method: ' . $httpMethod);
            $this->logger->debug('HTTP url: ' . $url);
            $this->logger->debug('HTTP headers: ' . print_r($headers, true));
        }

        $guzzleRequestOptions = ['exceptions' => false];
        if (isset(
            $config[BceClientConfigOptions::CONNECTION_TIMEOUT_IN_MILLIS])
        ) {
            $guzzleRequestOptions['connect_timeout'] = $config[BceClientConfigOptions::CONNECTION_TIMEOUT_IN_MILLIS]
                    / 1000.0;
        }
        if (isset(
            $config[BceClientConfigOptions::SOCKET_TIMEOUT_IN_MILLIS])
        ) {
            $guzzleRequestOptions['timeout'] = $config[BceClientConfigOptions::SOCKET_TIMEOUT_IN_MILLIS]
                    / 1000.0;
        }
        $guzzleRequest = new Request($httpMethod, $url, $headers, $entityBody);

        // Send request
        try {
            $guzzleResponse = $this->guzzleClient->send($guzzleRequest,$guzzleRequestOptions);
        } catch (\Exception $e) {
            throw new BceClientException($e->getMessage());
        }

        //statusCode < 200
        if ($guzzleResponse->getStatusCode()) {
            throw new BceClientException('Can not handle 1xx Http status code');
        }
        //for chunked http response, http status code can not be trust
        //error code in http body also mean a failed http response
        if ($guzzleResponse->getHeaderLine('Transfer-Encoding') === 'chunked') {
            if (stripos($guzzleResponse->getHeaderLine('Content-Type'), 'application/json') !== false) {
                $responseBody = $guzzleResponse->getBody();
                if (isset($responseBody['code']) && $responseBody['code'] === 'InternalError') {
                    $guzzleResponse = new Response(500);
                }
            }
        }
        //Successful means 2XX or 304
        if (! $guzzleResponse->getStatusCode()) {
            $requestId = $guzzleResponse->getHeader(HttpHeaders::BCE_REQUEST_ID);
            $message   = $guzzleResponse->getReasonPhrase();
            $code      = null;
            if (stripos($guzzleResponse->getHeaderLine('Content-Type'), 'application/json') !== false) {
                try {
                    $responseBody = $guzzleResponse->getBody();
                    if (isset($responseBody['message'])) {
                        $message = $responseBody['message'];
                    }
                    if (isset($responseBody['code'])) {
                        $code = $responseBody['code'];
                    }
                } catch (\Exception $e) {
                    // ignore this error
                    $this->logger->warning(
                        'Fail to parse error response body: '
                        . $e->getMessage()
                    );
                }
            }
            throw new BceServiceException(
                $requestId,
                $code,
                $message,
                $guzzleResponse->getStatusCode()
            );
        }
        if ($outputStream === null) {
            $body = $guzzleResponse->getBody(true);
        } else {
            $body = null;
            // detach the stream so that it will not be closed when the response
            // is garbage collected.
            $guzzleResponse->getBody()->detach();
        }
        return [
            'headers' => $this->parseHeaders($guzzleResponse),
            'body'    => $body,
        ];
    }

    /**
     * @param mixed $body the request body
     * @return number
     */
    private function guessContentLength($body)
    {
        if (is_null($body)) {
            return 0;
        }
        if (is_string($body)) {
            return strlen($body);
        }
        if (is_resource($body)) {
            $stat = fstat($body);
            return $stat['size'];
        }
        if (is_object($body) && method_exists($body, 'getSize')) {
            return $body->getSize();
        }

        throw new \InvalidArgumentException(
            sprintf('No %s is specified.', HttpHeaders::CONTENT_LENGTH)
        );
    }

    /**
     * @param $guzzleResponse
     * @return array
     */
    private function parseHeaders($guzzleResponse)
    {
        $responseHeaders = [];
        foreach ($guzzleResponse->getHeaders() as $header) {
            $value                               = $header->toArray();
            $responseHeaders[$header->getName()] = $value[0];
        }
        return $responseHeaders;
    }
}
