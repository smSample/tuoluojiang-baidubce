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

use Psr\SimpleCache\CacheInterface;
use Tuoluojiang\Baidubce\Base\Chat;

class Conversation extends Chat
{
    public function __construct(array $config,CacheInterface $cache = null, bool $type = true)
    {
        parent::__construct($config, $cache, $type);
    }

    /**
     * ERNIE-4.0-8K.
     * @param string $user_id 表示最终用户的唯一标识符，可以监视和检测滥用行为，防止接口恶意调用
     * @param float $temperature 说明：（1）较高的数值会使输出更加随机，而较低的数值会使其更加集中和确定。（2）默认0.95，范围 (0, 1.0]，不能为0。（3）建议该参数和top_p只设置1个。（4）建议top_p和temperature不要同时更改。。
     * @param float $top_p 说明：（1）影响输出文本的多样性，取值越大，生成文本的多样性越强。（2）默认0.8，取值范围 [0, 1.0]。（3）建议该参数和temperature只设置1个。（4）建议top_p和temperature不要同时更改。
     * @param int $penalty_score 通过对已生成的token增加惩罚，减少重复生成的现象。说明：（1）值越大表示惩罚越大。（2）默认1.0，取值范围：[1.0, 2.0]。
     * @param string $system 模型人设，主要用于人设设定，例如，你是xxx公司制作的AI助手，说明：（1）长度限制1024个字符（2）如果使用functions参数，不支持设定人设system
     * @param string $stop 生成停止标识，当模型生成结果以stop中某个元素结尾时，停止文本生成
     * @param bool $disable_search 否强制关闭实时搜索功能，默认false，表示不关闭
     * @param bool $enable_citation 是否开启上角标返回
     * @param string $max_output_tokens 指定模型最大输出token数，范围[2, 2048]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function ernie_4(array $messages, string $user_id = '', float $temperature = 0.95, float $top_p = 0.8, int $penalty_score = 1, string $system = '', string $stop = '', bool $disable_search = false, bool $enable_citation = false, string $max_output_tokens = '')
    {
        $body = json_encode(compact('messages', 'user_id', 'temperature', 'top_p', 'penalty_score', 'system', 'stop', 'disable_search', 'enable_citation', 'max_output_tokens'));
        return $this->request(self::ERNIE_4, $body);
    }
}
