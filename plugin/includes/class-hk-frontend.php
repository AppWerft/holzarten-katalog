<?php

class HK_Frontend
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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            'hk-frontend-katalog',
            HK_PLUGIN_URL . 'assets/css/frontend-katalog.css',
            [],
            HK_VERSION
        );
    }
    /**
     * Shortcode [hk_holzarten]
     */
    public function render_wood_catalog($atts = [])
    {
        // Gespeicherte Einstellungen als Standardwerte verwenden
        $defaults = wp_parse_args(
            get_option('hk_katalog_settings', []),
            [
                'sortierung' => 'name',
                'bilder'     => 1,
                'spalten'    => 3,
            ]
        );

        // Shortcode-Attribute können die Einstellungen überschreiben
        $settings = shortcode_atts(
            $defaults,
            $atts,
            'hk_holzarten'
        );

        return self::render_catalog($settings);
    }

    /**
     * Gemeinsame HTML-Ausgabe für Frontend und Admin-Vorschau
     * Für die Darstellung der Holzarten gelten die Optionen aus render_holzarten()
     */
    public static function render_catalog($settings = [])
    {
        $settings = wp_parse_args(
            $settings,
            [
                'sortierung' => 'name',
                'bilder'     => 1,
                'spalten'    => 3,

            ]
        );
        // Einstellungen für die Darstellung der Holzarten
        $holz_settings = wp_parse_args(
            get_option('hk_holzarten_settings', []),
            [
                'size'  => 120,
                'round' => 0,
                'lupe'  => 0,
            ]
        );

        $size  = absint($holz_settings['size']);
        $round = !empty($holz_settings['round']);
        $lupe  = !empty($holz_settings['lupe']);

        // Einstellungen absichern
        $sortierung = $settings['sortierung'] === 'latin'
            ? 'latin'
            : 'name';

        $bilder = $settings['bilder'] === 1;

        $spalten = max(1, min(6, absint($settings['spalten'])));

        // Aktive Holzarten laden
        $items = array_filter(
            HK_Catalog::get_all(),
            function ($item) {
                return !empty($item['active']);
            }
        );

        // Sortierung
        usort($items, function ($a, $b) use ($sortierung) {

            if ($sortierung === 'latin') {
                return strnatcasecmp(
                    $a['name_latin'] ?? '',
                    $b['name_latin'] ?? ''
                );
            }

            return strnatcasecmp(
                $a['name_de'] ?? '',
                $b['name_de'] ?? ''
            );
        });

        if (empty($items)) {
            return '<p>Keine aktiven Holzarten vorhanden.</p>';
        }

        // HTML erzeugen
        $html = '<div class="hk-wood-grid" style="--hk-columns:' . esc_attr($spalten) . ';">';

        foreach ($items as $item) {

            $name_de = $item['name_de'] ?? '';
            $name_latin = $item['name_latin'] ?? '';

            $html .= '<div class="hk-wood-card">';

            // Bild nur bei entsprechender Einstellung
            if ($bilder) {

                $image_url = HK_Storage::image_url(
                    $item['image_file'] ?? ''
                );

                if ($bilder) {

                    $image_url = HK_Storage::image_url(
                        $item['image_file'] ?? ''
                    );

                    if ($image_url) {

                        $image_class = 'hk-wood-image';

                        if ($lupe) {
                            $image_class .= ' hk-wood-zoom';
                        }

                        $html .= '<img
                            class="' . esc_attr($image_class) . '"
                            src="' . esc_url($image_url) . '"
                            alt="' . esc_attr($name_de) . '"
                            loading="lazy"
                            style="
                                width: ' . esc_attr($size) . 'px;
                                height: ' . esc_attr($size) . 'px;
                                object-fit: cover;
                                border-radius: ' . ($round ? '50%' : '0') . ';
                            ">';
                    }
                }
            }

            $html .= '<h4 style="padding:0;margin:50px 0 0 0">' . esc_html($name_de) . '</h4>';

            if ($name_latin !== '') {
                $html .= '<span>' . esc_html($name_latin) . '</span>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
