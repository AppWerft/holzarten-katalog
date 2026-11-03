    <?php

    if (!defined('ABSPATH')) {
        exit;
    }

    class HK_WC_Origin
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
            add_action(
                'woocommerce_product_options_general_product_data',
                [$this, 'render_fields']
            );

            add_action(
                'woocommerce_admin_process_product_object',
                [$this, 'save_fields']
            );
        
        }
    
    

    /**
     * Herkunftskoordinaten im Produkteditor anzeigen.
     */
    public function render_fields()
    {
        error_log('HK_WC_Origin::render_fields aufgerufen');
        global $post;

        $product = wc_get_product($post->ID);

        if (!$product) {
            return;
        }

        echo '<div class="options_group">';

        woocommerce_wp_text_input([
            'id'          => '_hk_origin_lat',
            'label'       => 'Breitengrad',
            'type'        => 'number',
            'custom_attributes' => [
                'step' => 'any',
            ],
        ]);

        woocommerce_wp_text_input([
            'id'          => '_hk_origin_lng',
            'label'       => 'Längengrad',
            'type'        => 'number',
            'custom_attributes' => [
                'step' => 'any',
            ],
        ]);

        // Ortssuche
        echo '<p class="form-field">';
        echo '<label for="hk-origin-search-input">Ortssuche</label>';
        echo '<input type="search" id="hk-origin-search-input" ';
        echo 'placeholder="Ort oder Adresse eingeben ..." style="width:50%;">';
        echo '</p>';

        echo '<div id="hk-origin-search-results" style="margin-left:150px;"></div>';

        echo '<p class="form-field">';
        echo '<label>&nbsp;</label>';
        echo '<button type="button" class="button" id="hk-origin-picker">';
        echo 'Herkunft auf Karte wählen';
        echo '</button>';
        echo '</p>';

        echo '<div id="hk-origin-map" style="display:none;height:400px;margin:10px 12px;"></div>';

        echo '</div>';
    }


        /**
         * Herkunftskoordinaten speichern.
         */
        public function save_fields($product)
        {
            $coordinates = [
                '_hk_origin_lat' => ['min' => -90, 'max' => 90],
                '_hk_origin_lng' => ['min' => -180, 'max' => 180],
            ];

            foreach ($coordinates as $key => $range) {
                if (!isset($_POST[$key])) {
                    continue;
                }

                $value = trim(wp_unslash($_POST[$key]));

                if ($value === '' || !is_numeric($value)) {
                    $product->delete_meta_data($key);
                    continue;
                }

                $value = (float) $value;

                if ($value >= $range['min'] && $value <= $range['max']) {
                    $product->update_meta_data($key, $value);
                }
            }
        }
    }