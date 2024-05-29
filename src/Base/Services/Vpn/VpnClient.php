<?php


namespace Tuoluojiang\Baidubce\Base\Services\Vpn;

use Tuoluojiang\Baidubce\Base\Auth\BceV1Signer;
use Tuoluojiang\Baidubce\Base\BceBaseClient;
use Tuoluojiang\Baidubce\Base\Http\BceHttpClient;
use Tuoluojiang\Baidubce\Base\Http\HttpHeaders;
use Tuoluojiang\Baidubce\Base\Http\HttpMethod;
use Tuoluojiang\Baidubce\Base\Http\HttpContentTypes;
use Tuoluojiang\Baidubce\Base\Services\Vpn\model\Billing;
use Tuoluojiang\Baidubce\Base\Services\Vpn\model\IkeConfig;
use Tuoluojiang\Baidubce\Base\Services\Vpn\model\IpsecConfig;
use Tuoluojiang\Baidubce\Base\Services\Vpn\util\CheckUtils;

/**
 * This module provides a client class for VpnClient.
 */
class VpnClient extends BceBaseClient
{

    private $signer;
    private $httpClient;
    private $prefix = '/v1';

    /**
     * The VpnClient constructor.
     *
     * @param array $config The client configuration
     */
    function __construct(array $config)
    {
        parent::__construct($config, 'vpn');
        $this->signer = new BceV1Signer();
        $this->httpClient = new BceHttpClient();
    }

    /**
     * Return a list of vpn.
     * @param $vpcId
     *         Belonging VPC The identifier
     *
     * @param null $eip
     *        vpn bound eip address
     *
     * @param null $marker
     *        The optional parameter marker specified in the original request to specify
     *        where in the results to begin listing.
     *        Together with the marker, specifies the list result which listing should begin.
     *        If the marker is not specified, the list result will listing from the first one.
     *
     * @param null $maxkeys
     *        The optional parameter to specifies the max number of list result to return.
     *        The default value is 1000.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating VpnClient instance.
     *
     * @return mixed
     */
    public function listVpn($vpcId, $eip = null, $marker = null, $maxkeys = null, $options = array()){
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpcId, '$vpcId');

        $params = array();
        $params['vpcId'] = $vpcId;
        if(!empty($marker)) {
            $params['marker'] = $marker;
        }
        if(!empty($maxkeys)) {
            $params['maxKeys'] = $maxkeys;
        }
        if(!empty($eip)) {
            $params['eip'] = $eip;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/vpn'
        );

    }

    /**
     * Create an Vpn with the specified options.
     *
     * @param $vpcId
     *           vpc  id
     *
     * @param $vpnName
     *           vpn  name
     *
     * @param Billing $billing
     *           billing information. The optional parameter, default paymentTiming is Postpaid
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param null $description
     *           vpn description
     *
     * @param null $eip
     *           bind eip
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function createVpn($vpcId, $vpnName, Billing $billing, $clientToken = null, $description = null, $eip = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpcId, '$vpcId');
        CheckUtils::isBlank($vpnName, '$vpnName');
        CheckUtils::isBlank($billing, '$billing');

        $body = array();
        $body['vpcId'] = $vpcId;
        $body['vpnName'] = $vpnName;
        $body['billing'] = $this->objectToArray($billing);
        if (!empty($description)) {
            $body['description'] = $description;
        }
        if (!empty($description)) {
            $body['eip'] = $eip;
        }

        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn'
        );
    }

    /**
     * update vpn
     *
     * @param $vpnId
     *          vpn  id
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param null $vpnName
     *           vpn  name
     *
     * @param null $description
     *           vpn description
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function updateVpn($vpnId, $clientToken = null, $vpnName = null, $description = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');

        $params = array();
        $params['modifyAttribute'] = null;
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        $body = array();
        if (!empty($description)) {
            $body['description'] = $description;
        }
        if (!empty($vpnName)) {
            $body['vpnName'] = $vpnName;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn/' . $vpnId
        );
    }

    /**
     * get vpn
     *
     * @param $vpnId
     *          vpn  id
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function getVpn($vpnId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/vpn/' . $vpnId
        );
    }

    /**
     * delete vpn
     *
     * @param $vpnId
     *          vpn  id
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixedk
     */
    public function deleteVpn($vpnId, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');

        $params = array();
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
            '/vpn/' . $vpnId
        );
    }

    /**
     * vpn bind eip.
     *
     * @param $vpnId
     *          vpn id
     *
     * @param null $eip
     *          eip address
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function bindEip($vpnId, $eip, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');
        CheckUtils::isBlank($eip, '$eip');

        $params = array();
        $params['bind'] = null;
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        $body = array();
        $body['eip'] = $eip;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn/' . $vpnId
        );
    }

    /**
     * vpn unbind eip.
     *
     * @param $vpnId
     *          vpn id
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function unBindEip($vpnId, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');

        $params = array();
        $params['unbind'] = null;
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/vpn/' . $vpnId
        );
    }

    /**
     * renew vpn .
     *
     * @param $vpnId
     *          vpn id
     *
     * @param Billing $billing
     *          billing information. The optional parameter, default paymentTiming is Postpaid
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function renewVpn($vpnId, Billing $billing, $clientToken = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');
        CheckUtils::isBlank($billing, '$billing');

        $params = array();
        $params['purchaseReserved'] = null;
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        $body = array();
        $body['billing'] = $this->objectToArray($billing);

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn/' . $vpnId
        );
    }

    /**
     * Create an VpnConn.
     *
     * @param $vpnId
     *        vpn id
     * @param $secretKey
     *         Shared key, 8~17 characters, English, numbers and symbols must exist at the same time, and the symbols are limited to @#$%^*()_
     *
     * @param array $localSubnets
     *         Local network cidr List
     *
     * @param $remoteIp
     *         Peer VPN Gateway public network IP
     *
     * @param array $remoteSubnets
     *         Peer network cidr List
     *
     * @param $vpnConnName
     *        VPN Tunnel name, uppercase and lowercase letters, numbers and -_/. special characters, must start with a letter, length 1-65
     *
     * @param IkeConfig $ikeConfig
     *        IKE  Configuration
     *
     * @param IpsecConfig $ipsecConfig
     *        IPSec  Configuration
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param null $description
     *           vpnconn description
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function createVpnConn($vpnId, $secretKey,  array $localSubnets, $remoteIp, array $remoteSubnets, $vpnConnName, IkeConfig $ikeConfig, IpsecConfig $ipsecConfig, $clientToken = null, $description = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');
        CheckUtils::isBlank($secretKey, '$secretKey');
        CheckUtils::isBlank($localSubnets, '$localSubnets');
        CheckUtils::isBlank($remoteIp, '$remoteIp');
        CheckUtils::isBlank($remoteSubnets, '$remoteSubnets');
        CheckUtils::isBlank($vpnConnName, '$vpnConnName');
        CheckUtils::isBlank($ikeConfig, '$ikeConfig');
        CheckUtils::isBlank($ipsecConfig, '$ipsecConfig');

        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        $body = array();
        $body['secretKey'] = $secretKey;
        $body['localSubnets'] = $localSubnets;
        $body['remoteIp'] = $remoteIp;
        $body['remoteSubnets'] = $remoteSubnets;
        $body['vpnConnName'] = $vpnConnName;
        $body['ikeConfig'] = $this->objectToArray($ikeConfig);
        $body['ipsecConfig'] = $this->objectToArray($ipsecConfig);
        if (!empty($description)) {
            $body['description'] = $description;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn/' . $vpnId . '/vpnconn'
        );
    }

    /**
     * update an VpnConn.
     *
     * @param $vpnConnId
     *        vpnconn id
     *
     * @param $vpnId
     *        vpn id
     * @param $secretKey
     *         Shared key, 8~17 characters, English, numbers and symbols must exist at the same time, and the symbols are limited to @#$%^*()_
     *
     * @param array $localSubnets
     *         Local network cidr List
     *
     * @param $remoteIp
     *         Peer VPN Gateway public network IP
     *
     * @param array $remoteSubnets
     *         Peer network cidr List
     *
     * @param $vpnConnName
     *        VPN Tunnel name, uppercase and lowercase letters, numbers and -_/. special characters, must start with a letter, length 1-65
     *
     * @param IkeConfig $ikeConfig
     *        IKE  Configuration
     *
     * @param IpsecConfig $ipsecConfig
     *        IPSec  Configuration
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param null $description
     *           vpnconn description
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function updateVpnConn($vpnConnId, $vpnId, $secretKey,  array $localSubnets, $remoteIp, array $remoteSubnets, $vpnConnName, IkeConfig $ikeConfig, IpsecConfig $ipsecConfig, $clientToken = null, $description = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnConnId');
        CheckUtils::isBlank($vpnId, '$vpnId');
        CheckUtils::isBlank($secretKey, '$secretKey');
        CheckUtils::isBlank($localSubnets, '$localSubnets');
        CheckUtils::isBlank($remoteIp, '$remoteIp');
        CheckUtils::isBlank($remoteSubnets, '$remoteSubnets');
        CheckUtils::isBlank($vpnConnName, '$vpnConnName');
        CheckUtils::isBlank($ikeConfig, '$ikeConfig');
        CheckUtils::isBlank($ipsecConfig, '$ipsecConfig');

        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        $body = array();
        $body['vpnId'] = $vpnId;
        $body['secretKey'] = $secretKey;
        $body['localSubnets'] = $localSubnets;
        $body['remoteIp'] = $remoteIp;
        $body['remoteSubnets'] = $remoteSubnets;
        $body['vpnConnName'] = $vpnConnName;
        $body['ikeConfig'] = $this->objectToArray($ikeConfig);
        $body['ipsecConfig'] = $this->objectToArray($ipsecConfig);
        if (!empty($description)) {
            $body['description'] = $description;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
                'params' => $params,
            ),
            '/vpn/vpnconn/' . $vpnConnId
        );
    }

    /**
     * get vpnConn  list by vpnId
     *
     * @param $vpnId
     *          vpn  id
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function getVpnConn($vpnId, $clientToken = null , $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnId, '$vpnId');

        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/vpn/vpnconn/' . $vpnId
        );
    }

    /**
     * delete vpnConn
     *
     * @param $vpnConnId
     *          vpnconn  id
     *
     * @param null $clientToken
     *          if the clientToken is not specified by the user, a random String
     *          generated by default algorithm will be used.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating Vpn instance.
     *
     * @return mixed
     */
    public function deleteVpnConn($vpnConnId, $clientToken = null , $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        CheckUtils::isBlank($vpnConnId, '$vpnId');

        $params = array();
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
            '/vpn/vpnconn/' . $vpnConnId
        );
    }


    /**
     * convert object to array
     *
     * @param object $obj
     *
     * @return array
     */
    function objectToArray(object $obj)
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
    private function sendRequest(string $httpMethod, array $varArgs, $requestPath = '/')
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
