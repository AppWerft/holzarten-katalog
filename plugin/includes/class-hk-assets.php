
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
     * Leaflet, D3 und PingLayer zentral laden.
     */
    private static function enqueue_leaflet()
    {
        // Leaflet CSS
        wp_enqueue_style(
            'hk-leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
            [],
            '1.9.4'
        );

        // D3
        wp_enqueue_script(
            'hk-d3',
            HK_PLUGIN_URL . 'assets/vendor/d3/d3v5.js',
            [],
            '5.16.0',
            true
        );

        // Leaflet
        wp_enqueue_script(
            'hk-leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // PingLayer
        wp_enqueue_script(
            'hk-ping-layer',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet-d3.js',
            ['hk-leaflet', 'hk-d3'],
            HK_VERSION,
            true
        );
    }

    /**
     * Backend.
     */
    public static function enqueue_admin($hook)
    {
        $screen = get_current_screen();

        if (
            !$screen ||
            $screen->post_type !== 'product' ||
            !in_array($hook, ['post.php', 'post-new.php'], true)
        ) {
            return;
        }

        self::enqueue_leaflet();

        wp_enqueue_script(
            'hk-origin-picker',
            HK_PLUGIN_URL . 'assets/js/hk-origin-picker.js',
            ['hk-leaflet', 'hk-ping-layer'],
            HK_VERSION,
            true
        );
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
