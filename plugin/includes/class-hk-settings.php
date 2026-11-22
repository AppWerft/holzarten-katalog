<?php

class HK_Settings
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
        add_action('wp_ajax_hk_preview_katalog', [self::class, 'ajax_preview_katalog']);
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
        add_submenu_page(
            'holzarten-katalog',
            'Holzlistendarstellung',
            'Holzlistendarstellung',
            'manage_options',
            'hk_settings_katalog',
            [self::class, 'render_katalog']
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
            'hk-leaflet',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
            [],
            '1.9.4',
            true
        );
        wp_enqueue_script(
            'hk-d3',
            HK_PLUGIN_URL . 'assets/vendor/d3/d3v5.js',
            [],
            HK_VERSION,
            true
        );
        // PingLayer
        wp_enqueue_script(
            'hk-ping-layer',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet-d3.js',
            ['hk-leaflet', 'hk-d3'],
            HK_VERSION,
            true
        );
        // Leaflet JavaScript
        wp_enqueue_script(
            'hk-leaflet-providers',
            HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet-providers.js',
            ['hk-leaflet'],
            HK_VERSION,
            true
        );
        // CSS
        if ($hook === 'holzartenkatalog_page_hk_settings_katalog') {
            wp_enqueue_style(
                'hk-frontend-katalog',
                HK_PLUGIN_URL . 'assets/css/frontend-katalog.css',
                [],
                filemtime(HK_PLUGIN_DIR . 'assets/css/frontend-katalog.css')
            );
        }

        if ($hook === 'holzartenkatalog_page_hk_settings_katalog') {
            wp_enqueue_script(
                'hk-admin',
                HK_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                HK_VERSION,
                true
            );
        }

        // Holzarten-Einstellungen
        if ($hook === 'holzartenkatalog_page_hk_settings_holzarten') {

            $js_file = HK_PLUGIN_DIR . 'assets/js/admin-holzarten-settings.js';
            $css_file = HK_PLUGIN_DIR . 'assets/css/admin-holzarten-settings.css';

            wp_enqueue_style(
                'hk-holzarten-settings',
                HK_PLUGIN_URL . 'assets/css/admin-holzarten-settings.css',
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
                ['hk-leaflet', 'hk-leaflet-providers'],
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

    private static function get_map_providers(): array
    {
        return [
            'OpenStreetMap.Mapnik' => 'OpenStreetMap – Standard',
            'OpenStreetMap.DE'     => 'OpenStreetMap – Deutsch',
            'OpenStreetMap.HOT'    => 'OpenStreetMap – Humanitarian',
            'OpenTopoMap'          => 'OpenTopoMap – Gelände',
            'Esri.WorldTopoMap'    => 'Esri – Topografisch',
            'Esri.WorldImagery'    => 'Esri – Satellit',
            'Esri.WorldStreetMap'  => 'Esri – Straßenkarte',
            'Stadia.AlidadeSmooth' => 'Stadia Alidade Smooth',
            'Stadia.AlidadeSmoothDark' => 'Stadia Alidade SmoothDark',
            'Stadia.StamenTonerDark' => 'Stadia Stamen TonerDark',
            'Stadia.StamenWatercolor' => 'Stadia Stamen Watercolor',
            'Stadia.StamenTerrain' => 'Stadia Stamen Terrain'
        ];
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
                        <legend>Textanzeigen</legend>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="hk-holzarten-description">Beschreibungstext?</label>
                                </th>
                                <td>
                                    <input
                                        type="checkbox"
                                        id="hk-holzarten-description"
                                        name="hk_holzarten_settings[description]"
                                        value="1"
                                        <?php checked(
                                            get_option('hk_holzarten_settings', [])['description'] ?? 0,
                                            1
                                        ); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hk-holzarten-lat">lateinischer Name?</label>
                                </th>
                                <td>
                                    <input
                                        type="checkbox"
                                        id="hk-holzarten-lat"
                                        name="hk_holzarten_settings[lat]"
                                        value="1"
                                        <?php checked(
                                            get_option('hk_holzarten_settings', [])['lat'] ?? 0,
                                            1
                                        ); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hk-holzarten-ger">deutscher Name?</label>
                                </th>
                                <td>
                                    <input
                                        type="checkbox"
                                        id="hk-holzarten-description"
                                        name="hk_holzarten_settings[ger]"
                                        value="1"
                                        <?php checked(
                                            get_option('hk_holzarten_settings', [])['ger'] ?? 0,
                                            1
                                        ); ?>>
                                </td>
                            </tr>
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


    public static function sanitize_katalog($input)
    {
        return [
            'sortierung' => in_array(
                $input['sortierung'] ?? '',
                ['name', 'latin'],
                true
            ) ? $input['sortierung'] : 'name',

            'bilder' => !empty($input['bilder']) ? 1 : 0,
            'card' => !empty($input['card']) ? 1 : 0,
            'nurprodukte' => !empty($input['nurprodukte']) ? 1 : 0,
            'spalten' => max(1, min(6, absint($input['spalten'] ?? 3))),
        ];
    }

    public static function ajax_preview_katalog()
    {
        check_ajax_referer('hk_katalog_preview', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(-1, 403);
        }

        $input = [];
        if (
            isset($_POST['hk_katalog_settings'])
            && is_array($_POST['hk_katalog_settings'])
        ) {
            $input = map_deep(
                wp_unslash($_POST['hk_katalog_settings']),
                'sanitize_text_field'
            );
        }

        $settings = self::sanitize_katalog($input);


        wp_send_json_success([
            'html' => HK_Frontend::render_catalog($settings)
        ]);
    }

    public static function sanitize_holzarten($input)
    {
        return [
            'size' => max(
                10,
                min(500, absint($input['size'] ?? 100))
            ),

            'round' => !empty($input['round']) ? 1 : 0,
            'lupe' => !empty($input['lupe']) ? 1 : 0,
            'description' => !empty($input['description']) ? 1 : 0,
            'lat' => !empty($input['lat']) ? 1 : 0,
            'ger' => !empty($input['ger']) ? 1 : 0

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
        register_setting(
            'hk_katalog_settings_group',
            'hk_katalog_settings',
            [
                'sanitize_callback' => [self::class, 'sanitize_katalog']
            ]
        );
    }

    public static function render_katalog()
    {
        $settings = wp_parse_args(
            get_option('hk_katalog_settings', []),
            [
                'sortierung' => 'name',
                'bilder'     => 1,
                'nurprodukte' => 0,
                'spalten'    => 3

            ]
        );
    ?>
        <div class="wrap">
            <h1>Holzartenkatalog – Darstellung</h1>
            <p>Einstellungen für die öffentliche Holzartenübersicht. Für die Darstellung der Hölzer gelten die Einstellungen aus HOLZARTEN</p>
            <form id="hk-katalog-settings-form" method="post" action="options.php">
                <?php settings_fields('hk_katalog_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Sortierung</th>
                        <td>
                            <select name="hk_katalog_settings[sortierung]">
                                <option value="name"
                                    <?php selected($settings['sortierung'], 'name'); ?>>
                                    Deutscher Name
                                </option>
                                <option value="latin"
                                    <?php selected($settings['sortierung'], 'latin'); ?>>
                                    Botanischer Name
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Bilder anzeigen</th>
                        <td>
                            <input
                                type="checkbox"
                                name="hk_katalog_settings[bilder]"
                                value=1
                                <?php checked($settings['bilder'], 1); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">nur Hölzer für die es Produkte gibt</th>
                        <td>
                            <input
                                type="checkbox"
                                name="hk_katalog_settings[nurprodukte]"
                                value=1
                                <?php checked($settings['nurprodukte'], 1); ?>>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Anzahl der Spalten</th>
                        <td>
                            <select name="hk_katalog_settings[spalten]">
                                <?php for ($i = 1; $i <= 6; $i++) : ?>
                                    <option
                                        value="<?php echo absint($i); ?>"
                                        <?php selected($settings['spalten'], $i); ?>>
                                        <?php echo absint($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Darstellung als<br>Material Design Cards</th>
                        <td>
                            <input
                                type="checkbox"
                                name="hk_katalog_settings[card]"
                                value="1"
                                <?php checked($settings['card'] ?? 0, 1); ?>>

                        </td>
                    </tr>
                </table>
                <?php submit_button('Einstellungen speichern'); ?>
                <button type="button" class="button" id="hk-settings-cancel">
                    Abbrechen
                </button>

            </form>

            <h2>Vorschau der Holzartenübersicht:</h2>
            <p>Wird erst nach Abspeichern aktualisiert.</p>
            <div id="hk-katalog-preview">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is generated internally; dynamic values are escaped in render_catalog().
                echo HK_Frontend::render_catalog($settings);
                ?>
            </div>
        </div>
    <?php
    }


    public static function render_fundorte()
    {
        $settings = wp_parse_args(
            get_option('hk_map_settings', []),
            [
                'height'        => 200,
                'width'         => 100,
                'radius'        => 0,
                'position'      => 'center',
                'priority'      => 35,
                'tile_provider' => 'OpenStreetMap.Mapnik',
                'pingcolor'     => '#4285F4'
            ]
        );

        $providers = self::get_map_providers();
        $selected = $settings['tile_provider'];
    ?>
        <div class="wrap">
            <h1>Holzfundortkarten – Darstellungseinstellungen</h1>
            Den Produkten kann eine Geoposition zugeordnet werden.
            <form method="post" action="options.php">
                <?php settings_fields('hk_settings_group'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hk-tile-provider">Kartennanbieter</label>
                        </th>
                        <td>
                            <select
                                name="hk_map_settings[tile_provider]"
                                id="hk-tile-provider">
                                <?php foreach ($providers as $key => $label) : ?>
                                    <option
                                        value="<?php echo esc_attr($key); ?>"
                                        <?php selected($selected, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="description">Beachte eventuelle Lizenzbeschränkungen!</span>
                        </td>
                    </tr>
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
                                max="400"
                                step="10">
                            <span class="description">Pixel (80–400)</span>
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
                                max="100"
                                step="5">
                            <span class="description">Prozent (20%–100%)</span>
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
                    <tr>
                        <th scope="row">
                            <label for="hk-map-priority">Farbe des Geostandortes</label>
                        </th>
                        <td>
                            <input
                                type="color"
                                id="hk-map-pingcolor"
                                name="hk_map_settings[pingcolor]"
                                value="<?php echo esc_attr($settings['pingcolor']); ?>"
                                max="100">
                            <p class="description">

                            </p>
                        </td>
                    </tr>


                </table>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="button" id="hk-settings-cancel">
                        Abbrechen
                    </button>
                    <?php submit_button('Einstellungen speichern'); ?>
                    
                </div>
            </form>
        </div>
        <i>Vorschau</i>
        <div id="settings-preview-wrapper">
            <div id="settings-preview-map"></div>
        </div>
<?php
    }

    public static function sanitize_map($input)
    {
        $input = is_array($input) ? $input : [];

        $providers = self::get_map_providers();

        $provider = sanitize_text_field(
            $input['tile_provider'] ?? ''
        );

        if (!array_key_exists($provider, $providers)) {
            $provider = 'OpenStreetMap.Mapnik';
        }

        return [
            'height' => max(100, min(800, absint($input['height'] ?? 200))),
            'width' => max(20, min(100, absint($input['width'] ?? 100))),
            'radius' => max(0, min(50, absint($input['radius'] ?? 0))),

            'position' => in_array(
                $input['position'] ?? '',
                ['left', 'center', 'right'],
                true
            ) ? $input['position'] : 'center',

            'priority' => max(1, min(100, absint($input['priority'] ?? 35))),
            'pingcolor' => sanitize_hex_color(
                $input['pingcolor'] ?? '#4285F4'
            ) ?: '#4285F4',
            'tile_provider' => $provider,
        ];
    }
}
