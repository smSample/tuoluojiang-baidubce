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
 * 模型.
 */
class Models extends Qianfan
{
    public function __construct(string $accessKey, string $secretKey)
    {
        parent::__construct($accessKey, $secretKey);
    }

    /**
     * 获取模型版本详情.
     * @param int $modelVersionId 模型版本id，通过页面url获取该字段值：在控制台-模型仓库列表-点击某模型详情-点击某版本详情，在版本详情页面url中查看
     * @throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function modelVersionDetail(int $modelVersionId)
    {
        $body = json_encode(compact('modelVersionId'));
        return $this->request(self::MODEL_VERSION_PATH, $body);
    }

    /**
     * 获取模型详情.
     * @param int $modelVersionId 模型版本id，通过页面url获取该字段值：在控制台-模型仓库列表-点击某模型详情-点击某版本详情，在版本详情页面url中查看
     * @throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function modelDetail(int $modelVersionId)
    {
        $body = json_encode(compact('modelVersionId'));
        return $this->request(self::MODEL_INFO_PATH, $body);
    }

    /**
     * 训练任务发布为模型.
     * @param bool $isNewModel 是否创建新模型，默认false，可选值如下：true：是 false：否
     * @param string $versionMeta 待发布的新模型版本元数据信息
     * @param string $modelName 模型名称。说明：（1）如果字段isNewModel为true，即发布为新建模型的版本时，该字段必填
     * @param int $modelId 已存在模型的ID。说明：（1）如果字段isNewModel为false，即发布为已有模型新版本时，该字段必填（2）该字段值通过千帆控制台-模型管理-模型列表页获取
     * @param string $tags 模型业务标签列表，说明：（1）如果isNewModel为false，即发布为已有模型新版本，使用该字段会更新模型业务标签列表；如果isNewModel为true，即发布为新建模型的版本，使用该字段会新建模型业务标签（2）业务标签数量限制最大不超过5个（3）业务标签格式需符合以下：中文或大小写字母数字组成，每个标签不超过10个字符
     * @throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function publishTrainModel(bool $isNewModel, string $versionMeta, string $modelName = '', int $modelId = 0, string $tags = '')
    {
        $body = json_encode(compact('isNewModel', 'versionMeta', 'modelName', 'modelId', 'tags'));
        return $this->request(self::MODEL_PUBLISH_PATH, $body);
    }

    /**
     * 获取预置模型列表.
     * @param int $pageNo 页码，最小值为1
     * @param int $pageSize 每页大小，必须大于0
     * @param string $nameFilter 名称过滤器
     * @param string $modelType 模型类型
     * @param string $orderBy 排序字段，目前仅支持create_time
     * @param string $order 次序
     *@throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function getPresetModel(int $pageNo, int $pageSize, string $nameFilter = '', string $modelType = '', string $orderBy = '', string $order = '')
    {
        $body = json_encode(compact('pageNo', 'pageSize', 'nameFilter', 'modelType', 'orderBy', 'order'));
        return $this->request(self::MODEL_PRESET_PATH, $body);
    }

    /**
     * 获取用户模型列表.
     * @param int $pageNo 页码，最小值为1
     * @param int $pageSize 每页大小，必须大于0
     * @param string $nameFilter 名称过滤器
     * @param string $modelType 模型类型
     * @param string $orderBy 排序字段，目前仅支持create_time
     * @param string $order 次序
     * @throws \BaiduBce\Exception\BceServiceException
     * @throws \BaiduBce\Exception\BceClientException
     * @return mixed
     */
    public function getUserModel(int $pageNo, int $pageSize, string $nameFilter = '', string $modelType = '', string $orderBy = '', string $order = '')
    {
        $body = json_encode(compact('pageNo', 'pageSize', 'nameFilter', 'modelType', 'orderBy', 'order'));
        return $this->request(self::MODEL_USER_PATH, $body);
    }

    /**
     * 批量删除模型.
     * @param array $modelIDs 要删除的模型id列表，说明：列表里的模型id类型需相同，即所有的模型id都是int，或者都是string
     * @throws \BaiduBce\Exception\BceClientException
     * @throws \BaiduBce\Exception\BceServiceException
     * @return mixed
     */
    public function batchDelete(array $modelIDs)
    {
        $body = json_encode(compact('modelIDs'));
        return $this->request(self::MODEL_BATCH_DELETE_PATH, $body);
    }

    /**
     * 批量删除模型版本.
     * @param array $modelVersionIds 要删除的模型版本id列表，说明：模型id类型需相同，即所有的模型id都是int，或者都是string
     * @throws BceClientException
     * @throws BceServiceException
     * @return mixed
     */
    public function batchVersionDelete(array $modelVersionIds)
    {
        $body = json_encode(compact('modelVersionIds'));
        return $this->request(self::MODEL_VERSION_BATCH_DELETE_PATH, $body);
    }
}
