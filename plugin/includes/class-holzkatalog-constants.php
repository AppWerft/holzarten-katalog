<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HOLZKATALOG_CONSTANTS
{  
   
    public const HANDLE_HK_FRONTEND_KATALOG = 'holzkat-frontend-katalog'; 
    public const HANDLE_HK_ADMIN = 'holzkat-admin';  
    public const HOLZARTEN_GEOPOSITION_MAP ='holzkat-geopositions-map';
}
final class HOLZKATALOG_HOOKS
{ public const WC_PRODUCT_DATA = 'woocommerce_product_options_general_product_data';
  public const WC_PRODUCT_OBJECT  ='woocommerce_admin_process_product_object';

}
final class HOLZKATALOG_HANDLES
{
}

final class HOLZKATALOG_OPTIONS
{ 
    public const HOLZARTEN_SETTINGS = 'hk_holzarten_settings';
    public const KATALOG_SETTINGS   = 'hk_katalog_settings';
    public const MAP_SETTINGS = 'hk_map_settings';
}

