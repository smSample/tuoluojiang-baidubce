<?php

namespace Tuoluojiang\Baidubce\Base\Services\Acl;

use Tuoluojiang\Baidubce\Base\Auth\BceV1Signer;
use Tuoluojiang\Baidubce\Base\BceBaseClient;
use Tuoluojiang\Baidubce\Base\Http\BceHttpClient;
use Tuoluojiang\Baidubce\Base\Http\HttpHeaders;
use Tuoluojiang\Baidubce\Base\Http\HttpMethod;
use Tuoluojiang\Baidubce\Base\Http\HttpContentTypes;

class AclClient extends BceBaseClient
{

    private $signer;
    private $httpClient;
    private $prefix = '/v1';

    /**
     * AclClient constructor.
     * @param array $config
     */
    function __construct(array $config)
    {
        parent::__construct($config, 'acl');
        $this->signer = new BceV1Signer();
        $this->httpClient = new BceHttpClient();
    }


    /**
     * Get the acl information.
     * @param string $vpcId
     *        The id of the vpc
     * @param array $options
     * @return Acl information
     */
    public function getAcl($vpcId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if (empty($vpcId)) {
            throw new \InvalidArgumentException(
                'request $vpcId  should not be empty .'
            );
        } else {
            $params['vpcId'] = $vpcId;
        }
        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/acl'
        );
    }


    /**
     * @param array $aclRules
     *        The list of AclRule to be created.
     * @return mixed
     */
    public function createAclRule($aclRules, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        $body = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        if (empty($aclRules)) {
            throw new \InvalidArgumentException(
                'request $aclRules should not be empty .'
            );
        }
        $body['aclRules'] = $this->objectToArray($aclRules);
        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/acl/rule'
        );
    }

    /**
     * Return a list of acl rules which belong to the subnet.
     * @param string $marker
     *        The optional parameter marker specified in the original request to specify
     *        where in the results to begin listing.
     *        Together with the marker, specifies the list result which listing should begin.
     *        If the marker is not specified, the list result will listing from the first one.
     * @param int $maxkeys
     *        The optional parameter to specifies the max number of list result to return.
     *        The default value is 1000.
     * @param string $subnetId
     *        The subnet id which you want to list aclRules .
     * @param array $options
     * @return The list of acl rule which belong to the $subnetId  subnet.
     */
    public function listAclRules($subnetId, $marker = null, $maxkeys = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if (!empty($marker)) {
            $params['marker'] = $marker;
        }
        if (!empty($maxkeys)) {
            $params['maxKeys'] = $maxkeys;
        }
        if (empty($subnetId)) {
            throw new \InvalidArgumentException(
                'request $subnetId should not be empty .'
            );
        } else {
            $params['subnetId'] = $subnetId;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/acl/rule'
        );

    }

    /**
     * Delete the specified aclRule owned by the user.
     * @param string $aclRuleId
     *        The id of the specified acl rule.
     * @param string $clientToken
     *        An ASCII string whose length is less than 64.
     *        The request will be idempotent if clientToken is provided.
     *        If the clientToken is not specified by the user, a random String generated by default algorithm will be used.
     * @param array $options
     * @return mixed
     */
    public function deleteAclRule($aclRuleId, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if (empty($aclRuleId)) {
            throw new \InvalidArgumentException(
                'request $vpcId  should not be empty .'
            );
        }
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/acl/rule/' . $aclRuleId
        );
    }

    /**
     * update the rule attribute of the vpc owned by the user.
     * @param string $aclRuleId
     *        The id of the specified acl rule.
     * @param string $description
     *        The description of the specified acl rule.
     * @param string $protocol
     *        The protocol of the specified acl rule.include 'all' 'tcp' 'udp' and 'icmp'
     * @param string $sourceIpAddress
     *        The sourceIpAddress of the specified acl rule.
     * @param string $destinationIpAddress
     *        The destinationIpAddress of the specified acl rule.
     * @param string $sourcePort
     *        The sourcePort of the specified acl rule.
     * @param string $destinationPort
     *        TThe destinationPort of the specified acl rule.
     * @param string $position
     *        The position of the specified acl rule.the range of position is[1,5000]
     * @param string $action
     *        The action of the specified acl rule.include 'allow' and 'deny'.
     * @param string $clientToken
     *        An ASCII string whose length is less than 64.
     *        The request will be idempotent if clientToken is provided.
     *        If the clientToken is not specified by the user, a random String generated by default algorithm will be used.
     * @param array $options
     * @return mixed
     */
    public function updateAclRule($aclRuleId, $description = null, $protocol = null, $sourceIpAddress = null, $destinationIpAddress = null, $sourcePort = null, $destinationPort = null, $position = null, $action = null, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        $body = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        if (empty($aclRuleId)) {
            throw new \InvalidArgumentException(
                'request $aclRuleId  should not be empty .'
            );
        }
        $body['description'] = $description;
        $body['protocol'] = $protocol;
        $body['sourceIpAddress'] = $sourceIpAddress;
        $body['destinationIpAddress'] = $destinationIpAddress;
        $body['sourcePort'] = $sourcePort;
        $body['destinationPort'] = $destinationPort;
        $body['position'] = $position;
        $body['action'] = $action;
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/acl/rule/' . $aclRuleId
        );
    }


    /**
     * convert object to array
     *
     * @param object $obj
     *
     * @return array
     */
    function objectToArray($obj)
    {
        if (is_array($obj)) {
            return $obj;
        }
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        $arr = array();
        foreach ($_arr as $key => $value) {
            $value = (is_array($value)) || is_object($value) ? $this->objectToArray($value) : $value;
            $arr[$key] = $value;
        }
        return $arr;
    }


    /**
     * Create HttpClient and send request
     *
     * @param string $httpMethod
     *          The Http request method
     *
     * @param array $varArgs
     *          The extra arguments
     *
     * @param string $requestPath
     *          The Http request uri
     *
     * @return mixed The Http response and headers.
     */
    private function sendRequest($httpMethod, array $varArgs, $requestPath = '/')
    {
        $defaultArgs = array(
            'config' => array(),
            'body' => null,
            'headers' => array(),
            'params' => array(),
        );

        $args = array_merge($defaultArgs, $varArgs);
        if (empty($args['config'])) {
            $config = $this->config;
        } else {
            $config = array_merge(
                array(),
                $this->config,
                $args['config']
            );
        }
        if (!isset($args['headers'][HttpHeaders::CONTENT_TYPE])) {
            $args['headers'][HttpHeaders::CONTENT_TYPE] = HttpContentTypes::JSON;
        }
        $path = $this->prefix . $requestPath;
        $response = $this->httpClient->sendRequest(
            $config,
            $httpMethod,
            $path,
            $args['body'],
            $args['headers'],
            $args['params'],
            $this->signer
        );

        $result = $this->parseJsonResult($response['body']);

        return $result;
    }

    /**
     * The default method to generate the random String for clientToken if the optional parameter clientToken
     * is not specified by the user.
     *
     * The default algorithm is Mersenne Twister to generate a random UUID,
     * @return string
     */
    public static function generateClientToken()
    {
        $uuid = md5(uniqid(mt_rand(), true));
        return $uuid;
    }
}
