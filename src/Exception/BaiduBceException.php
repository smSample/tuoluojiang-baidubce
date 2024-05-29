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
namespace Tuoluojiang\Baidubce\Exception;

use Throwable;

/**
 * BaiduBceException.
 */
class BaiduBceException extends \RuntimeException
{
    protected int $statusCode;

    /**
     * @param $message
     * @param $code
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, int $statusCode = 200)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }
}
