<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Assets {
    public static function init()
    {
        add_action(
            'admin_enqueue_scripts',
            [__CLASS__, 'enqueue_admin']
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_frontend']
        );
    }

    /**
     * Leaflet zentral laden.
     */
    private static function enqueue_leaflet()
    {
        wp_enqueue_style(
            'hk-leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'hk-leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );
    }

    /**
     * Backend.
     */
    public static function enqueue_admin($hook)
{
    error_log('HK_Assets: enqueue_admin aufgerufen: ' . $hook);

    $screen = get_current_screen();

    error_log(
        'HK_Assets: Screen: '
        . ($screen ? $screen->id : 'NULL')
    );

    self::enqueue_leaflet();
}

    /**
     * Frontend.
     */
    public static function enqueue_frontend()
    {
        if (!is_product()) {
            return;
        }

        self::enqueue_leaflet();

        // Frontend-Karten-JavaScript folgt hier.
    }
}