<?php

class HOLZKATALOG_Frontend
{
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
        add_shortcode('hk_holzarten', [$this, 'render_wood_catalog']);
        add_action(HOLZKATALOG_HOOKS::WP_ADMIN_ENQUEUE_SCRIPTS, [$this, 'enqueue_frontend_assets']);
    }

    public function enqueue_frontend_assets()
    {
        $jsfile = HOLZKATALOG_PLUGIN_DIR . HOLZKATALOG_JS::ADMIN_HOLZARTENKATALOG;

        error_log('DIR: ' . HOLZKATALOG_PLUGIN_DIR);
        error_log('URL: ' . HOLZKATALOG_PLUGIN_URL);
        error_log('JS: ' . $jsfile);
        error_log('JS exists: ' . (file_exists($jsfile) ? 'YES' : 'NO'));
        wp_enqueue_style(
            HOLZKATALOG_STYLE_HANDLES::FRONTEND_KATALOG,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::FRONTEND_KATALOG,
            [],
            HOLZKATALOG_VERSION
        );

        wp_enqueue_script(
            HOLZKATALOG_SCRIPT_HANDLES::ADMIN,
            $jsfile,
            ['jquery'],
            file_exists($jsfile) ? filemtime($jsfile) : HOLZKATALOG_VERSION,
            true
        );
    }
    public function enqueue_styles()
    {
        wp_enqueue_style(
            HOLZKATALOG_STYLE_HANDLES::FRONTEND_KATALOG,
            HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::FRONTEND_KATALOG,
            [],
            HOLZKATALOG_VERSION
        );
    }
    /**
     * Shortcode [hk_holzarten]
     */
    public function render_wood_catalog($atts = [])
    {
        // Gespeicherte Einstellungen als Standardwerte
        $defaults = wp_parse_args(
            get_option(HOLZKATALOG_OPTIONS::KATALOG_SETTINGS, []),
            [
                HOLZKATALOG_OPTIONS::SORTIERUNG => 'name',
                HOLZKATALOG_OPTIONS::BILDER => 1,
                HOLZKATALOG_OPTIONS::SPALTEN  => 3,
                HOLZKATALOG_OPTIONS::CARD => 0,
            ]
        );

        // Shortcode-Attribute können Einstellungen überschreiben
        $settings = shortcode_atts(
            $defaults,
            $atts,
            'hk_holzarten'
        );

        return self::render_catalog($settings);
    }


    /**
     * Gemeinsame HTML-Ausgabe für Frontend und Admin-Vorschau.
     * Für die Darstellung der Holzarten gelten die Optionen aus render_holzarten().
     */
    public static function render_catalog($settings = [])
    {
        // Katalog-Einstellungen
        $settings = wp_parse_args(
            $settings,
            [
                HOLZKATALOG_OPTIONS::SORTIERUNG  => 'name',
                HOLZKATALOG_OPTIONS::BILDER => 1,
                HOLZKATALOG_OPTIONS::SPALTEN  => 3,
                HOLZKATALOG_OPTIONS::CARD  => 0,
                HOLZKATALOG_OPTIONS::NURPRODUKTE => 0,
            ]
        );

        // Einstellungen für die Darstellung der Holzarten
        $holz_settings = wp_parse_args(
            get_option(HOLZKATALOG_OPTIONS::HOLZARTEN_SETTINGS, []),
            [
                HOLZKATALOG_OPTIONS::SIZE       => 120,
                HOLZKATALOG_OPTIONS::ROUND       => 0,
                HOLZKATALOG_OPTIONS::LUPE       => 0,
                HOLZKATALOG_OPTIONS::DESCRIPTION => 0,
                HOLZKATALOG_OPTIONS::LAT         => 1,
                HOLZKATALOG_OPTIONS::GER         => 1,
            ]
        );

        $size        = absint($holz_settings[HOLZKATALOG_OPTIONS::SIZE]);
        $round       = !empty($holz_settings[HOLZKATALOG_OPTIONS::ROUND]);
        $lupe        = !empty($holz_settings[HOLZKATALOG_OPTIONS::LUPE]);
        $description = !empty($holz_settings[HOLZKATALOG_OPTIONS::DESCRIPTION]);
        $show_lat    = !empty($holz_settings[HOLZKATALOG_OPTIONS::LAT]);
        $show_ger    = !empty($holz_settings[HOLZKATALOG_OPTIONS::GER]);

        // Einstellungen absichern
        $card = !empty($settings[HOLZKATALOG_OPTIONS::CARD]);

        $sortierung = $settings[HOLZKATALOG_OPTIONS::SORTIERUNG] === HOLZKATALOG_OPTIONS::LAT
            ? HOLZKATALOG_OPTIONS::LAT
            : HOLZKATALOG_OPTIONS::NAME;

        $bilder      = !empty($settings[HOLZKATALOG_OPTIONS::BILDER]);
        $nurprodukte = !empty($settings[HOLZKATALOG_OPTIONS::NURPRODUKTE]);

        $spalten = max(
            1,
            min(6, absint($settings[HOLZKATALOG_OPTIONS::SPALTEN]))
        );

        // Aktive Holzarten laden
        $items = array_filter(
            HOLZKATALOG_Catalog::get_all(),
            function ($item) {
                return !empty($item['active']);
            }
        );

        /*
     * Produktzuordnung ermitteln.
     * Verwendet werden ausschließlich tatsächlich belegte
     * WooCommerce-Holzart-Terme.
     */
        $used_wood_slugs = [];

        $terms = get_terms([
            'taxonomy'   => 'pa_holzart',
            'hide_empty' => true,
            'fields'     => 'all',
        ]);

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $used_wood_slugs[] = sanitize_title($term->name);
            }
        }

        // Doppelte Einträge vermeiden
        $used_wood_slugs = array_unique($used_wood_slugs);

        /*
     * Nur Holzarten mit Produktzuordnung anzeigen,
     * sofern die entsprechende Option aktiviert ist.
     */
        if ($nurprodukte) {
            $items = array_filter(
                $items,
                function ($item) use ($used_wood_slugs) {
                    $slug = sanitize_title($item['name_de'] ?? '');

                    return in_array(
                        $slug,
                        $used_wood_slugs,
                        true
                    );
                }
            );
        }

        // Sortierung
        usort(
            $items,
            function ($a, $b) use ($sortierung) {
                if ($sortierung === HOLZKATALOG_OPTIONS::LAT) {
                    return strnatcasecmp(
                        $a['name_latin'] ?? '',
                        $b['name_latin'] ?? ''
                    );
                }

                return strnatcasecmp(
                    $a['name_de'] ?? '',
                    $b['name_de'] ?? ''
                );
            }
        );

        if (empty($items)) {
            return '<p>Keine aktiven Holzarten vorhanden.</p>';
        }

        // Grid erzeugen
        $grid_class = 'hk-wood-grid';

        if ($card) {
            $grid_class .= ' hk-material-cards';
        }

        $html = '<div class="' . esc_attr($grid_class) .
            '" style="--hk-columns:' . esc_attr($spalten) . ';">';

        foreach ($items as $item) {

            $name_de    = $item['name_de'] ?? '';
            $name_latin = $item['name_latin'] ?? '';

            // Produktzuordnung für JavaScript-Vorschau
            $has_product = in_array(
                sanitize_title($name_de),
                $used_wood_slugs,
                true
            );

            $html .= '<div 
            class="hk-wood-card" 
            data-nurprodukt="' . ($has_product ? '1' : '0') . '"
            data-name-de="' . esc_attr($name_de) . '"
            data-name-latin="' . esc_attr($name_latin) . '"
        >';

            // Bild nur bei entsprechender Einstellung
            if ($bilder) {

                $image_url = HOLZKATALOG_Storage::image_url(
                    $item['image_file'] ?? ''
                );

                if ($image_url) {
                    $image_class = 'hk-wood-image';
                    if ($round) {
                        $image_class .= ' hk-wood-image-round';
                    }
                    if ($lupe) {
                        $image_class .= ' hk-wood-zoom';
                    }
                    $html .= '<div class="hk-wood-image-wrapper">';
                    $html .= '<img
                    class="' . esc_attr($image_class) . '"
                    style="width:' . esc_attr($size) . 'px;"
                    src="' . esc_url($image_url) . '"
                    alt="' . esc_attr($name_de) . '"
                    loading="lazy">';
                    $html .= '</div>';
                }
            }

            // Deutscher Name
            if ($show_ger && $name_de !== '') {
                $html .= '<h4 style="padding:0;margin:0">'
                    . esc_html($name_de)
                    . '</h4>';
            }

            // Botanischer Name
            if ($show_lat && $name_latin !== '') {
                $html .= '<span>'
                    . esc_html($name_latin)
                    . '</span>';
            }

            // Beschreibung
            if ($description && !empty($item['description'])) {
                $html .= '<div class="hk-wood-description">'
                    . wp_kses_post($item['description'])
                    . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
