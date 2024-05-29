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
namespace Tuoluojiang\Baidubce\Application;

use BaiduBce\Exception\BceClientException;
use BaiduBce\Exception\BceServiceException;
use Tuoluojiang\baidubce\Base\Qianfan;

/**
 * 数据集.
 */
class Dataset extends Qianfan
{
    public function __construct(string $accessKey, string $secretKey)
    {
        parent::__construct($accessKey, $secretKey);
    }

    /**
     * 创建数据集.
     * @param string $name 数据集名称，当创建新数据集时必传非空，示例：数据集名称
     * @param int $projectType 标注类型，可选值如下：· 20：表示文本对话· 401：表示泛文本无标注· 402：表示query问题集· 705：表示文生图
     * @param int $templateType 标注模板类型，可选值如下：· 2000：表示非排序文本对话· 2001：表示含排序文本对话· 40100：表示泛文本无标注· 40200：表示query问题集· 70500：表示文生图
     * @param int $dataType 数据类型，可选值如下：· 4：表示文本· 7：表示跨模态
     * @param int $storageType 数据集存储类型，示例：sysBos，可选值如下：· 用户bos，固定值usrBos· 公共bos，固定值sysBos
     * @param string $storageId 使用用户bos时需要填写使用的bucket
     * @param string $rawStoragePath 使用用户bos时需要填写使用的目录，格式为前后加斜杠，示例：“/yourDir/”
     * @throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function createDataset(string $name, int $projectType, int $templateType, int $dataType, int $storageType, string $storageId = '', string $rawStoragePath = '')
    {
        $body = json_encode(compact('projectType', 'templateType', 'dataType', 'storageType', 'storageId', 'rawStoragePath'));
        return $this->request(self::CREATE_PATH, $body);
    }

    /**
     * 获取数据集详情.
     * @param string $datasetIds 数据集版本ID，说明：（1）多个数据集版本ID时，用英文逗号,隔开，示例：'1,2,3,4'（2）可以通过以下任一方式获取值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     */
    public function getDatasetInfo(string $datasetIds)
    {
        $body = json_encode(compact('datasetIds'));
        return $this->request(self::INFO_PATH, $body);
    }

    /**
     * 删除数据集.
     * @param string $datasetIds 数据集版本ID，说明：（1）多个数据集版本ID时，用英文逗号,隔开，示例：'1,2,3,4'（2）可以通过以下任一方式获取值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     */
    public function deleteDataset(string $datasetIds)
    {
        $body = json_encode(compact('datasetIds'));
        return $this->request(self::DELETE_PATH, $body);
    }

    /**
     * 发起数据集发布任务.
     * @param string $datasetIds 数据集版本ID，说明：（1）多个数据集版本ID时，用英文逗号,隔开，示例：'1,2,3,4'（2）可以通过以下任一方式获取值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     */
    public function pushDatasetTask(string $datasetIds)
    {
        $body = json_encode(compact('datasetIds'));
        return $this->request(self::RELEASE_PATH, $body);
    }

    /**
     * 发起数据集导入任务.
     * @param int $datasetId 要导入的数据集版本ID，示例：1，可以通过以下任一方式获取该字段值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     * @param bool $annotated 是否带标注导入，可选值如下：· true：表示带标注信息· false：表示不带
     * @param int $importFrom 上传方式，可选值如下：· 1：用户Bos目录/文件上传，默认1· 2：网络分享链接,将全部文件保存至同一压缩包，压缩包仅支持zip/tar.gz格式，压缩前源文件大小限制5G以内；仅支持来自百度BOS、阿里OSS、腾讯COS、华为OBS的共享链接
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function importDatasetTask(int $datasetId, bool $annotated, int $importFrom)
    {
        $body = json_encode(compact('datasetId', 'annotated', 'importFrom'));
        return $this->request(self::IMPORT_PATH, $body);
    }

    /**
     * 获取数据集状态详情.
     * @param int $datasetId 要导入的数据集版本ID，示例：1，可以通过以下任一方式获取该字段值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function getDatasetStatus(int $datasetId)
    {
        $body = json_encode(compact('datasetId'));
        return $this->request(self::STAUS_PATH, $body);
    }

    /**
     * 发起数据集导出任务.
     * @param int $datasetId 数据集版本ID，可以通过以下任一方式获取该字段值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     * @param int $exportFormat 导出格式，固定值为0，表示平台默认格式
     * @param string $exportTo 导出到的存储，可选值如下：· 0：平台存储· 1：用户Bos
     * @param int $exportType 导出数据类型，可选值如下：· 1：导出全部数据，包含源文件及已有的标注文件· 2：仅导出源文件
     * @param string $storageId 导出到用户Bos时需要填写导入到的bucket，示例：yourBucketName
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function exportDatasetTask(int $datasetId, int $exportFormat, string $exportTo, int $exportType, string $storageId = '')
    {
        $body = json_encode(compact('datasetId', 'exportFormat', 'exportTo', 'exportType', 'storageId'));
        return $this->request(self::EXPORT_PATH, $body);
    }

    /**
     * 获取数据集导出记录.
     * @param int $datasetId 数据集版本ID，可以通过以下任一方式获取该字段值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function exportDatasetRecord(int $datasetId)
    {
        $body = json_encode(compact('datasetId'));
        return $this->request(self::EXPORT_RECORD_PATH, $body);
    }

    /**
     * 获取数据集导入错误详情.
     * @param int $datasetId 数据集版本ID，可以通过以下任一方式获取该字段值：· 方式一，通过调用创建数据集接口，返回的id字段获取· 方式二，在千帆控制台-数据集管理列表页面查看
     * @param int $errCode 错误码，调用获取数据集状态详情接口时，如果有错误会返回导入错误信息importErrorInfo字段；
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function importErrorDetail(int $datasetId, int $errCode)
    {
        $body = json_encode(compact('datasetId', 'errCode'));
        return $this->request(self::IMPORT_ERROR_PATH, $body);
    }
}
