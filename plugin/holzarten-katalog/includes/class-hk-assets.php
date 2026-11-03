<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Assets {
    public static function init()
    {   error_log('HK_ASSETS INIT ERREICHT');
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
            ['hk-leaflet'],
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