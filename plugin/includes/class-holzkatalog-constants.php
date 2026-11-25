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
    public const HOLZARTENKATALOG_PAGE_HK_SETTINGS_HOLZARTEN = 'holzartenkatalog_page_hk_settings_holzarten';
    public const WC_PRODUCT_DATA = 'woocommerce_product_options_general_product_data';
    public const WC_PRODUCT_OBJECT  = 'woocommerce_admin_process_product_object';
    public const WP_ADMIN_MENU  = 'admin_menu';
    public const HOLZARTENKATALOG_PAGE_HK_SETTINGS_FUNDORTE = 'holzartenkatalog_page_hk_settings_fundorte';
    public const WP_ADMIN_ENQUEUE_SCRIPTS = 'admin_enqueue_scripts';
    public const WC_AJAX_SAVE_ONLYACTIVE = 'wp_ajax_hk_save_only_active';
    public const LEAFLET = 'leaflet';

    public const HOLZARTENKATALOG_PAGE_HK_SETTING_KATALOG = 'holzartenkatalog_page_hk_settings_katalog';
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
    public const JQUERY = 'jquery';
    public const LEAFLET_CSS = 'holzcat-leaflet_css';
    public const LEAFLET_JS = 'holzcat-leaflet_js';
    public const FRONTEND_KATALOG = 'holzkat-frontend-katalog';
    public const ADMIN = 'holzkat-admin';
    public const HOLZCAT_HOLZARTEN = 'holzkat-holzarten';
    public const HOLZCAT_HOLZARTEN_SETTING = 'holzkat-holzarten-settings';
    public const GEOPOSITION_MAP = 'holzkat-geopositions-map';
    public const HOLZCAT_D3 = 'holzkat-d3';
    public const HOLZCAT_PINGLAYER = 'holzkat-ping-layer';
    public const HOLZCAT_LEAFLET_PROVIDERS = 'holzkat-leaflet-providers';
    public const HOLZCAT_HOLZARTEN_GEOSETTINGS_JS = 'holzkat-holzarten-geosettings_js';
    public const HOLZCAT_HOLZARTEN_GEOSETTINGS_CSS = 'holzkat-holzarten-geosettings_css';
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
    public const RADIUS = 'radius';
    public const ZOOM = 'zoom';
    public const LUPE = 'lupe';
    public const DESCRIPTION = 'description';
    public const LAT = 'lat';
    public const GER = 'ger';
    public const NAME = 'name';
    public const TILE_PROVIDER = 'tile_provider';
}

final class HOLZKATALOG_JS_CSS
{
    public const ADMIN_HOLZARTEN_SETTINGS_JS = 'assets/js/admin-holzarten-settings.js';
    public const ADMIN_HOLZARTEN_SETTINGS_CSS = 'assets/css/admin-holzarten-settings.css';

    public const HK_ADMIN_CSS = 'assets/css/hk-admin.css';
    public const HK_ADMIN_JS = 'assets/css/hk-admin.js';

    public const ADMIN_HOLZARTENKATALOG_JS = 'assets/js/admin-holzarten-katalog.js';
    public const FRONTEND_KATALOG_CSS = 'assets/css/frontend-katalog.css';
    public const HOLZKATLOG_ADMIN_JS = 'assets/js/holzkatalog-admin.js';
    public const LEAFLET_CSS = 'assets/vendor/leaflet/leaflet.css';
    public const LEAFLET_JS = 'assets/vendor/leaflet/leaflet.js';
    public const PINGLAYER = 'assets/vendor/leaflet/leaflet-d3.js';
    public const LEAFLET_PROVIDERS = 'assets/vendor/leaflet/leaflet-providers.js';
    public const ADMIN_HOLZARTEN_GEOSETTINGS_JS = 'assets/js/admin-holzarten-geosettings.js';
    public const ADMIN_HOLZARTEN_GEOSETTINGS_CSS = 'assets/js/admin-holzarten-geosettings.css';


    public const D3V5 = 'assets/vendor/d3/d3v5.js';
    // noch nicht genutzt:
    public const FRONTEND_KATALOG_JS = 'assets/js/frontend-katalog.js';
}   
