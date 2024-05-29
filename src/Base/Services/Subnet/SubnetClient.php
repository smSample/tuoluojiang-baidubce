<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjing60
 * Date: 17/8/14
 * Time: 下午4:15
 */
namespace Tuoluojiang\Baidubce\Base\Services\Subnet;

use Tuoluojiang\Baidubce\Base\Auth\BceV1Signer;
use Tuoluojiang\Baidubce\Base\BceBaseClient;
use Tuoluojiang\Baidubce\Base\Http\BceHttpClient;
use Tuoluojiang\Baidubce\Base\Http\HttpHeaders;
use Tuoluojiang\Baidubce\Base\Http\HttpMethod;
use Tuoluojiang\Baidubce\Base\Http\HttpContentTypes;

class SubnetClient extends BceBaseClient {
    private $signer;
    private $httpClient;
    private $prefix = '/v1';

    /**
     * SubnetClient constructor.
     * @param array $config
     */
    function __construct(array $config)
    {
        parent::__construct($config, 'subnet');
        $this->signer = new BceV1Signer();
        $this->httpClient = new BceHttpClient();
    }

    /**
     * Create a subnet with the specified options.
     * 
     * @param string $name
     *        The name of subnet that will be created.
     * @param string $zoneName
     *        the name of available zone which the subnet belong
     *        through listZones, we can get all available zone info at current region
     *        ee.g. "cn-gz-a"  "cn-gz-b"
     * @param string $cidr
     *        The CIDR of this subnet.
     * @param string $vpcId
     *        The id of vpc which this subnet belongs.
     * @param string $subnetType
     *        The option param to describe the type of subnet create
     * @param string $description
     *        The option param to describe the subnet
     * @param string $clientToken
     *        An ASCII string whose length is less than 64.
     *        The request will be idempotent if clientToken is provided.
     *        If the clientToken is not specified by the user, a random String generated by default algorithm will be used.
     * @param array $options
     * @return mixed
     */
    public function createSubnet($name, $zoneName, $cidr, $vpcId, $subnetType = null,
                                 $description = null, $clientToken = null,  $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        $body = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        }
        else {
            $params['clientToken'] = $clientToken;
        }
        if (empty($name)) {
            throw new \InvalidArgumentException(
                'request $name  should not be empty .'
            );
        }
        if (empty($zoneName)) {
            throw new \InvalidArgumentException(
                'request $zoneName  should not be empty .'
            );
        }
        if (empty($cidr)) {
            throw new \InvalidArgumentException(
                'request $cidr  should not be empty .'
            );
        }
        if (empty($vpcId)) {
            throw new \InvalidArgumentException(
                'request $vpcId  should not be empty .'
            );
        }
        $body['name'] = $name;
        $body['zoneName'] = $zoneName;
        $body['cidr'] = $cidr;
        $body['vpcId'] = $vpcId;
        if(!empty($subnetType)) {
            $body['subnetType'] = $subnetType;
        }
        if(!empty($description)) {
            $body['description'] = $description;
        }
        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/subnet'
        );
    }

    /**
     * Return a list of subnets owned by the authenticated user.
     * 
     * @param string $marker
     *        The optional parameter marker specified in the original request to specify
     *        where in the results to begin listing.
     *        Together with the marker, specifies the list result which listing should begin.
     *        If the marker is not specified, the list result will listing from the first one.
     * @param int $maxKeys
     *        The optional parameter to specifies the max number of list result to return.
     *        The default value is 1000.
     * @param string $vpcId
     *        The id of the vpc
     * @param string $zoneName
     *        the name of available zone which the subnet belong
     *        through listZones, we can get all available zone info at current region
     *        ee.g. "cn-gz-a"  "cn-gz-b"
     * @param string $subnetType
     *        he option param to describe the type of subnet to be created
     * @param array $options
     * @return mixed
     */
    public function listSubnets($marker = null, $maxKeys = null, $vpcId = null, $zoneName = null, 
                                $subnetType = null, $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if (!empty($marker)) {
            $params['marker'] = $marker;
        }
        if (!empty($maxKeys)) {
            $params['maxKeys'] = $maxKeys;
        }
        if (!empty($vpcId)) {
            $params['vpcId'] = $vpcId;
        }
        if (!empty($zoneName)) {
            $params['zoneName'] = $zoneName;
        }
        if (!empty($subnetType)) {
            $params['subnetType'] = $subnetType;
        }
        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/subnet'
        );
    }

    /**
     * Get the detail information of a specified subnet.
     * 
     * @param string $subnetId
     *        the id of the subnet
     * @param array $options
     * @return mixed
     */
    public function getSubnet($subnetId, $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($subnetId)) {
            throw new \InvalidArgumentException(
                'request $subnetId  should not be empty .'
            );
        }
        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,

            ),
            '/subnet/' . $subnetId
        );
    }

    /**
     * Delete the specified subnet owned by the user.
     * 
     * @param string $subnetId
     *        the id of the subnet to be deleted
     * @param string $clientToken
     *        An ASCII string whose length is less than 64.
     *        The request will be idempotent if clientToken is provided.
     *        If the clientToken is not specified by the user, a random String generated by default algorithm will be used.
     * @param array $options
     * @return mixed
     */
    public function deleteSubnet($subnetId, $clientToken = null, $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if (empty($subnetId)) {
            throw new \InvalidArgumentException(
                'request $subnetId  should not be empty .'
            );
        }
        if (empty($clientToken)) {
            $params['clientToken'] = $this -> generateClientToken();
        }
        else {
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/subnet/' . $subnetId
        );

    }

    /**
     * Modify the special attribute to new value of the subnet owned by the user.
     * 
     * @param string $subnetId
     *        The id of the specific subnet to be updated
     * @param string $name
     *        The name of the subnet
     * @param string $description
     *        The option param to describe the subnet
     * @param string $clientToken
     *        An ASCII string whose length is less than 64.
     *        The request will be idempotent if clientToken is provided.
     *        If the clientToken is not specified by the user, a random String generated by default algorithm will be used.
     * @param array $options
     * @return mixed
     */
    public function updateSubnet($subnetId, $name, $description = null, $clientToken = null, $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        $body = array();
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this -> generateClientToken();
        }
        else {
            $params['clientToken'] = $clientToken;
        }

        if (empty($subnetId)) {
            throw new \InvalidArgumentException(
                'request $subnetId  should not be empty .'
            );
        }

        if (empty($name)) {
            throw new \InvalidArgumentException(
                'request $name  should not be empty .'
            );
        }
        $body['name'] = $name;
        if (!empty($description)) {
            $body['description'] = $description;
        }
        $params['modifyAttribute'] = null;
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/subnet/' . $subnetId
        );
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
     * @return String An random String generated by Mersenne Twister.
     */
    public static function generateClientToken()
    {
        $uuid = md5(uniqid(mt_rand(), true));
        return $uuid;
    }


}
