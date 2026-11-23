<?php

if (!defined('ABSPATH')) {
    exit;
}
class HOLZKATALOG_Assets
{
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
            HOLZKATALOG_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
            [],
            '1.9.4'
        );

        // D3
        wp_enqueue_script(
            'hk-d3',
            HOLZKATALOG_PLUGIN_URL . 'assets/vendor/d3/d3v5.js',
            [],
            '5.16.0',
            true
        );

        // Leaflet
        wp_enqueue_script(
            'hk-leaflet',
            HOLZKATALOG_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // PingLayer
        wp_enqueue_script(
            'hk-ping-layer',
            HOLZKATALOG_PLUGIN_URL . 'assets/vendor/leaflet/leaflet-d3.js',
            ['hk-leaflet', 'hk-d3'],
            HOLZKATALOG_VERSION,
            true
        );
    }

    /**
     * Backend.
     */
    public static function enqueue_admin($hook)
    { // Katalog-CSS für die Admin-Vorschau
        wp_enqueue_style(
            'hk-frontend-holzarten',
            HOLZKATALOG_PLUGIN_URL . 'assets/css/frontend-holzarten.css',
            [],
            HOLZKATALOG_VERSION
        );

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
            HOLZKATALOG_PLUGIN_URL . 'assets/js/hk-origin-picker.js',
            ['hk-leaflet', 'hk-ping-layer'],
            HOLZKATALOG_VERSION,
            true
        );
    }

    /**
     * Frontend.
     */
    public static function enqueue_frontend()
    {
        // Katalog-CSS auf allen Frontend-Seiten
        wp_enqueue_style(
            'hk-frontend-holzarten',
            HOLZKATALOG_PLUGIN_URL . 'assets/css/frontend-holzarten.css',
            [],
            HOLZKATALOG_VERSION
        );
        // Leaflet nur auf Produktseiten
        if (
            function_exists('is_product')
            && is_product()
        ) {
             self::enqueue_leaflet();
        }       

    }
}
