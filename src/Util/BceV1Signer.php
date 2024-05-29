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
namespace Tuoluojiang\Baidubce\Util;

use Tuoluojiang\Baidubce\Base\Auth\SignerInterface;
use Tuoluojiang\Baidubce\Base\Auth\SignOptions;
use Tuoluojiang\Baidubce\Base\Http\HttpHeaders;
use Tuoluojiang\Baidubce\Base\Util\DateUtils;
use Tuoluojiang\Baidubce\Base\Util\HttpUtils;

/**
 * The V1 implementation of Signer with the BCE signing protocol.
 */
class BceV1Signer implements SignerInterface
{
    const BCE_AUTH_VERSION = 'bce-auth-v1';

    // Default headers to sign with the BCE signing protocol.
    private static $defaultHeadersToSign;

    public static function __init()
    {
        BceV1Signer::$defaultHeadersToSign = [
            strtolower(HttpHeaders::HOST),
            strtolower(HttpHeaders::CONTENT_LENGTH),
            strtolower(HttpHeaders::CONTENT_TYPE),
            strtolower(HttpHeaders::CONTENT_MD5),
        ];
    }

    /**
     * Sign the given request with the given set of credentials. Modifies the passed-in request to apply the signature.
     *
     * @param $credentials array the credentials to sign the request with
     * @param $httpMethod string
     * @param $path string
     * @param $headers array
     * @param $params array
     * @param $options  array   the options for signing
     * @return string the signed authorization string
     */
    public function sign(
        array $credentials,
        $httpMethod,
        $path,
        $headers,
        $params,
        $options = []
    ) {
        if (! isset($options[SignOptions::EXPIRATION_IN_SECONDS])) {
            $expirationInSeconds = SignOptions::DEFAULT_EXPIRATION_IN_SECONDS;
        } else {
            $expirationInSeconds = $options[SignOptions::EXPIRATION_IN_SECONDS];
        }

        // to compatible with ak/sk or accessKeyId/secretAccessKey
        if (isset($credentials['ak'])) {
            $accessKeyId = $credentials['ak'];
        }
        if (isset($credentials['sk'])) {
            $secretAccessKey = $credentials['sk'];
        }
        if (isset($credentials['accessKeyId'])) {
            $accessKeyId = $credentials['accessKeyId'];
        }
        if (isset($credentials['secretAccessKey'])) {
            $secretAccessKey = $credentials['secretAccessKey'];
        }

        if (isset($options[SignOptions::TIMESTAMP])) {
            $timestamp = $options[SignOptions::TIMESTAMP];
        } else {
            $timestamp = new \DateTime();
        }
        $timestamp->setTimezone(DateUtils::$UTC_TIMEZONE);

        $authString = BceV1Signer::BCE_AUTH_VERSION . '/' . $accessKeyId . '/'
            . DateUtils::formatAlternateIso8601Date(
                $timestamp
            ) . '/' . $expirationInSeconds;

        $signingKey = hash_hmac('sha256', $authString, $secretAccessKey);

        // Formatting the URL with signing protocol.
        $canonicalURI = BceV1Signer::getCanonicalURIPath($path);
        // Formatting the query string with signing protocol.
        $canonicalQueryString = HttpUtils::getCanonicalQueryString(
            $params,
            true
        );

        // Sorted the headers should be signed from the request.
        $headersToSignOption = null;
        if (isset($options[SignOptions::HEADERS_TO_SIGN])) {
            $headersToSignOption = $options[SignOptions::HEADERS_TO_SIGN];
        }
        $headersToSign = BceV1Signer::getHeadersToSign($headers, $headersToSignOption);
        // Formatting the headers from the request based on signing protocol.
        $canonicalHeader = BceV1Signer::getCanonicalHeaders($headersToSign);
        $headersToSign   = array_keys($headersToSign);
        sort($headersToSign);
        $signedHeaders = '';
        if ($headersToSignOption !== null) {
            $signedHeaders = strtolower(
                trim(implode(';', $headersToSign))
            );
        }

        $canonicalRequest = "{$httpMethod}\n{$canonicalURI}\n"
            . "{$canonicalQueryString}\n{$canonicalHeader}";
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);

        return "{$authString}/{$signedHeaders}/{$signature}";
    }

    /**
     * @param $path string
     * @return string
     */
    private static function getCanonicalURIPath($path)
    {
        if (empty($path)) {
            return '/';
        }
        if ($path[0] == '/') {
            return HttpUtils::urlEncodeExceptSlash($path);
        }
        return '/' . HttpUtils::urlEncodeExceptSlash($path);
    }

    /**
     * @param $headers array
     * @return string
     */
    private static function getCanonicalHeaders($headers)
    {
        if (count($headers) == 0) {
            return '';
        }

        $headerStrings = [];
        foreach ($headers as $k => $v) {
            if ($k === null) {
                continue;
            }
            if ($v === null) {
                $v = '';
            }
            $headerStrings[] = rawurlencode(
                strtolower(trim($k))
            ) . ':' . rawurlencode(trim($v));
        }
        sort($headerStrings);

        return implode("\n", $headerStrings);
    }

    /**
     * @param $headers array
     * @param $headersToSign array
     * @return array
     */
    private static function getHeadersToSign($headers, $headersToSign)
    {
        $ret = [];
        if ($headersToSign !== null) {
            $tmp = [];
            foreach ($headersToSign as $header) {
                $tmp[] = strtolower(trim($header));
            }
            $headersToSign = $tmp;
        }
        foreach ($headers as $k => $v) {
            if (trim((string) $v) !== '') {
                if ($headersToSign !== null) {
                    if (in_array(strtolower(trim($k)), $headersToSign)) {
                        $ret[$k] = $v;
                    }
                } else {
                    if (BceV1Signer::isDefaultHeaderToSign(
                        $k,
                        $headersToSign
                    )
                    ) {
                        $ret[$k] = $v;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * @param $header string
     * @return bool
     */
    private static function isDefaultHeaderToSign($header)
    {
        $header = strtolower(trim($header));
        if (in_array($header, BceV1Signer::$defaultHeadersToSign)) {
            return true;
        }
        $prefix = substr($header, 0, strlen(HttpHeaders::BCE_PREFIX));
        if ($prefix === HttpHeaders::BCE_PREFIX) {
            return true;
        }
        return false;
    }
}

BceV1Signer::__init();
