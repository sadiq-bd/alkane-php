<?php

namespace Core;

/**
 * SessionControler Class
 *
 * @category  Session Controler
 * @package   Session
 * @author    Sadiq <sadiq.developer.bd@gmail.com>
 * @copyright Copyright (c) 2022-23
 * @version   2.0
 * @package   SessionControler
 */

class Session {


    private static $keyDelimiter = '.';

    /**
     * starts a session if not started
     * @return void
     */
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }


    /**
     * @param $key
     * @param mixed $value
     */
    public static function set(string $keys, $value) {
        self::init();

        $array = [];
        $keys = explode(self::$keyDelimiter, trim(trim($keys), self::$keyDelimiter));
        $reference = &$array;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }
        $reference = $value;
        unset($reference);
        
        $_SESSION = array_merge_recursive($_SESSION, $array);

    }


    /**
     * @param $key
     * @return mixed
     */
    public static function get($keys) {
        self::init();

        $array = $_SESSION;
        $value = '';

        $keys = explode(self::$keyDelimiter, trim(trim($keys), self::$keyDelimiter));

        $reference = &$array;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }

        if ($reference === array()) {
            return null;
        } else {
            return $reference;
        }

    }


    /**
     * @param string $key
     */
    public static function is_exist(string $key) {
        self::init();
        return self::get($key) !== null;
    }


    /**
     * unsets a session or all sessions
     * @param string $key
     */
    public static function unset($keys = null) {
        self::init();
        if ($keys !== null || $keys !== '') {
            $array = $_SESSION;
            
            $keys = explode(self::$keyDelimiter, trim(trim($keys), self::$keyDelimiter));
            
            $reference = &$array;
            foreach ($keys as $i => $key) {
                if (!array_key_exists($key, $reference)) {
                    $reference[$key] = [];
                }
                if ($i === count($keys) - 1) {
                    unset($reference[$key]);

                    $_SESSION = $array;

                    return true;
                } else {
                    $reference = &$reference[$key];
                }
                
            }
        } else {
            self::destroy();
            return true;
        }
    }

    /**
     * destroys the session
     */
    public static function destroy() {
        self::init();
        session_unset();
        session_destroy();
    }
}


