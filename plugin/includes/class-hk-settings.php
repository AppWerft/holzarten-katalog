<?php

class HK_Settings
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function add_menu()
    {
        // Einstellungen Holzfundorte
        add_submenu_page(
            'holzarten-katalog',
            'Holzfundorte',
            'Holzfundorte',
            'manage_options',
            'hk_settings_fundorte',
            [self::class, 'render_fundorte']
        );

        // Einstellungen Holzarten
        add_submenu_page(
            'holzarten-katalog',
            'Holzarten',
            'Holzarten',
            'manage_options',
            'hk_settings_holzarten',
            [self::class, 'render_holzarten']
        );

        // Automatischen ersten Menüpunkt umbenennen
        global $submenu;

        if (isset($submenu['holzarten-katalog'][0])) {
            $submenu['holzarten-katalog'][0][0] = 'Holzartenliste';
        }
    }

    public static function enqueue_scripts($hook)
    {

        // Leaflet CSS
        wp_enqueue_style(
            'leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
            [],
            '1.9.4'
        );

        // Leaflet JavaScript
        wp_enqueue_script(
            'leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );



        // Holzarten-Einstellungen
        if ($hook === 'holzartenkatalog_page_hk_settings_holzarten') {

            $js_file = HK_PLUGIN_DIR . 'assets/js/admin-holzarten-holzsettings.js';

            wp_enqueue_script(
                'hk-holzarten-holzsettings',
                HK_PLUGIN_URL . 'assets/js/admin-holzarten-holzsettings.js',
                [],
                file_exists($js_file) ? filemtime($js_file) : HK_VERSION,
                true
            );

            $css_file = HK_PLUGIN_DIR . 'assets/css/admin-holzarten-holzsettings.css';

            wp_enqueue_style(
                'hk-holzarten-settings',
                HK_PLUGIN_URL . 'assets/css/admin-holzarten-holzsettings.css',
                [],
                file_exists($css_file) ? filemtime($css_file) : HK_VERSION
            );
        }

        // Holzfundorte-Einstellungen
        if ($hook === 'holzartenkatalog_page_hk_settings_fundorte') {

            $js_file = HK_PLUGIN_DIR . 'assets/js/admin-holzarten-geosettings.js';

            wp_enqueue_script(
                'hk-holzarten-geosettings',
                HK_PLUGIN_URL . 'assets/js/admin-holzarten-geosettings.js',
                [],
                file_exists($js_file) ? filemtime($js_file) : HK_VERSION,
                true
            );

            $css_file = HK_PLUGIN_DIR . 'assets/css/admin-holzarten-geosettings.css';

            wp_enqueue_style(
                'hk-holzarten-geosettings',
                HK_PLUGIN_URL . 'assets/css/admin-holzarten-geosettings.css',
                [],
                file_exists($css_file) ? filemtime($css_file) : HK_VERSION
            );
        }
    }

    public static function render_holzarten()
    {
?>
        <div class="wrap">
            <h1>Holzarteneinstellungen in Detailanzeige</h1>
            In der Detailanzeige des Produkte kann optional die Holzart angezeigt werden.
            Folgende Einstellungen gelten für dieses Detail.
            <form method="post" action="options.php">
                <?php settings_fields('hk_holzarten_settings_group'); ?>
                <fieldset class="hk-settings-section">
                    <legend>Anzeige Holzinformationen</legend>

                    <fieldset class="hk-settings-subsection">
                        <legend>Anzeige Holzbild</legend>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Größe in Pixel</th>
                                <td>
                                    <input
                                        type="range"
                                        size="3"
                                        id="hk-wood-size"
                                        name="hk_holzarten_settings[size]"
                                        min="40"
                                        max="300"
                                        step="10"
                                        value="<?php
                                                echo esc_attr(
                                                    get_option('hk_holzarten_settings', [])['size'] ?? ''
                                                );
                                                ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hk-holzarten-round">Als Kreis darstellen?</label>
                                </th>
                                <td>
                                    <input
                                        type="checkbox"
                                        id="hk-holzarten-round"
                                        name="hk_holzarten_settings[round]"
                                        value="1"
                                        <?php checked(
                                            get_option('hk_holzarten_settings', [])['round'] ?? 0,
                                            1
                                        ); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hk-holzarten-lupe">mit Lupe?</label>
                                </th>
                                <td>
                                    <input
                                        type="checkbox"
                                        id="hk-holzarten-lupe"
                                        name="hk_holzarten_settings[lupe]"
                                        value="1"
                                        <?php checked(
                                            get_option('hk_holzarten_settings', [])['lupe'] ?? 0,
                                            1
                                        ); ?>>
                                </td>
                            </tr>
                        </table>

                    </fieldset>
                    <fieldset class="hk-settings-subsection">
                        <legend>Weitere Anzeigeoptionen</legend>

                        <table class="form-table">
                            <!-- Weitere Einstellungen -->

                        </table>
                    </fieldset>

                </fieldset>
                <?php
                $settings = get_option('hk_holzarten_settings', [
                    'size'  => 120,
                    'round' => 0,
                ]);
                $size = absint($settings['size']);
                $round = !empty($settings['round']);
                $lupe = !empty($settings['lupe']);
                submit_button('Einstellungen speichern'); ?>

                <div class="hk-preview-wrapper">
                    <p><i>Vorschau der Holzart in der Detailanzeige:</i></p>
                    <div class="hk-preview">
                        <img
                            id="hk-wood-preview"
                            class="<?php echo $lupe ? 'hk-wood-zoom' : ''; ?>"
                            src="<?php echo esc_url(HK_PLUGIN_URL . 'assets/images/liveholz.png'); ?>"
                            alt="Vorschau Holzbild in Holzurnenanzeige"
                            style="
                                width: <?php echo esc_attr($size); ?>px;
                                height: <?php echo esc_attr($size); ?>px;
                                object-fit: cover;
                                border-radius: <?php echo $round ? '50%' : '0'; ?>;
    ">
                    </div>
                </div>


            </form>
        </div>
    <?php
    }

    public static function sanitize_holzarten($input)
    {
        return [
            'size' => max(
                10,
                min(500, absint($input['size'] ?? 100))
            ),

            'round' => !empty($input['round']) ? 1 : 0,
            'lupe' => !empty($input['lupe']) ? 1 : 0
        ];
    }

    public static function register_settings()
    {
        register_setting(
            'hk_settings_group',
            'hk_map_settings',
            [
                'sanitize_callback' => [self::class, 'sanitize_map']
            ]
        );

        register_setting(
            'hk_holzarten_settings_group',
            'hk_holzarten_settings',
            [
                'sanitize_callback' => [self::class, 'sanitize_holzarten']
            ]
        );
    }


    public static function render_fundorte()
    {
        $settings = wp_parse_args(
            get_option('hk_map_settings', []),
            [
                'height'   => 200,
                'width'    => 100,
                'position' => 'center',
                'priority' => 35
            ]
        );
    ?>
        <div class="wrap">
            <h1>Holzfundortkarten – Darstellungseinstellungen</h1>
            Den Produkten kann eine Geoposition zugeordnet werden.
            <form method="post" action="options.php">
                <?php settings_fields('hk_settings_group'); ?>

                <table class="form-table">

                    <tr>
                        <th scope="row">
                            <label for="hk-map-height">Kartenhöhe</label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-height"
                                name="hk_map_settings[height]"
                                value="<?php echo esc_attr($settings['height']); ?>"
                                min="80"
                                max="300"
                                step="10">
                            <span class="description">Pixel (80–300)</span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-width">Kartenbreite</label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-width"
                                name="hk_map_settings[width]"
                                value="<?php echo esc_attr($settings['width']); ?>"
                                min="20"
                                max="80"
                                step="5">
                            <span class="description">Prozent (20%–80%)</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hk-map-radius">Eckenradius</label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-radius"
                                name="hk_map_settings[radius]"
                                value="<?php echo esc_attr($settings['radius']); ?>"
                                min="0"
                                max="50"
                                step="1">
                            <span class="description">Eckenradius in Pixeln</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hk-map-position">Ausrichtung</label>
                        </th>
                        <td>
                            <select style="width:12em"
                                id="hk-map-position"
                                name="hk_map_settings[position]">
                                <option value="left"
                                    <?php selected($settings['position'], 'left'); ?>>
                                    Links
                                </option>

                                <option value="center"
                                    <?php selected($settings['position'], 'center'); ?>>
                                    Mittig
                                </option>

                                <option value="right"
                                    <?php selected($settings['position'], 'right'); ?>>
                                    Rechts
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hk-map-priority">Position in WooCommerce</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="hk-map-priority"
                                name="hk_map_settings[priority]"
                                value="<?php echo esc_attr($settings['priority']); ?>"
                                min="1"
                                max="100">
                            <p class="description">
                                Hook-Priorität: kleinerer Wert = frühere Ausgabe.
                                Standard: 35.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Einstellungen speichern'); ?>
            </form>
        </div>
        <i>Vorschau</i>
        <div id="settings-preview-map"></div>
<?php
    }

    public static function sanitize_map($input)
    {
        return [
            'height' => max(100, min(800, absint($input['height'] ?? 200))),
            'width' => max(20, min(100, absint($input['width'] ?? 100))),

            'position' => in_array(
                $input['position'] ?? '',
                ['left', 'center', 'right'],
                true
            ) ? $input['position'] : 'center',

            'priority' => max(1, min(100, absint($input['priority'] ?? 35))),
        ];
    }
}
