<?php

if (!defined('ABSPATH')) {
    exit;
}
/*
final class HOLZKATALOG_CONSTANTS
{
    public const HOLZARTEN_GEOPOSITION_MAP = 'holzkat-geopositions-map';
}*/
final class HOLZKATALOG_HOOKS
{
    public const WC_PRODUCT_DATA = 'woocommerce_product_options_general_product_data';
    public const WC_PRODUCT_OBJECT  = 'woocommerce_admin_process_product_object';
    public const WP_ADMIN_MENU  = 'admin_menu';
    public const WP_ADMIN_ENQUEUE_SCRIPTS = 'admin_enqueue_scripts';
    public const WC_AJAX_SAVE_ONLYACTIVE = 'wp_ajax_hk_save_only_active';
}

final class HOLZKATALOG_HOOKSUFFIXES
{
    public const TOPLEVEL_PAGE_HOLZARTENKATALOG = 'toplevel_page_holzarten-katalog';
    public const  HOLZARTENKATALOG_PAGE_IMPORTEXPORT = 'holzartenkatalog_page_holzarten-import-export';
}

final class HOLZKATALOG_NONCES
{
    public const TOGGLE_STATUS = 'holzkatalog_toggle_status';
    public const CAT_NONCE = 'hk_catalog_nonce';
}

final class HOLZKATALOG_HANDLES
{
    public const FRONTEND_KATALOG = 'holzkat-frontend-katalog';
    public const ADMIN = 'holzkat-admin';

    public const GEOPOSITION_MAP = 'holzkat-geopositions-map';
}

final class HOLZKATALOG_OPTIONS
{
    public const HOLZARTEN_SETTINGS = 'hk_holzarten_settings';
    public const KATALOG_SETTINGS   = 'hk_katalog_settings';
    public const MAP_SETTINGS = 'hk_map_settings';
    public const POSITION = 'position';
    public const PINGCOLOR = 'pingcolor';
    public const WIDTH = 'width';
    public const HEIGHT = 'height';
    public const SORTIERUNG = 'sortierung';
    public const BILDER = 'bilder';
    public const SPALTEN = 'spalten';
    public const CARD = 'card';
    public const NURPRODUKTE = 'nurprodukte';
    public const SIZE = 'size';
    public const ROUND = 'round';
    public const LUPE = 'lupe';
    public const DESCRIPTION = 'description';
    public const LAT = 'lat';
    public const GER = 'ger';
    public const NAME = 'name';
}
final class HOLZKATALOG_ASSETS
{
    public const ADMIN_HOLZARTENKATALOG_JS = 'assets/js/admin-holzartenkatalog.js';
    public const FRONTEND_KATALOG_CSS = 'assets/css/frontend-katalog.css';
    // noch nicht genutzt:
    public const FRONTEND_KATALOG_JS = 'assets/js/frontend-katalog.js'; 
}
