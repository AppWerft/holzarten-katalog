<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOLZKATALOG_CONSTANTS
{
    public const OPTIONS = [
        'MAP_SETTINGS'       => 'hk_map_settings',
        'HOLZARTEN_SETTINGS' => 'hk_holzarten_settings',
        'KATALOG_SETTINGS'   => 'hk_katalog_settings',
    ];

    public const AJAX = [
        'TOGGLE_STATUS'    => 'hk_toggle_status',
        'SYNC_WOOCOMMERCE' => 'hk_sync_woocommerce',
    ];

    public const TAXONOMIES = [
        'HOLZART' => 'pa_holzart',
    ];
}