<?php
/*
* Copyright 2017 Baidu, Inc.
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may not
* use this file except in compliance with the License. You may obtain a copy of
* the License at
*
* Http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations under
* the License.
*/

namespace Tuoluojiang\Baidubce\Base\Services\Bcc;

use Tuoluojiang\Baidubce\Base\Auth\BceV1Signer;
use Tuoluojiang\Baidubce\Base\BceBaseClient;
use Tuoluojiang\Baidubce\Base\Http\BceHttpClient;
use Tuoluojiang\Baidubce\Base\Http\HttpHeaders;
use Tuoluojiang\Baidubce\Base\Http\HttpMethod;
use Tuoluojiang\Baidubce\Base\Http\HttpContentTypes;
use Tuoluojiang\Baidubce\Base\BceClientConfigOptions;
use Tuoluojiang\Baidubce\Base\Services\Bcc\model\Billing;
use Tuoluojiang\Baidubce\Base\Services\Bcc\model\SecurityGroupRuleModel;

/**
 * This module provides a client class for BCC.
 */
class BccClient extends BceBaseClient 
{

    private $signer;
    private $httpClient;
    private $prefix = '/v2';

    /**
     * The BccClient constructor.
     *
     * @param array $config The client configuration
     */
    function __construct(array $config)
    {
        parent::__construct($config, 'bcc');
        $this->signer = new BceV1Signer();
        $this->httpClient = new BceHttpClient();
    }

    /**
     * Create a bcc Instance with the specified options.
     * You must fill the field of clientToken,which is especially for keeping idempotent.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceType (unicode)
     *          The specified Specification to create the instance,
     *          See more detail on
     *               https://cloud.baidu.com/doc/BCC/API.html#InstanceType
     *
     * @param int $cpuCount
     *          The parameter to specified the cpu core to create the instance.
     *
     * @param int $memoryCapacityInGB
     *          The parameter to specified the capacity of memory in GB to create the instance.
     *
     * @param string $imageId (unicode)
     *          The id of image, list all available image in BccClient.listImages.
     *
     * @param Billing $billing
     *          Billing information.
     *
     * @param int $localDiskSizeInGB
     *          The optional parameter to specify the temporary disk size in GB.
     *          The temporary disk excludes the system disk, available is 0-500GB.
     *
     * @param array $createCdsList
     *          The optional array of volume detail info to create.
     *
     * @param int $networkCapacityInMbps
     *          The optional parameter to specify the bandwidth in Mbps for the new instance.
     *          It must among 0 and 200, default value is 0.
     *          If it's specified to 0, it will get the internal ip address only.
     *
     * @param int $purchaseCount
     *          The number of instance to buy, the default value is 1.
     *
     * @param string $name
     *          The optional parameter to desc the instance that will be created.
     *
     * @param string $adminPass
     *          The optional parameter to specify the password for the instance.
     *          If specify the adminPass,the adminPass must be a 8-16 characters String
     *          which must contains letters, numbers and symbols.
     *          The symbols only contains "!@#$%^*()".
     *          The adminPass will be encrypted in AES-128 algorithm
     *          with the substring of the former 16 characters of user SecretKey.
     *          If not specify the adminPass, it will be specified by an random string.
     *          See more detail on
     *               https://bce.baidu.com/doc/BCC/API.html#.7A.E6.31.D8.94.C1.A1.C2.1A.8D.92.ED.7F.60.7D.AF
     *
     * @param string $zoneName
     *          The optional parameter to specify the available zone for the instance.
     *          See more detail through listZones method
     *
     * @param string $subnetId
     *          The optional parameter to specify the id of subnet from vpc, optional param
     *          default value is default subnet from default vpc
     *
     * @param string $securityGroupId
     *          The optional parameter to specify the securityGroupId of the instance
     *          vpcId of the securityGroupId must be the same as the vpcId of subnetId
     *          See more detail through listSecurityGroups method
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createInstance($cpuCount, $memoryCapacityInGB, $imageId, $options = array())
    {
        list($instanceType, $billing, $localDiskSizeInGB, $createCdsList, $networkCapacityInMbps,
            $purchaseCount, $name, $adminPass, $zoneName, $subnetId, $securityGroupId,
            $clientToken, $config) = $this->parseOptions($options,
            'instanceType',
            'billing',
            'localDiskSizeInGB',
            'createCdsList',
            'networkCapacityInMbps',
            'purchaseCount',
            'name',
            'adminPass',
            'zoneName',
            'subnetId',
            'securityGroupId',
            'clientToken',
            'config');
        $params = array();
        if ($clientToken !== null) {
            $params['clientToken'] = $clientToken;
        } else {
            $params['clientToken'] = $this->generateClientToken();
        }
        if (empty($billing)) {
            $billing = $this->generateDefaultBilling();
        }
        if (empty($cpuCount)) {
            throw new \InvalidArgumentException(
                '$cpuCount should not be empty.'
            );
        }
        if (empty($memoryCapacityInGB)) {
            throw new \InvalidArgumentException(
                '$memoryCapacityInGb should not be empty.'
            );
        }
        if (empty($imageId)) {
            throw new \InvalidArgumentException(
                '$imageId should not be empty.'
            );
        }
        $body = array();
        $body['cpuCount'] = $cpuCount;
        $body['memoryCapacityInGB'] = $memoryCapacityInGB;
        $body['imageId'] = $imageId;
        $body['billing'] = $this->objectToArray($billing);
        if ($instanceType !== null) {
            $body['instanceType'] = $instanceType;
        }
        if (!empty($localDiskSizeInGB)) {
            $body['localDiskSizeInGB'] = $localDiskSizeInGB;
        }
        if ($createCdsList !== null) {
            $body['createCdsList'] = $createCdsList;
        }
        if (!empty($networkCapacityInMbps)) {
            $body['networkCapacityInMbps'] = $networkCapacityInMbps;
        }
        if (empty($purchaseCount)) {
            $purchaseCount = 1;
        }
        $body['purchaseCount'] = $purchaseCount;
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($adminPass !== null) {
            $credentials = $this->config[BceClientConfigOptions::CREDENTIALS];
            $secretAccessKey = null;
            if(isset($credentials['sk'])){
                $secretAccessKey = $credentials['sk'];
            }
            if(isset($credentials['secretAccessKey'])){
                $secretAccessKey = $credentials['secretAccessKey'];
            }
            $cipherAdminPass = $this->aes128WithFirst16Char($adminPass, $secretAccessKey);
            $body['adminPass'] = $cipherAdminPass;
        }
        if ($zoneName !== null) {
            $body['zoneName'] = $zoneName;
        }
        if ($subnetId != null) {
            $body['subnetId'] = $subnetId;
        }
        if ($securityGroupId != null) {
            $body['securityGroupId'] = $securityGroupId;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance'
        );
    }

    /**
     * Create a Instance from dedicatedHost with the specified options.
     * You must fill the field of clientToken,which is especially for keeping idempotent.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param int $cpuCount
     *          The specified number of cpu core to create the instance,
     *          is less than or equal to the remain of dedicated host.
     *
     * @param int $memoryCapacityInGB
     *          The capacity of memory to create the instance,
     *          is less than or equal to the remain of dedicated host.
     *
     * @param string $imageId
     *          The id of image, list all available image in BccClient.listImages.
     *
     * @param string $dedicatedHostId (unicode)
     *          The id of dedicated host, we can locate the instance in specified dedicated host.
     *
     * @param array $ephemeralDisks
     *          The optional array of ephemeral volume detail info to create.
     *
     * @param int $purchaseCount
     *          The number of instance to buy, the default value is 1.
     *
     * @param string $name
     *          The optional parameter to desc the instance that will be created.
     *
     * @param string $adminPass
     *          The optional parameter to specify the password for the instance.
     *          If specify the adminPass,the adminPass must be a 8-16 characters String
     *          which must contains letters, numbers and symbols.
     *          The symbols only contains "!@#$%^*()".
     *          The adminPass will be encrypted in AES-128 algorithm
     *          with the substring of the former 16 characters of user SecretKey.
     *          If not specify the adminPass, it will be specified by an random string.
     *          See more detail on
     *              https://bce.baidu.com/doc/BCC/API.html#.7A.E6.31.D8.94.C1.A1.C2.1A.8D.92.ED.7F.60.7D.AF
     *
     * @param string $subnetId
     *          The optional parameter to specify the id of subnet from vpc, optional param
     *          default value is default subnet from default vpc
     *
     * @param string $securityGroupId
     *          The optional parameter to specify the securityGroupId of the instance
     *          vpcId of the securityGroupId must be the same as the vpcId of subnetId
     *          See more detail through listSecurityGroups method
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createInstanceFromDedicatedHost($cpuCount, $memoryCapacityInGB, $imageId, $dedicatedHostId,
                                                    $options = array())
    {
        list($ephemeralDisks, $purchaseCount, $name, $adminPass, $subnetId, $securityGroupId,
            $clientToken, $config) = $this->parseOptions($options,
            'ephemeralDisks',
            'purchaseCount',
            'name',
            'adminPass',
            'subnetId',
            'securityGroupId',
            'clientToken',
            'config');
        $params = array();
        if ($clientToken !== null) {
            $params['clientToken'] = $clientToken;
        } else {
            $params['clientToken'] = $this->generateClientToken();
        }
        if (empty($cpuCount)) {
            throw new \InvalidArgumentException(
                '$cpuCount should not be empty.'
            );
        }
        if (empty($memoryCapacityInGB)) {
            throw new \InvalidArgumentException(
                '$memoryCapacityInGb should not be empty.'
            );
        }
        if (empty($imageId)) {
            throw new \InvalidArgumentException(
                '$imageId should not be empty.'
            );
        }
        if (empty($dedicatedHostId)) {
            throw new \InvalidArgumentException(
                '$dedicatedHostId should not be empty.'
            );
        }
        $body = array();
        $body['cpuCount'] = $cpuCount;
        $body['memoryCapacityInGB'] = $memoryCapacityInGB;
        $body['imageId'] = $imageId;
        $body['dedicatedHostId'] = $dedicatedHostId;
        if ($ephemeralDisks !== null) {
            $body['ephemeralDisks'] = $this->objectToArray($ephemeralDisks);
        }
        if (empty($purchaseCount)) {
            $purchaseCount = 1;
        }
        $body['purchaseCount'] = $purchaseCount;
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($adminPass !== null) {
            $body['adminPass'] = $adminPass;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance'
        );
    }

    /**
     * Return a array of instances owned by the authenticated user.
     *
     * @param string $marker
     *          The optional parameter marker specified in the original request to specify
     *          where in the results to begin listing.
     *          Together with the marker, specifies the list result which listing should begin.
     *          If the marker is not specified, the list result will listing from the first one.
     *
     * @param int $maxKeys
     *          The optional parameter to specifies the max number of list result to return.
     *          The default value is 1000.
     *
     * @param string $internalIp
     *          The identified internal ip of instance.
     *
     * @param string $dedicatedHostId
     *          get instance list filtered by id of dedicated host
     *
     * @param string $zoneName
     *          get instance list filtered by name of available zone
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listInstances($marker=null, $maxKeys=null, $internalIp=null, $dedicatedHostId=null,
                                  $zoneName=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if ($marker !== null) {
            $params['marker'] = $marker;
        }
        if ($maxKeys !== null) {
            $params['maxKeys'] = $maxKeys;
        }
        if ($internalIp !== null) {
            $params['internalIp'] = $internalIp;
        }
        if ($dedicatedHostId !== null) {
            $params['dedicatedHostId'] = $dedicatedHostId;
        }
        if ($zoneName !== null) {
            $params['zoneName'] = $zoneName;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/instance'
        );
    }

    /**
     * Get the detail information of specified instance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function getInstance($instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Starting the instance owned by the user.
     * You can start the instance only when the instance is Stopped,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          id of instance proposed to start.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function startInstance($instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        $params['start'] = null;
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Stopping the instance owned by the user.
     * You can stop the instance only when the instance is Running,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param bool $forceStop
     *          The optional parameter to stop the instance forcibly.If true,
     *          it will stop the instance just like power off immediately
     *          and it may result in losing important data which have not been written to disk.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function stopInstance($instanceId, $forceStop=false, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $body = array();
        $body['forceStop'] = $forceStop;
        $params = array();
        $params['stop'] = null;
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Rebooting the instance owned by the user.
     * You can reboot the instance only when the instance is Running,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.get_instance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param bool $forceStop
     *          The optional parameter to stop the instance forcibly.If true,
     *          it will stop the instance just like power off immediately
     *          and it may result in losing important data which have not been written to disk.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function rebootInstance($instanceId, $forceStop=false, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $body = array();
        $body['forceStop'] = $forceStop;
        $params = array();
        $params['reboot'] = null;
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Modifying the password of the instance.
     * You can change the instance password only when the instance is Running or Stopped ,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param string $adminPass (unicode)
     *          The new password to update.
     *          The adminPass will be encrypted in AES-128 algorithm
     *          with the substring of the former 16 characters of user SecretKey.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function modifyInstancePassword($instanceId, $adminPass, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($adminPass)) {
            throw new \InvalidArgumentException(
                'request $adminPass should not be empty.'
            );
        }
        $body = array();
        $credentials = $this->config[BceClientConfigOptions::CREDENTIALS];
        $secretAccessKey = null;
        if(isset($credentials['sk'])){
            $secretAccessKey = $credentials['sk'];
        }
        if(isset($credentials['secretAccessKey'])){
            $secretAccessKey = $credentials['secretAccessKey'];
        }
        $cipherAdminPass = $this->aes128WithFirst16Char($adminPass, $secretAccessKey);
        $body['adminPass'] = $cipherAdminPass;
        $params = array();
        $params['changePass'] = null;
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Modifying the special attribute to new value of the instance.
     * You can reboot the instance only when the instance is Running or Stopped ,
     * otherwise, it's will get 409 errorCode.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param string $name (unicode)
     *          The new value for instance's name.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function modifyInstanceAttributes($instanceId, $name, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($name)) {
            throw new \InvalidArgumentException(
                'request $name should not be empty.'
            );
        }
        $body = array();
        $body['name'] = $name;
        $params = array();
        $params['modifyAttribute'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Rebuilding the instance owned by the user.
     * After rebuilding the instance,
     * all of snapshots created from original instance system disk will be deleted,
     * all of customized images will be saved for using in the future.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param string $imageId (unicode)
     *          The id of the image which is used to rebuild the instance.
     *
     * @param string $adminPass (unicode)
     *          The admin password to login the instance.
     *          The admin password will be encrypted in AES-128 algorithm
     *          with the substring of the former 16 characters of user SecretKey.
     *          See more detail on
     *              https://bce.baidu.com/doc/BCC/API.html#.7A.E6.31.D8.94.C1.A1.C2.1A.8D.92.ED.7F.60.7D.AF
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function rebuildInstance($instanceId, $imageId, $adminPass, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($imageId)) {
            throw new \InvalidArgumentException(
                'request $imageId should not be empty.'
            );
        }
        if (empty($adminPass)) {
            throw new \InvalidArgumentException(
                'request $adminPass should not be empty.'
            );
        }
        $body = array();
        $credentials = $this->config[BceClientConfigOptions::CREDENTIALS];
        $secretAccessKey = null;
        if(isset($credentials['sk'])){
            $secretAccessKey = $credentials['sk'];
        }
        if(isset($credentials['secretAccessKey'])){
            $secretAccessKey = $credentials['secretAccessKey'];
        }
        $cipherAdminPass = $this->aes128WithFirst16Char($adminPass, $secretAccessKey);
        $body['imageId'] = $imageId;
        $body['adminPass'] = $cipherAdminPass;

        $params = array();
        $params['rebuild'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Releasing the instance owned by the user.
     * Only the Postpaid instance or Prepaid which is expired can be released.
     * After releasing the instance,
     * all of the data will be deleted.
     * all of volumes attached will be auto detached, but the volume snapshots will be saved.
     * all of snapshots created from original instance system disk will be deleted,
     * all of customized images created from original instance system disk will be reserved.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function releaseInstance($instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Resizing the instance owned by the user.
     * The Prepaid instance can not be downgrade.
     * Only the Running/Stopped instance can be resized, otherwise, it's will get 409 errorCode.
     * After resizing the instance,it will be reboot once.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          The id of instance.
     *
     * @param int $cpuCount
     *          The parameter of specified the cpu core to resize the instance.
     *
     * @param int $memoryCapacityInGB
     *          The parameter of specified the capacity of memory in GB to resize the instance.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function resizeInstance($instanceId, $cpuCount, $memoryCapacityInGB, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($cpuCount)) {
            throw new \InvalidArgumentException(
                'request $cpuCount should be positive.'
            );
        }
        if (empty($memoryCapacityInGB)) {
            throw new \InvalidArgumentException(
                'request $memoryCapacityInGB should be positive.'
            );
        }
        $body = array();
        $body['cpuCount'] = $cpuCount;
        $body['memoryCapacityInGB'] = $memoryCapacityInGB;
        $params = array();
        if (empty($clientToken)) {
            $params['resize'] = null;
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['resize'] = null;
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Binding the instance to specified securitygroup.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance.
     *
     * @param string $securityGroupId (unicode)
     *          The id of the securitygroup.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function bindInstanceToSecurityGroup($instanceId, $securityGroupId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($securityGroupId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupId should not be empty.'
            );
        }
        $body = array();
        $body['securityGroupId'] = $securityGroupId;
        $params = array();
        $params['bind'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Unbinding the instance from securitygroup.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance.
     *
     * @param string $securityGroupId (unicode)
     *          The id of the securitygroup.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function unbindInstanceFromSecurityGroup($instanceId, $securityGroupId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($securityGroupId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupId should not be empty.'
            );
        }
        $body = array();
        $body['securityGroupId'] = $securityGroupId;
        $params = array();
        $params['unbind'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * Getting the vnc url to access the instance.
     * The vnc url can be used once.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function getInstanceVnc($instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/instance/' . $instanceId . '/vnc'
        );
    }

    /**
     * PurchaseReserved the instance with fixed duration.
     * You can not purchaseReserved the instance which is resizing.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getInstance.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance.
     *
     * @param Billing $billing
     *          Billing information.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function purchaseReservedInstance($instanceId, $billing=null, $clientToken=null, $options = array()) {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        if (empty($billing)) {
            $billing = $this->generateDefaultBillingWithReservation();
        }
        $body = array();
        $body['billing'] = $this->objectToArray($billing);
        $params = array();
        if (empty($clientToken)) {
            $params['purchaseReserved'] = null;
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['purchaseReserved'] = null;
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/instance/' . $instanceId
        );
    }

    /**
     * The interface will be deprecated in the future,
     * we suggest to use triad (instanceType，cpuCount，memoryCapacityInGB) to specified the instance configuration.
     * Listing all of specification for instance resource to buy.
     * See more detail on
     * https://bce.baidu.com/doc/BCC/API.html#.E5.AE.9E.E4.BE.8B.E5.A5.97.E9.A4.90.E8.A7.84.E6.A0.BC
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listInstanceSpecs($options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/instance/spec'
        );
    }

    /**
     * Create a volume with the specified options.
     * You can use this method to create a new empty volume by specified options
     * or you can create a new volume from customized volume snapshot but not system disk snapshot.
     * By using the cdsSizeInGB parameter you can create a newly empty volume.
     * By using snapshotId parameter to create a volume form specific snapshot.
     *
     * @param int $cdsSizeInGB
     *          The size of volume to create in GB.
     *          By specifying the snapshotId,
     *          it will create volume from the specified snapshot and the parameter cdsSizeInGB will be ignored.
     *
     * @param Billing $billing
     *          Billing information.
     *
     * @param int $purchaseCount
     *          The optional parameter to specify how many volumes to buy, default value is 1.
     *          The maximum to create for one time is 5.
     *
     * @param string $storageType menu{'hp1', 'std1'}
     *          The storage type of volume, see more detail in
     *          https://bce.baidu.com/doc/BCC/API.html#StorageType
     *
     * @param string $zoneName
     *          The optional parameter to specify the available zone for the volume.
     *          See more detail through BccClient.listZones method
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createVolumeWithCdsSize($cdsSizeInGB, $billing=null, $purchaseCount=1, $storageType='hp1',
                                                $zoneName=null, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($cdsSizeInGB)) {
            throw new \InvalidArgumentException(
                'request $cdsSizeInGB should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        if (empty($billing)) {
            $billing = $this->generateDefaultBilling();
        }
        $body = array();
        $body['cdsSizeInGB'] = $cdsSizeInGB;
        $body['billing'] = $this->objectToArray($billing);
        if ($purchaseCount !== null) {
            $body['purchaseCount'] = $purchaseCount;
        }
        if ($storageType !== null) {
            $body['storageType'] = $storageType;
        }
        if ($zoneName !== null) {
            $body['zoneName'] = $zoneName;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume'
        );
    }

    /**
     * Create a volume with the specified options.
     * You can use this method to create a new empty volume by specified options
     * or you can create a new volume from customized volume snapshot but not system disk snapshot.
     * By using the cdsSizeInGB parameter you can create a newly empty volume.
     * By using snapshotId parameter to create a volume form specific snapshot.
     *
     * @param string $snapshotId (unicode)
     *          The id of snapshot.
     *          By specifying the snapshotId,
     *          it will create volume from the specified snapshot and the parameter cdsSizeInGB will be ignored.
     *
     * @param Billing $billing
     *          Billing information.
     *
     * @param int $purchaseCount
     *          The optional parameter to specify how many volumes to buy, default value is 1.
     *          The maximum to create for one time is 5.
     *
     * @param string $storageType menu{'hp1', 'std1'}
     *          The storage type of volume, see more detail in
     *              https://bce.baidu.com/doc/BCC/API.html#StorageType
     *
     * @param string $zoneName
     *          The optional parameter to specify the available zone for the volume.
     *          See more detail through BccClient.listZones method
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createVolumeWithSnapshotId($snapshotId, $billing=null, $purchaseCount=1, $storageType='hp1',
                                                $zoneName=null, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($snapshotId)) {
            throw new \InvalidArgumentException(
                'request $snapshotId should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        if (empty($billing)) {
            $billing = $this->generateDefaultBilling();
        }
        $body = array();
        $body['snapshotId'] = $snapshotId;
        $body['billing'] = $this->objectToArray($billing);
        if ($purchaseCount !== null) {
            $body['purchaseCount'] = $purchaseCount;
        }
        if ($storageType !== null) {
            $body['storageType'] = $storageType;
        }
        if ($zoneName !== null) {
            $body['zoneName'] = $zoneName;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume'
        );
    }

    /**
     * Listing volumes owned by the authenticated user.
     *
     * @param string $instanceId
     *          The id of instance. The optional parameter to list the volume.
     *          If it's specified,only the volumes attached to the specified instance will be listed.
     *
     * @param string $zoneName
     *          The name of available zone. The optional parameter to list volumes
     *
     * @param string $marker
     *          The optional parameter marker specified in the original request to specify
     *          where in the results to begin listing.
     *          Together with the marker, specifies the list result which listing should begin.
     *          If the marker is not specified, the list result will listing from the first one.
     *
     * @param int $maxKeys
     *          The optional parameter to specifies the max number of list result to return.
     *          The default value is 1000.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listVolumes($instanceId=null, $zoneName=null, $marker=null, $maxKeys=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if ($instanceId !== null) {
            $params['instanceId'] = $instanceId;
        }
        if ($zoneName !== null) {
            $params['zoneName'] = $zoneName;
        }
        if ($marker !== null) {
            $params['marker'] = $marker;
        }
        if ($maxKeys !== null) {
            $params['maxKeys'] = $maxKeys;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/volume'
        );
    }

    /**
     * Get the detail information of specified volume.
     *
     * @param string $volumeId (unicode)
     *          The id of the volume.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function getVolume($volumeId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Attaching the specified volume to a specified instance.
     * You can attach the specified volume to a specified instance only
     * when the volume is Available and the instance is Running or Stopped,
     * otherwise, it's will get 409 errorCode.
     *
     * @param string $volumeId (unicode)
     *          The id of the volume which will be attached to specified instance.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance which will be attached with a volume.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function attachVolume($volumeId, $instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        $body = array();
        $body['instanceId'] = $instanceId;
        $params = array();
        $params['attach'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Detaching the specified volume from a specified instance.
     * You can detach the specified volume from a specified instance only
     * when the instance is Running or Stopped ,
     * otherwise, it's will get 409 errorCode.
     *
     * @param string $volumeId (unicode)
     *          The id of the volume which will be detached to specified instance.
     *
     * @param string $instanceId (unicode)
     *          The id of the instance which will be detached with a volume.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function detachVolume($volumeId, $instanceId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        $body = array();
        $body['instanceId'] = $instanceId;
        $params = array();
        $params['detach'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Releasing the specified volume owned by the user.
     * You can release the specified volume only
     * when the instance is among state of  Available/Expired/Error,
     * otherwise, it's will get 409 errorCode.
     *
     * @param string $volumeId (unicode)
     *          The id of the volume which will be released.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function releaseVolume($volumeId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Resizing the specified volume with newly size.
     * You can resize the specified volume only when the volume is Available,
     * otherwise, it's will get 409 errorCode.
     * The prepaid volume can not be downgrade.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getVolume.
     *
     * @param string $volumeId (unicode)
     *          The id of volume which you want to resize.
     *
     * @param int $newCdsSizeInGB
     *          The new volume size you want to resize in GB.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function resizeVolume($volumeId, $newCdsSizeInGB, $clientToken, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($newCdsSizeInGB)) {
            throw new \InvalidArgumentException(
                'request $newCdsSizeInGB should not be empty.'
            );
        }

        $body = array();
        $body['newCdsSizeInGB'] = $newCdsSizeInGB;
        $params = array();
        if (empty($clientToken)) {
            $params['resize'] = null;
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['resize'] = null;
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Rollback the volume with the specified volume snapshot.
     * You can rollback the specified volume only when the volume is Available,
     * otherwise, it's will get 409 errorCode.
     * The snapshot used to rollback must be created by the volume,
     * otherwise,it's will get 404 errorCode.
     * If rolling back the system volume,the instance must be Running or Stopped,
     * otherwise, it's will get 409 errorCode.After rolling back the
     * volume,all the system disk data will erase.
     *
     * @param string $volumeId (unicode)
     *          The id of volume which will be rollback.
     *
     * @param string $snapshotId (unicode)
     *          The id of snapshot which will be used to rollback the volume.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function rollbackVolume($volumeId, $snapshotId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($snapshotId)) {
            throw new \InvalidArgumentException(
                'request $snapshotId should not be empty.'
            );
        }
        $body = array();
        $body['snapshotId'] = $snapshotId;
        $params = array();
        $params['rollback'] = null;

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * PurchaseReserved the instance with fixed duration.
     * You can not purchaseReserved the instance which is resizing.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getVolume.
     *
     * @param string $volumeId (unicode)
     *          The id of volume which will be renew.
     *
     * @param Billing $billing
     *          Billing information.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function purchaseReservedVolume($volumeId, $billing=null, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($billing)) {
            $billing = $this->generateDefaultBillingWithReservation();
        }
        $body = array();
        $body['billing'] = $this->objectToArray($billing);
        $params = array();
        if (empty($clientToken)) {
            $params['purchaseReserved'] = null;
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['purchaseReserved'] = null;
            $params['clientToken'] = $clientToken;
        }

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/volume/' . $volumeId
        );
    }

    /**
     * Creating a customized image which can be used for creating instance.
     * You can create an image from an instance with this method.
     * While creating an image from an instance, the instance must be Running or Stopped,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getImage.
     *
     * @param string $imageName (unicode)
     *          The name for the image that will be created.
     *          The name length from 1 to 65,only contains letters,digital and underline.
     *
     * @param string $instanceId (unicode)
     *          The optional parameter specify the id of the instance which will be used to create the new image.
     *          When instanceId and snapshotId are specified ,only instanceId will be used.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createImageFromInstanceId($imageName, $instanceId, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($imageName)) {
            throw new \InvalidArgumentException(
                'request $imageName should not be empty.'
            );
        }
        if (empty($instanceId)) {
            throw new \InvalidArgumentException(
                'request $instanceId should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['imageName'] = $imageName;
        $body['instanceId'] = $instanceId;

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/image'
        );
    }

    /**
     * Creating a customized image which can be used for creating instance.
     * You can create an image from an snapshot with tihs method.
     * You can create the image only from system snapshot.
     * While creating an image from a system snapshot,the snapshot must be Available,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getImage.
     *
     * @param string $imageName (unicode)
     *          The name for the image that will be created.
     *          The name length from 1 to 65,only contains letters,digital and underline.
     *
     * @param string $snapshotId (unicode)
     *          The optional parameter specify the id of the snapshot which will be used to create the new image.
     *          When instanceId and snapshotId are specified ,only instanceId will be used.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createImageFromSnapshotId($imageName, $snapshotId, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($imageName)) {
            throw new \InvalidArgumentException(
                'request $imageName should not be empty.'
            );
        }
        if (empty($snapshotId)) {
            throw new \InvalidArgumentException(
                'request $snapshotId should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['imageName'] = $imageName;
        $body['snapshotId'] = $snapshotId;

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/image'
        );
    }

    /**
     * Listing images owned by the authenticated user.
     *
     * @param string $imageType menu{'All', 'System', 'Custom', 'Integration'}
     *          The optional parameter to filter image to list.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#ImageType"
     *
     * @param string $marker
     *          The optional parameter marker specified in the original request to specify
     *          where in the results to begin listing.
     *          Together with the marker, specifies the list result which listing should begin.
     *          If the marker is not specified, the list result will listing from the first one.
     *
     * @param int $maxKeys
     *          The optional parameter to specifies the max number of list result to return.
     *          The default value is 1000.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listImages($imageType='All', $marker=null, $maxKeys=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        $params['imageType'] = $imageType;
        if ($marker !== null) {
            $params['marker'] = $marker;
        }
        if ($maxKeys !== null) {
            $params['maxKeys'] = $maxKeys;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/image'
        );
    }

    /**
     * Get the detail information of specified image.
     *
     * @param string $imageId (unicode)
     *          The id of image.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function getImage($imageId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($imageId)) {
            throw new \InvalidArgumentException(
                'request $imageId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/image/' . $imageId
        );
    }

    /**
     * Deleting the specified image.
     * Only the customized image can be deleted,
     * otherwise, it's will get 403 errorCode.
     *
     * @param string $imageId (unicode)
     *          The id of image.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function deleteImage($imageId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($imageId)) {
            throw new \InvalidArgumentException(
                'request $imageId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/image/' . $imageId
        );
    }

    /**
     * Creating snapshot from specified volume.
     * You can create snapshot from system volume and CDS volume.
     * While creating snapshot from system volume,the instance must be Running or Stopped,
     * otherwise, it's will get 409 errorCode.
     * While creating snapshot from CDS volume, the volume must be InUs or Available,
     * otherwise, it's will get 409 errorCode.
     * This is an asynchronous interface,
     * you can get the latest status by BccClient.getSnapshot.
     *
     * @param string $volumeId (unicode)
     *          The id which specify where the snapshot will be created from.
     *          If you want to create an snapshot from a customized volume, a id of the volume will be set.
     *          If you want to create an snapshot from a system volume, a id of the instance will be set.
     *
     * @param string $snapshotName (unicode)
     *          The name for the snapshot that will be created.
     *          The name length from 1 to 65,only contains letters,digital and underline.
     *
     * @param string $desc
     *          The optional parameter to describe the information of the new snapshot.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createSnapshot($volumeId, $snapshotName, $desc=null, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($volumeId)) {
            throw new \InvalidArgumentException(
                'request $volumeId should not be empty.'
            );
        }
        if (empty($snapshotName)) {
            throw new \InvalidArgumentException(
                'request $snapshotName should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['volumeId'] = $volumeId;
        $body['snapshotName'] = $snapshotName;
        if ($desc !== null) {
            $body['desc'] = $desc;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/snapshot'
        );
    }

    /**
     * List snapshots
     *
     * @param string $marker
     *          The optional parameter marker specified in the original request to specify
     *          where in the results to begin listing.
     *          Together with the marker, specifies the list result which listing should begin.
     *          If the marker is not specified, the list result will listing from the first one.
     *
     * @param int $maxKeys
     *          The optional parameter to specifies the max number of list result to return.
     *          The default value is 1000.
     *
     * @param string $volumeId
     *          The id of the volume.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listSnapshots($marker=null, $maxKeys=null, $volumeId=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if ($marker !== null || $maxKeys !== null || $volumeId !== null) {
            $params = array();
        }
        if ($marker !== null) {
            $params['marker'] = $marker;
        }
        if ($maxKeys !== null) {
            $params['maxKeys'] = $maxKeys;
        }
        if ($volumeId !== null) {
            $params['volumeId'] = $volumeId;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/snapshot'
        );
    }

    /**
     * Get the detail information of specified snapshot.
     *
     * @param string $snapshotId (unicode)
     *          The id of snapshot.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function getSnapshot($snapshotId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($snapshotId)) {
            throw new \InvalidArgumentException(
                'request $snapshotId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/snapshot/' . $snapshotId
        );
    }

    /**
     * Deleting the specified snapshot.
     * Only when the snapshot is CreatedFailed or Available,the specified snapshot can be deleted.
     * otherwise, it's will get 403 errorCode.
     *
     * @param string $snapshotId (unicode)
     *          The id of snapshot.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function deleteSnapshot($snapshotId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($snapshotId)) {
            throw new \InvalidArgumentException(
                'request $snapshotId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/snapshot/' . $snapshotId
        );
    }

    /**
     * Creating a newly SecurityGroup with specified rules.
     *
     * @param string $name (unicode)
     *          The name of SecurityGroup that will be created.
     *
     * @param array $rules (array[SecurityGroupRuleModel])
     *          The array of rules which define how the SecurityGroup works.
     *
     * @param string $vpcId
     *          The optional parameter to specify the id of VPC to SecurityGroup
     *
     * @param string $desc
     *          The optional parameter to describe the SecurityGroup that will be created.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function createSecurityGroup($name, $rules, $vpcId=null, $desc=null, $clientToken=null,
                                        $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($name)) {
            throw new \InvalidArgumentException(
                'request $name should not be empty.'
            );
        }
        if (empty($rules)) {
            throw new \InvalidArgumentException(
                'request $rules should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['name'] = $name;
        $body['rules'] = $this->objectToArray($rules);
        if ($vpcId != null) {
            $body['vpcId'] = $vpcId;
        }
        if ($desc !== null) {
            $body['desc'] = $desc;
        }

        return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/securityGroup'
        );
    }

    /**
     * Listing SecurityGroup owned by the authenticated user.
     *
     * @param string $instanceId
     *          The id of instance. The optional parameter to list the SecurityGroup.
     *          If it's specified,only the SecurityGroup related to the specified instance will be listed
     *
     * @param string $vpcId
     *          filter by vpcId, optional parameter.
     *
     * @param string $marker
     *          The optional parameter marker specified in the original request to specify
     *          where in the results to begin listing.
     *          Together with the marker, specifies the list result which listing should begin.
     *          If the marker is not specified, the list result will listing from the first one.
     *
     * @param int $maxKeys
     *          The optional parameter to specifies the max number of list result to return.
     *          The default value is 1000.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listSecurityGroups($instanceId=null, $vpcId=null, $marker=null, $maxKeys=null,
                                       $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        $params = array();
        if ($instanceId !== null || $marker !== null || $maxKeys !== null) {
            $params = array();
        }
        if ($instanceId !== null) {
            $params['instanceId'] = $instanceId;
        }
        if ($vpcId != null) {
            $params['vpcId'] = $vpcId;
        }
        if ($marker !== null) {
            $params['marker'] = $marker;
        }
        if ($maxKeys !== null) {
            $params['maxKeys'] = $maxKeys;
        }

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
                'params' => $params,
            ),
            '/securityGroup'
        );
    }

    /**
     * Deleting the specified SecurityGroup.
     *
     * @param string $securityGroupId (unicode)
     *          The id of SecurityGroup that will be deleted.
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function deleteSecurityGroup($securityGroupId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($securityGroupId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupId should not be empty.'
            );
        }

        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/securityGroup/' . $securityGroupId
        );
    }

    /**
     * authorize a security group rule to the specified security group
     *
     * @param string $securityGroupId (unicode)
     *          The id of SecurityGroup that will be authorized.
     *
     * @param SecurityGroupRuleModel $rule
     *          security group rule detail.
     *          Through protocol/portRange/direction/sourceIp/sourceGroupId, we can confirmed only one rule.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function authorizeSecurityGroupRule($securityGroupId, $rule, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($securityGroupId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupId should not be empty.'
            );
        }
        if (empty($rule)) {
            throw new \InvalidArgumentException(
                'request $rule should not be empty.'
            );
        }

        $params = array();
        $params['authorizeRule'] = null;
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['rule'] = $this->objectToArray($rule);

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/securityGroup/' . $securityGroupId
        );
    }

    /**
     * revoke a security group rule from the specified security group
     *
     * @param string $securityGroupId (unicode)
     *          The id of SecurityGroup that will be revoked.
     *
     * @param SecurityGroupRuleModel $rule
     *          security group rule detail.
     *          Through protocol/portRange/direction/sourceIp/sourceGroupId, we can confirmed only one rule.
     *
     * @param string $clientToken
     *          An ASCII string whose length is less than 64.
     *          The request will be idempotent if client token is provided.
     *          If the clientToken is not specified by the user,
     *          a random String generated by default algorithm will be used.
     *          See more detail at
     *              https://bce.baidu.com/doc/BCC/API.html#.E5.B9.82.E7.AD.89.E6.80.A7
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function revokeSecurityGroupRule($securityGroupId, $rule, $clientToken=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($securityGroupId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupId should not be empty.'
            );
        }
        if (empty($rule)) {
            throw new \InvalidArgumentException(
                'request $rule should not be empty.'
            );
        }
        $params = array();
        if (empty($clientToken)) {
            $params['clientToken'] = $this->generateClientToken();
        } else {
            $params['clientToken'] = $clientToken;
        }
        $body = array();
        $body['rule'] = $this->objectToArray($rule);

        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'params' => $params,
                'body' => json_encode($body),
            ),
            '/securityGroup/' . $securityGroupId
        );
    }

    /**
     * Get zone detail list within current region
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function listZones($options = array())
    {
        list($config) = $this->parseOptions($options, 'config');

        return $this->sendRequest(
            HttpMethod::GET,
            array(
                'config' => $config,
            ),
            '/zone'
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
     * The encryption implement for AES-128 algorithm for BCE password encryption.
     * Only the first 16 bytes of $secretAccessKey will be used to encrypt the $adminPass.
     *
     * See more detail on
     * <a href = "https://bce.baidu.com/doc/BCC/API.html#.7A.E6.31.D8.94.C1.A1.C2.1A.8D.92.ED.7F.60.7D.AF">
     *     BCE API doc</a>
     *
     * @param String $adminPass
     *          The content String to encrypt.
     *
     * @param String $secretAccessKey
     *          The security key to encrypt.
     *          Only the first 16 bytes of privateKey will be used to encrypt the content.
     *
     * @return string
     *          The encrypted string of the original content with AES-128 algorithm.
     */
    private function aes128WithFirst16Char($adminPass, $secretAccessKey)
    {
        $adminPass = $this->pkcs5Pad($adminPass);
        $secretAccessKey = substr($secretAccessKey, 0, 16);
        $crypted = openssl_encrypt($adminPass, 'AES-128-ECB', $secretAccessKey, OPENSSL_RAW_DATA);
        return bin2hex(substr($crypted, 0, 16));
    }

    /**
     * This is a filling algorithm, the purpose is to ensure that the content length reached 16
     *
     * @param String $adminPass
     *          The content String to filling.
     *
     * @return string
     *          ensure that the content length reached 16
     */
    private function pkcs5Pad($adminPass)
    {
        $pad = 16 - (strlen($adminPass) % 16);
        return $adminPass . str_repeat(chr($pad), $pad);
    }

    /**
     * The method to generate a default Billing which is Postpaid.
     *
     * @return Billing object with Postpaid PaymentTiming.
     */
    private function generateDefaultBilling() {
        return new Billing('Postpaid');
    }

    /**
     * The method to generate a default Billing with default Reservation which default ReservationLength is 1.
     *
     * @return Billing object with default Reservation which default ReservationLength is 1
     */
    private function generateDefaultBillingWithReservation() {
        return new Billing();
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

    /**
     * Delete the specified security group rule via the rule id
     *
     * @param string $securityGroupRuleId
     *          The id of security group rule to be deleted
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function deleteSecurityGroupRule($securityGroupRuleId, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($securityGroupRuleId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupRuleId should not be empty.'
            );
        }
        return $this->sendRequest(
            HttpMethod::DELETE,
            array(
                'config' => $config,
            ),
            '/securityGroup' . '/rule/' . $securityGroupRuleId
        );
    }

    /**
     * Update the specified security group rule via params shown below
     *
     * @param string $securityGroupRuleId
     *          The id of security group rule to be updated
     *
     * @param string $remark
     *          The remark of the security group rule to be updated. The optional parameter
     *
     * @param string $portRange
     *          The port range of the security group rule to be updated. The optional parameter
     *
     * @param string $sourceIp
     *          The source ip of the security group rule to be updated. The optional parameter
     *
     * @param string $sourceGroupId
     *          The id of the source security group. The optional parameter
     *
     * @param string $destIp
     *          The destination ip address. The optional parameter
     *
     * @param string $destGroupId
     *          The id of the destination security group. The optional parameter
     *
     * @param string $protocol
     *          The protocol type. The optional parameter
     *
     * @param array $options
     *          The optional bce configuration, which will overwrite the
     *          default configuration that was passed while creating BccClient instance.
     *
     * @return mixed
     */
    public function updateSecurityGroupRule($securityGroupRuleId, $remark=null, $portRange=null,
                                            $sourceIp=null, $sourceGroupId=null, $destIp=null,
                                            $destGroupId=null, $protocol=null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
        if (empty($securityGroupRuleId)) {
            throw new \InvalidArgumentException(
                'request $securityGroupRuleId should not be empty.'
            );
        }
        $body = array();
        $body['securityGroupRuleId'] = $securityGroupRuleId;
        if ($remark != null) {
            $body['remark'] = $remark;
        }
        if ($portRange !== null) {
            $body['portRange'] = $portRange;
        }
        if ($sourceIp != null) {
            $body['sourceIp'] = $sourceIp;
        }
        if ($sourceGroupId !== null) {
            $body['sourceGroupId'] = $sourceGroupId;
        }
        if ($destIp != null) {
            $body['destIp'] = $destIp;
        }
        if ($destGroupId !== null) {
            $body['destGroupId'] = $destGroupId;
        }
        if ($protocol !== null) {
            $body['protocol'] = $protocol;
        }
        return $this->sendRequest(
            HttpMethod::PUT,
            array(
                'config' => $config,
                'body' => json_encode($body),
            ),
            '/securityGroup' . '/rule' . '/update'
        );
    }
}
