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
 * 向量.
 */
class Embeddings extends Chat
{
    protected array $path = [
        'embedding_v1' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/embeddings/embedding-v1',
        'bge_large_zh' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/embeddings/bge_large_zh',
        'bge_large_en' => '/rpc/2.0/ai_custom/v1/wenxinworkshop/embeddings/bge_large_en',
        'tao_8k'       => '/rpc/2.0/ai_custom/v1/wenxinworkshop/embeddings/tao_8k',
    ];

    public function __construct(array $config, CacheInterface $cache = null, bool $type = self::TYPE_TOKEN)
    {
        parent::__construct($config, $cache, $type);
    }

    /**
     * 发送请求
     * @param array $input 输入文本以获取embeddings。说明：（1）文本数量不超过16 （2）每个文本长度不超过 384个token
     * @param string $user_id 表示最终用户的唯一标识符，可以监视和检测滥用行为，防止接口恶意调用
     * @param string $path 向量模型
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @return mixed
     */
    public function handler(array $input, string $path = 'embedding_v1', string $user_id = '')
    {
        $body = json_encode(compact('input', 'user_id'));
        return $this->request($this->path[$path], $body);
    }
}
