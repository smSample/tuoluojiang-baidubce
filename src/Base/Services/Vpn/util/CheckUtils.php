<?php


namespace Tuoluojiang\Baidubce\Base\Services\Vpn\util;


class CheckUtils {

    /**
     * @param $arg
     * @param $fieldName
     */
    public static function isBlank($arg, $fieldName){
        if (empty($arg)) {
            throw new \InvalidArgumentException(
                'request ' . $fieldName . ' should not be empty .'
            );
        }
    }
}
