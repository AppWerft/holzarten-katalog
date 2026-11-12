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
        add_shortcode(
            'hk_holzarten',
            [$this, 'render_wood_catalog']
        );
    }


    /**
     * Öffentliche Übersicht aller aktiven Holzarten.
     */
    public function render_wood_catalog($atts = [])
    {
        $items = HK_Catalog::get_all();

        // Nur aktive Holzarten berücksichtigen.
        $items = array_filter($items, function ($item) {
            return !empty($item['active']);
        });

        // Alphabetisch nach deutschem Namen sortieren.
        usort($items, function ($a, $b) {
            return strnatcasecmp(
                $a['name_de'],
                $b['name_de']
            );
        });

        if (empty($items)) {
            return '<p>Keine aktiven Holzarten vorhanden.</p>';
        }

        ob_start();
        echo '<div class="hk-wood-grid">';
        foreach ($items as $item) {
            echo '<div class="hk-wood-card">';
            $image_url='';
            // Bildausgabe folgt über die Plugin-Bildverwaltung.

            echo '<h3>' . esc_html($item['name_de']) . '</h3>';

            if (!empty($item['name_latin'])) {
                echo '<p>' . esc_html($item['name_latin']) . '</p>';
            }
            echo '<img
    class="hk-wood-image"
    src="' . esc_url($image_url) . '"
    alt="' . esc_attr($item['name_de']) . '"
    loading="lazy"
>';

            echo '</div>';
        }

        echo '</div>';

        return ob_get_clean();
    }
}
