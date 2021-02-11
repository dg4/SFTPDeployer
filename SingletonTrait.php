<?php

namespace Vendor\App;

trait SingletonTrait
{
    private static $instance;

    private function __construct() { }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self(); // new self() will refer to the class that uses the trait
        }

        return self::$instance;
    }

    private function __clone() { }
    protected function __sleep() { }
    protected function __wakeup() { }
}
