<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Plugin
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
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function register_admin_menu()
    {
        add_menu_page(
            'Holzarten-Katalog',
            'Holzarten',
            'manage_options',
            'holzarten-katalog',
            [$this, 'admin_page'],
            'dashicons-palmtree',
            25
        );
    }

    public function admin_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Holzarten-Katalog</h1>';
        echo '<p>Verwaltung der Holzarten.</p>';
        echo '</div>';
    }
}