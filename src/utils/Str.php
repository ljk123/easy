<?php

namespace easy\utils;

class Str
{
    /**
     * 驼峰转下划线
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = null)
    {
        if (is_null($delimiter)) {
            $delimiter = '_';
        }
        $key = $value;

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);

            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }

    /**
     * 下划线转换驼峰 首字母大写
     * @param string $string $string
     * @return string
     */
    public static function studly(string $string)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    /**
     * 下划线转换驼峰 首字母小写
     * @param string $string $string
     * @return string
     */
    public static function camel(string $string)
    {
        return lcfirst(static::studly($string));
    }

    /**
     * 生产随机字符串
     * @param $length
     * @return bool|string
     */
    public static function getRandChar($length)
    {
        if (!is_int($length) || $length <= 0) {
            return false;
        }
        $str = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";//大小写字母以及数字
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[mt_rand(0, $max)];
        }
        return $str;
    }
}