<?php

namespace Core;

/**
 * Class CookieControler
 * @package Alkane\CookieControler
 */
class CookieControler {

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public static function set(
        string $name,
        string $value,
        int $expire = 1,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false
    ) {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
    

    /**
     * @static
     * @param string $name
     * @return mixed
     */
    public static function get(string $name) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return false;
    }


    /**
     * @static
     * @param string $name
     * @return bool
     */
    public static function delete(string $name) {
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            return setcookie($name, '', time() - 3600);
        }
    }
}


