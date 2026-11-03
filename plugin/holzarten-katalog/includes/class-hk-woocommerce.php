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
        add_action(
            'admin_init',
            [$this, 'maybe_sync_pending'],
            20
            );

       
    }
    /**
     * Synchronisierung für den nächsten Admin-Aufruf vormerken.
     */
    public static function schedule_sync()
    {
        update_option('hk_wc_sync_pending', 1, false);
    }

    /**
     * Vorgemerkte Synchronisierung ausführen.
     */
    public function maybe_sync_pending()
    {
        if (!get_option('hk_wc_sync_pending')) {
            return;
        }

        if (!class_exists('WooCommerce')) {
            return;
        }

        // Sicherstellen, dass das globale Attribut existiert.
        $this->register_attribute();

        // WooCommerce muss die Taxonomie bereits registriert haben.
        if (!taxonomy_exists('pa_holzart')) {
            return;
        }

        $result = $this->sync_attributes();

        // Auftrag nur bei erfolgreicher Synchronisierung löschen.
        if (is_array($result)) {
            delete_option('hk_wc_sync_pending');
        }
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
 * Holzarten-Bild in die Mediathek übernehmen
 * und als WooCommerce-Term-Bild speichern.
 */
private function sync_term_image($term_id, $image_file)
{
    if (empty($image_file)) {
        return;
    }

    $filename = basename($image_file);

    // Quelldatei im Holzarten-Verzeichnis.
    $source = trailingslashit(
        HK_Storage::directory()
    ) . 'images/' . $filename;

    if (!file_exists($source)) {
        error_log(
            'HK WooCommerce: Bild nicht gefunden: ' . $source
        );
        return;
    }

    // Prüfen, ob dieses Bild bereits importiert wurde.
    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_hk_source_image_file',
        'meta_value'     => $filename,
    ]);

    if (!empty($attachments)) {

        $attachment_id = (int) $attachments[0];

    } else {

        // Dateityp prüfen.
        $filetype = wp_check_filetype($filename);

        if (empty($filetype['type']) ||
            strpos($filetype['type'], 'image/') !== 0) {
            return;
        }

        // WordPress-Bildfunktionen laden.
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Mediathek-Eintrag erstellen.
        $attachment_id = wp_insert_attachment(
            [
                'post_mime_type' => $filetype['type'],
                'post_title'     => pathinfo(
                    $filename,
                    PATHINFO_FILENAME
                ),
                'post_status'    => 'inherit',
            ],
            $source
        );

        if (is_wp_error($attachment_id)) {
            error_log(
                'HK WooCommerce: ' .
                $attachment_id->get_error_message()
            );
            return;
        }

        // Bild-Metadaten und Vorschaubilder erzeugen.
        $metadata = wp_generate_attachment_metadata(
            $attachment_id,
            $source
        );

        if (is_array($metadata)) {
            wp_update_attachment_metadata(
                $attachment_id,
                $metadata
            );
        }

        // Ursprungsdatei merken, um Duplikate zu vermeiden.
        update_post_meta(
            $attachment_id,
            '_hk_source_image_file',
            $filename
        );
    }

    // WooCommerce-Term-Bild zuweisen.
    update_term_meta(
        $term_id,
        'thumbnail_id',
        $attachment_id
    );

    
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

            $term = get_term_by('slug', $slug, $taxonomy);

            if (!$term) {

                $result = wp_insert_term(
                    $name,
                    $taxonomy,
                    ['slug' => $slug]
                );

                if (is_wp_error($result)) {
                    continue;
                }

                $term_id = $result['term_id'];
                $created++;

            } else {

                $term_id = $term->term_id;

                if ($term->name !== $name) {
                    wp_update_term(
                        $term_id,
                        $taxonomy,
                        ['name' => $name]
                    );
                }

                $updated++;
            }

            // Term-Metadaten aktualisieren.
            update_term_meta(
                $term_id,
                'hk_name_latein',
                $item['name_latein'] ?? ''
            );

            update_term_meta(
                $term_id,
                'hk_code',
                $item['code'] ?? ''
            );

            update_term_meta(
                $term_id,
                'hk_image_file',
                $item['image_file'] ?? ''
            );
            // Bild in die Mediathek übernehmen und zuweisen.
            $this->sync_term_image(
                $term_id,
                $item['image_file'] ?? ''
            );
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Holzarten-Bild eines Produkts ausgeben.
     */
    public static function get_product_holzart_image($product_id)
    {
        $product = wc_get_product($product_id);

        if (!$product) {
            return '';
        }

        $terms = get_the_terms(
            $product_id,
            'pa_holzart'
        );

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $term = reset($terms);

        $image_id = get_term_meta(
            $term->term_id,
            'thumbnail_id',
            true
        );

        if (!$image_id) {
            return '';
        }

        return wp_get_attachment_image(
            $image_id,
            'medium',
            false,
            [
                'class' => 'hk-holzart-image',
                'alt'   => $term->name,
            ]
        );
    }
    

    /**
     * Synchronisierung über Admin-Aktion starten.
     */
    public function handle_sync() {

        
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
