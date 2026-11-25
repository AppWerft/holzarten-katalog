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
            HOLZKATALOG_SCRIPT_HANDLES::LEAFLET,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::LEAFLET,
            [],
            '1.9.4'
        );

        // D3
        wp_enqueue_script(
            HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_D3,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_D3,
            [],
            HOLZKATALOG_VERSION,
            true
        );

        // Leaflet
        wp_enqueue_script(
            HOLZKATALOG_SCRIPT_HANDLES::LEAFLET,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::LEAFLET_JS,
            [],
            '1.9.4',
            true
        );

        // PingLayer
        wp_enqueue_script(
            HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_PINGLAYER,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::PINGLAYER,
            [HOLZKATALOG_SCRIPT_HANDLES::LEAFLET, HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_D3],
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
            HOLZKATALOG_STYLE_HANDLES::HOLZCAT_FRONTEND_HOLZARTEN,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::FRONTEND_HOLZARTEN,
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
            HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_ORIGIN_PICKER,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::HK_ORIGIN_PICKER_JS,
            [HOLZKATALOG_HOOKS::LEAFLET, HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_PINGLAYER],
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
            'holzkat-frontend-holzarten',
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
