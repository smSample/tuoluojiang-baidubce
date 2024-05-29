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

use Tuoluojiang\Baidubce\Base\Qianfan;

/**
 * 提示词.
 */
class Prompt extends Qianfan
{
    public function __construct(string $accessKey, string $secretKey)
    {
        parent::__construct($accessKey, $secretKey);
    }

    /**
     * 创建Prompt模版.
     * @param string $templateName 模版名称
     * @param string $templateContent 模版内容
     * @param string $templateVariables 模版变量
     * @param string $variableIdentifier 变量识别符号
     * @param array $labelIds 模版标签Id数组
     * @param int $sceneType 场景类型
     * @param int $frameworkType 模版框架
     * @param string $negativeTemplateContent 反向prompt模版内容，表示不希望大模型生成的内容，说明：只有sceneType为2，即场景类型为文生图时，该字段有效
     * @param string $negativeTemplateVariables 反向prompt模版的变量，说明：只有sceneType为2时，即场景类型为文生图时，该字段有效
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptCreate(string $templateName, string $templateContent, string $templateVariables, string $variableIdentifier, array $labelIds = [], int $sceneType = 0, int $frameworkType = 0, string $negativeTemplateContent = '', string $negativeTemplateVariables = '')
    {
        $body = json_encode(compact('templateName', 'templateContent', 'templateVariables', 'variableIdentifier', 'labelIds', 'sceneType', 'frameworkType', 'negativeTemplateContent', 'negativeTemplateVariables'));
        return $this->request(self::PROMPT_CREATE_PATH, $body);
    }

    /**
     * 更新Prompt模版.
     * @param int $templateId Prompt模版Id
     * @param string $templateName Prompt模版的名称
     * @param array $labelIds 模版标签Id数组，说明：单个模版最多可选3个标签数组，元素是标签id
     * @param string $templateContent 模版内容
     * @param string $variableIdentifier 变量识别符号
     * @param string $negativeTemplateContent 反向prompt模版内容，表示不希望大模型生成的内容，说明：只有sceneType为2，即场景类型为文生图时，该字段有效
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptUpdate(int $templateId, string $templateName = '', array $labelIds = [], string $templateContent = '', string $variableIdentifier = '', string $negativeTemplateContent = '')
    {
        $body = json_encode(compact('templateId', 'templateName', 'labelIds', 'templateContent', 'variableIdentifier', 'negativeTemplateContent'));
        return $this->request(self::PROMPT_UPDATE_PATH, $body);
    }

    /**
     * 删除Prompt模版.
     * @param int $templateId Prompt模版Id
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptDelete(int $templateId)
    {
        $body = json_encode(compact('templateId'));
        return $this->request(self::PROMPT_DELETE_PATH, $body);
    }

    /**
     * 获取prompt模版列表.
     * @param int $offset 偏移量，不填则默认为0
     * @param int $pageSize 一页大小，不填则默认为10
     * @param string $name 输入模版名称或内容搜索
     * @param array $labelIds 标签ID数组
     * @param int $type 模板类型：1预置模板，2用户自定义模板
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptList(int $offset = 1, int $pageSize = 10, string $name = '', array $labelIds = [], int $type = 2)
    {
        $body = json_encode(compact('offset', 'pageSize', 'name', 'labelIds', 'type'));
        return $this->request(self::PROMPT_LIST_PATH, $body);
    }

    /**
     * 获取prompt模版标签列表.
     * @param int $offset 偏移量，不填则默认为0
     * @param int $pageSize 一页大小，不填则默认为10
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptLabelList(int $offset = 1, int $pageSize = 10)
    {
        $body = json_encode(compact('offset', 'pageSize'));
        return $this->request(self::PROMPT_LABEL_LIST_PATH, $body);
    }

    /**
     * 获取prompt模版标签详情.
     * @param int $id 模板ID
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return mixed
     */
    public function promptInfo(int $id)
    {
        $body = json_encode(compact('id'));
        return $this->request(self::PROMPT_INFO_PATH, $body);
    }
}
