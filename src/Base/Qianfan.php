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
use Tuoluojiang\Baidubce\Base\Util\HttpUtils;
use Tuoluojiang\Baidubce\Exception\BaiduBceException;
use Tuoluojiang\Baidubce\Util\BceV1Signer;

class Qianfan
{
    //创建数据集
    protected const CREATE_PATH = '/wenxinworkshop/dataset/create';

    //获取数据集详情
    protected const INFO_PATH = '/wenxinworkshop/dataset/statusList';

    //删除数据集
    protected const DELETE_PATH = '/wenxinworkshop/dataset/delete';

    //发起数据集发布任务
    protected const RELEASE_PATH = '/wenxinworkshop/dataset/release';

    //发起数据集导入任务
    protected const IMPORT_PATH = '/wenxinworkshop/dataset/import';

    //获取数据集状态详情
    protected const STAUS_PATH = '/wenxinworkshop/dataset/info';

    //发起数据集导出任务
    protected const EXPORT_PATH = '/wenxinworkshop/dataset/export';

    //获取数据集导出记录
    protected const EXPORT_RECORD_PATH = '/wenxinworkshop/dataset/exportRecord';

    //获取数据集导入错误详情
    protected const IMPORT_ERROR_PATH = '/wenxinworkshop/dataset/importErrorDetail';

    //获取模型版本详情
    protected const MODEL_VERSION_PATH = '/wenxinworkshop/modelrepo/modelVersionDetail';

    //获取模型详情
    protected const MODEL_INFO_PATH = '/wenxinworkshop/modelrepo/modelDetail';

    //训练任务发布为模型
    protected const MODEL_PUBLISH_PATH = '/wenxinworkshop/modelrepo/publishTrainModel';

    //获取预置模型列表
    protected const MODEL_PRESET_PATH = '/wenxinworkshop/modelrepo/model/preset/list';

    //获取用户模型列表
    protected const MODEL_USER_PATH = '/wenxinworkshop/modelrepo/model/user/list';

    //批量删除模型
    protected const MODEL_BATCH_DELETE_PATH = '/wenxinworkshop/modelrepo/model/batchDelete';

    //批量删除模型版本
    protected const MODEL_VERSION_BATCH_DELETE_PATH = '/wenxinworkshop/modelrepo/model/version/batchDelete';

    //创建Prompt模版
    protected const PROMPT_CREATE_PATH = '/wenxinworkshop/prompt/template/create';

    //更新Prompt模版
    protected const PROMPT_UPDATE_PATH = '/wenxinworkshop/prompt/template/update';

    //删除Prompt模版
    protected const PROMPT_DELETE_PATH = '/wenxinworkshop/prompt/template/delete';

    //获取prompt模版列表
    protected const PROMPT_LIST_PATH = '/wenxinworkshop/prompt/template/list';

    //获取标签列表
    protected const PROMPT_LABEL_LIST_PATH = '/wenxinworkshop/prompt/label/list';

    //获取Prompt模版详情
    protected const PROMPT_INFO_PATH = '/wenxinworkshop/prompt/template/info';

    //创建prompt优化任务
    protected const PROMPT_TASK_CREATE_PATH = '/wenxinworkshop/prompt/singleOptimize/create';

    //获取prompt优化任务的详情
    protected const PROMPT_TASK_INFO_PATH = '/wenxinworkshop/prompt/singleOptimize/info';

    //Prompt评估打分
    protected const PROMPT_PREDICT_PATH = '/wenxinworkshop/prompt/evaluate/predict';

    //Prompt评估总结
    protected const PROMPT_SUMMARY_PATH = '/wenxinworkshop/prompt/evaluate/summary';

    protected string $baseUrl = 'https://qianfan.baidubce.com';

    protected array $configs;

    private bool    $verify = false;

    private Client  $client;

    public function __construct(protected $accessKey, protected $secretKey)
    {
        $this->client  = new Client(['verify' => $this->verify, 'timeout' => 10]);
        $this->configs = [
            'credentials' => [
                'ak' => $this->accessKey,
                'sk' => $this->secretKey,
            ],
            'endpoint' => $this->baseUrl,
        ];
    }

    /**
     * 发送请求.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    protected function request(string $path, string $body = '', array $params = [], string $method = 'POST', array $headers = [])
    {
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
        $request = new Request($method, $url, $headers, $body);
        try {
            $response = $this->client->send($request);
            $response = json_decode($response->getBody()->getContents(), true);
            if (! $response) {
                throw new BaiduBceException('无响应', 500);
            }
            if (isset($response['error_code'])) {
                throw new BaiduBceException($response['error_msg'], $response['error_code']);
            }
            return $response;
        } catch (\Exception $e) {
            throw new BaiduBceException($e->getMessage());
        }
    }
}
