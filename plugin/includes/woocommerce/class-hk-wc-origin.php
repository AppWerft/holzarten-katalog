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
            error_log(
                'HK_ORIGIN: ' . wp_debug_backtrace_summary()
            );
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
echo '<div class="form-field" style="
    display:flex;
    align-items:center;
    gap:16px;
">';

// Label
echo '<label for="hk-origin-search-input" style="flex:0 0 10px;margin: 10px;">';
echo 'Ortssuche';
echo '</label>';

// Container für Eingabefeld und Trefferliste
echo '<div style="position:relative;width:60%;">';

// Input
echo '<input type="search"
    id="hk-origin-search-input"
    placeholder="Ort oder Adresse eingeben ..."
    style="width:400px">';

// Trefferliste
echo '<div id="hk-origin-search-results" style="
    top:100%;
    left:0;
    right:0;
    z-index:100000;
    color:white;
    background:#333;
    border:1px solid #ccc;
    border-top:none;
    max-height:270px;
    overflow-y:auto;
    box-shadow:0 4px 8px rgba(0,0,0,0.15);
"></div>';

echo '</div>';
echo '</div>';

// Button
echo '<p class="form-field">';
echo '<label>&nbsp;</label>';
echo '<button style="display:none;height:0"
    type="button"
    class="button"
    id="hk-origin-picker">';
echo 'Herkunft auf Karte wählen';
echo '</button>';
echo '</p>';

echo '<div id="hk-origin-map"
    style="display:none;height:400px;margin:10px 12px;">
</div>';


            
            
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
