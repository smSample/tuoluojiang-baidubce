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

/**
 * 会话.
 */
class Conversation extends Chat
{

    /**
     * 会话模型
     * @var array
     */
    protected array $conversationPath = [
        'ernie-4.0-8k' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro',
        //ERNIE-SPEED-8K
        'ernie-speed-8k' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie_speed',
        //ERNIE-SPEED-APPBUILDER
        'ernie-speed-appbuilder' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ai_apaas',
        //ERNIE-SPEED-128K
        'ernie-speed-128k' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-speed-128k',
        //ERNIE-LITE-8K
        'ernie-lite-8k' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-lite-8k',
        //ERNIE-LITE-8K-0922
        'ernie-lite-8k-0922' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/eb-instant',
        //YI-34B-CHAT
        'yi-34b-chat' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/yi_34b_chat',
        //FUYU-8B
        'fuyu-8b'   => '/rpc/2.0/ai_custom/v1/wenxinworkshop/image2text/fuyu_8b',
        'ernie-3.5' => [
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-3.5-8k-0205',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-3.5-8k-1222',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie_bot_8k',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-3.5-4k-0205',
        ],
        'ernie-4.0' => [
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro_preemptible',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-4.0-8k-preview',
            '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/ernie-4.0-8k-0329',
        ],
    ];

    public function __construct(array $config, CacheInterface $cache = null, bool $type = true)
    {
        parent::__construct($config, $cache, $type);
    }

    /**
     * 发送模型会话.
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
     * @return mixed
     */
    public function handler(array $messages, string $user_id = '', float $temperature = 0.95, float $top_p = 0.8, int $penalty_score = 1, string $system = '', string $stop = '', bool $disable_search = false, bool $enable_citation = false, string $max_output_tokens = '', string $path = 'yi-34b-chat')
    {
        $body = compact('messages', 'user_id', 'temperature', 'top_p', 'penalty_score', 'system', 'stop', 'disable_search', 'enable_citation', 'max_output_tokens');
        return $this->request($this->conversationPath[strtolower($path)], $body);
    }
}
