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
namespace Tuoluojiang\baidubce\Application;

class Completion
{
    public function run()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/completions/?access_token=',
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST  => 'POST',

            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
