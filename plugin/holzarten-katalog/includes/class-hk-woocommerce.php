
<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_WooCommerce
{
    /**
     * Globales WooCommerce-Attribut.
     */
    private const ATTRIBUTE_NAME = 'Holzart';
    private const ATTRIBUTE_SLUG = 'holzart';

    /**
     * Singleton.
     */
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
        // Erst nach Initialisierung von WooCommerce starten.
        add_action(
            'woocommerce_init',
            [$this, 'register_attribute']
        );

        // Synchronisierung im Admin-Menü.
        add_action(
            'admin_post_hk_sync_woocommerce',
            [$this, 'handle_sync']
        );

        // Bildinformationen für Variationen bereitstellen.
        add_filter(
            'woocommerce_available_variation',
            [$this, 'add_variation_image_data'],
            10,
            3
        );
    }

    /**
     * Globales Produktattribut anlegen.
     */
    public function register_attribute()
    {
        if (!function_exists('wc_create_attribute')) {
            return;
        }

        $attribute_id = wc_attribute_taxonomy_id_by_name(
            self::ATTRIBUTE_SLUG
        );

        if ($attribute_id) {
            return;
        }

        $result = wc_create_attribute([
            'name'         => self::ATTRIBUTE_NAME,
            'slug'         => self::ATTRIBUTE_SLUG,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($result)) {
            error_log(
                'HK WooCommerce: ' . $result->get_error_message()
            );
        }

        delete_transient('wc_attribute_taxonomies');
    }

    /**
     * Holzarten aus dem Katalog synchronisieren.
     */
    public function sync_attributes()
    {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return new WP_Error(
                'woocommerce_missing',
                'WooCommerce ist nicht verfügbar.'
            );
        }

        $attribute_id = wc_attribute_taxonomy_id_by_name(
            self::ATTRIBUTE_SLUG
        );

        if (!$attribute_id) {
            $this->register_attribute();

            $attribute_id = wc_attribute_taxonomy_id_by_name(
                self::ATTRIBUTE_SLUG
            );
        }

        if (!$attribute_id) {
            return new WP_Error(
                'attribute_missing',
                'Das WooCommerce-Attribut konnte nicht angelegt werden.'
            );
        }

        $taxonomy = wc_attribute_taxonomy_name(
            self::ATTRIBUTE_SLUG
        );

        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error(
                'taxonomy_missing',
                'Die Holzarten-Taxonomie ist noch nicht verfügbar. Bitte die Synchronisierung erneut starten.'
            );
        }

        $items = HK_Catalog::get_all();

        $created = 0;
        $updated = 0;

        foreach ($items as $item) {

            if (empty($item['active'])) {
                continue;
            }

            $name = trim($item['name_de'] ?? '');

            if ($name === '') {
                continue;
            }

            $slug = sanitize_title($name);

            $term = get_term_by(
                'slug',
                $slug,
                $taxonomy
            );

            if (!$term) {

                $result = wp_insert_term(
                    $name,
                    $taxonomy,
                    ['slug' => $slug]
                );

                if (is_wp_error($result)) {
                    continue;
                }

                $created++;

            } else {

                if ($term->name !== $name) {
                    wp_update_term(
                        $term->term_id,
                        $taxonomy,
                        ['name' => $name]
                    );
                }

                $updated++;
            }

        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Synchronisierung über Admin-Aktion starten.
     */
    public function handle_sync() {

        wp_die('handle_sync wurde aufgerufen');

        
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        check_admin_referer(
            'hk_sync_woocommerce',
            'hk_sync_nonce'
        );

        $result = $this->sync_attributes();

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        $url = admin_url(
            'admin.php?page=holzarten-katalog'
        );

        wp_safe_redirect(
            add_query_arg([
                'wc_synced'  => 1,
                'wc_created' => $result['created'],
                'wc_updated' => $result['updated'],
            ], $url)
        );

        exit;
    }

    /**
     * Bildinformationen an WooCommerce-Variationen übergeben.
     */
    public function add_variation_image_data(
        $variation_data,
        $product,
        $variation
    ) {
        $attributes = $variation->get_attributes();

        $holzart_slug = $attributes[
            'pa_' . self::ATTRIBUTE_SLUG
        ] ?? '';

        if ($holzart_slug === '') {
            return $variation_data;
        }

        $taxonomy = wc_attribute_taxonomy_name(
            self::ATTRIBUTE_SLUG
        );

        $term = get_term_by(
            'slug',
            $holzart_slug,
            $taxonomy
        );

        if (!$term || is_wp_error($term)) {
            return $variation_data;
        }

        $items = HK_Catalog::get_all();

        foreach ($items as $item) {

            if (
                sanitize_title($item['name_de'] ?? '')
                !== $term->slug
            ) {
                continue;
            }

            $image_file = $item['image_file'] ?? '';

            if ($image_file !== '') {

                $upload = wp_upload_dir();

                $image_url = trailingslashit(
                    $upload['baseurl']
                ) . 'holzarten-daten/images/'
                    . rawurlencode(basename($image_file));

                $variation_data['hk_image_url'] = esc_url(
                    $image_url
                );
            }

            break;
        }

        return $variation_data;
    }
}
