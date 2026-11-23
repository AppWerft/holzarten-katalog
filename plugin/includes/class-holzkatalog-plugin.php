<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOLZKATALOG_Plugin
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        HOLZKATALOG_Admins::instance();
        HOLZKATALOG_Actions::instance();
        HOLZKATALOG_Frontend::instance();
    }
}