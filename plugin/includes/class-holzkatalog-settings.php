<?php

class HOLZKATALOG_Settings
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
            'holzarten-katalog',             // Slug der übergeordneten Admin-Menüseite, unter der das Untermenü erscheint.
            'Holzfundorte',                   // Seitentitel: Wird im Browser bzw. als Titel der Admin-Seite verwendet.
            'Holzfundorte',                   // Menütext: Bezeichnung des Untermenüpunkts in der WordPress-Administration.
            'manage_options',                 // Erforderliche Berechtigung: Nur Benutzer mit dieser Fähigkeit dürfen die Seite aufrufen (standardmäßig Administratoren).
            'hk_settings_fundorte',           // Eindeutiger Slug der Untermenüseite, über den WordPress sie intern identifiziert.
            [self::class, 'render_fundorte']  // Callback: Statische Methode render_fundorte() dieser Klasse, die den Seiteninhalt ausgibt.
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
        // Holzlistendarstellung
        if ($hook === HOLZKATALOG_HOOKS::HOLZARTENKATALOG_PAGE_HK_SETTING_KATALOG) {

            $css_file = HOLZKATALOG_PLUGIN_DIR
                . HOLZKATALOG_CSS::FRONTEND_KATALOG;

            $js_file = HOLZKATALOG_JS::ADMIN_HOLZARTEN_KATALOG;
            $js_path = HOLZKATALOG_PLUGIN_DIR . $js_file;
            $js_url = HOLZKATALOG_PLUGIN_URL . $js_file;
            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_HOLZARTEN,
                $js_url,
                [HOLZKATALOG_SCRIPT_HANDLES::JQUERY],
                file_exists($js_path)
                    ? filemtime($js_path)
                    : HOLZKATALOG_VERSION,
                true
            );
            wp_enqueue_style(
                HOLZKATALOG_STYLE_HANDLES::FRONTEND_KATALOG,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::FRONTEND_KATALOG,
                [],
                file_exists($css_file)
                    ? filemtime($css_file)
                    : HOLZKATALOG_VERSION
            );

            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::ADMIN,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::HOLZKATLOG_ADMIN_JS,
                [HOLZKATALOG_SCRIPT_HANDLES::JQUERY],
                HOLZKATALOG_VERSION,
                true
            );
        }

        // Holzarteneinstellungen
        if ($hook === HOLZKATALOG_HOOKS::HOLZARTENKATALOG_PAGE_HK_SETTINGS_HOLZARTEN) {

            $js_file = HOLZKATALOG_PLUGIN_DIR
                . HOLZKATALOG_JS::ADMIN_HOLZARTEN_SETTINGS;

            $css_file = HOLZKATALOG_PLUGIN_DIR
                . HOLZKATALOG_CSS::ADMIN_HOLZARTEN_SETTINGS;

            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_HOLZARTEN_SETTING,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::ADMIN_HOLZARTEN_SETTINGS,
                [HOLZKATALOG_SCRIPT_HANDLES::JQUERY],
                file_exists($js_file)
                    ? filemtime($js_file)
                    : HOLZKATALOG_VERSION,
                true
            );

            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_HOLZARTEN,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::ADMIN_HOLZARTEN_KATALOG,
                [HOLZKATALOG_SCRIPT_HANDLES::JQUERY],
                file_exists($js_file)
                    ? filemtime($js_file)
                    : HOLZKATALOG_VERSION,
                true
            );

            wp_enqueue_style(
                HOLZKATALOG_STYLE_HANDLES::HOLZCAT_HOLZARTEN_SETTING,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::ADMIN_HOLZARTEN_SETTINGS,
                [],
                file_exists($css_file)
                    ? filemtime($css_file)
                    : HOLZKATALOG_VERSION
            );
        }

        // Holzfundorteinstellungen
        if ($hook === HOLZKATALOG_HOOKS::HOLZARTENKATALOG_PAGE_HK_SETTINGS_FUNDORTE) {

            // Leaflet CSS
            wp_enqueue_style(
                HOLZKATALOG_STYLE_HANDLES::LEAFLET,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::LEAFLET,
                [],
                '1.9.4'
            );

            // Leaflet JavaScript
            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::LEAFLET,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::LEAFLET_JS,
                [],
                '1.9.4',
                true
            );

            // D3 v5
            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_D3,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::D3V5,
                [],
                HOLZKATALOG_VERSION,
                true
            );

            // PingLayer
            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_PINGLAYER,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::PINGLAYER,
                [HOLZKATALOG_SCRIPT_HANDLES::LEAFLET, HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_D3],
                HOLZKATALOG_VERSION,
                true
            );

            // Tile Provider
            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_LEAFLET_PROVIDERS,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::LEAFLET_PROVIDERS,
                [HOLZKATALOG_SCRIPT_HANDLES::LEAFLET],
                HOLZKATALOG_VERSION,
                true
            );

            $js_file = HOLZKATALOG_PLUGIN_DIR
                . HOLZKATALOG_JS::ADMIN_HOLZARTEN_GEOSETTINGS;

            wp_enqueue_script(
                HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_HOLZARTEN_GEOSETTINGS_JS,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_JS::ADMIN_HOLZARTEN_GEOSETTINGS,
                [HOLZKATALOG_SCRIPT_HANDLES::LEAFLET, HOLZKATALOG_SCRIPT_HANDLES::HOLZCAT_LEAFLET_PROVIDERS],
                file_exists($js_file)
                    ? filemtime($js_file)
                    : HOLZKATALOG_VERSION,
                true
            );

            $css_file = HOLZKATALOG_PLUGIN_DIR
                . HOLZKATALOG_CSS::ADMIN_HOLZARTEN_SETTINGS;

            wp_enqueue_style(
                HOLZKATALOG_STYLE_HANDLES::HOLZCAT_HOLZARTEN_GEOSETTINGS_CSS,
                HOLZKATALOG_PLUGIN_URL . HOLZKATALOG_CSS::ADMIN_HOLZARTEN_SETTINGS,
                [],
                file_exists($css_file)
                    ? filemtime($css_file)
                    : HOLZKATALOG_VERSION
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
                $size = absint($settings[HOLZKATALOG_OPTIONS::SIZE]);
                $round = !empty($settings[HOLZKATALOG_OPTIONS::ROUND]);
                $lupe = !empty($settings[HOLZKATALOG_OPTIONS::LUPE]);
                submit_button('Einstellungen speichern'); ?>

                <div class="hk-preview-wrapper">
                    <p><i>Vorschau der Holzart in der Detailanzeige:</i></p>
                    <div class="hk-preview">
                        <img
                            id="hk-wood-preview"
                            class="<?php echo $lupe ? 'hk-wood-zoom' : ''; ?>"
                            src="<?php echo esc_url(HOLZKATALOG_URL . 'assets/images/liveholz.webp'); ?>"
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
                $input[HOLZKATALOG_OPTIONS::SORTIERUNG] ?? '',
                ['name', 'latin'],
                true
            ) ? $input[HOLZKATALOG_OPTIONS::SORTIERUNG] : 'name',

            'bilder' => !empty($input[HOLZKATALOG_OPTIONS::BILDER]) ? 1 : 0,
            'card' => !empty($input[HOLZKATALOG_OPTIONS::CARD]) ? 1 : 0,
            'nurprodukte' => !empty($input[HOLZKATALOG_OPTIONS::NURPRODUKTE]) ? 1 : 0,
            'spalten' => max(1, min(6, absint($input[HOLZKATALOG_OPTIONS::SPALTEN] ?? 3))),
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
            'html' => HOLZKATALOG_Frontend::render_catalog($settings)
        ]);
    }

    public static function sanitize_holzarten($input)
    {
        return [
            'size' => max(
                10,
                min(500, absint($input[HOLZKATALOG_OPTIONS::SIZE] ?? 100))
            ),

            'round' => !empty($input[HOLZKATALOG_OPTIONS::ROUND]) ? 1 : 0,
            'lupe' => !empty($input[HOLZKATALOG_OPTIONS::LUPE]) ? 1 : 0,
            'description' => !empty($input[HOLZKATALOG_OPTIONS::DESCRIPTION]) ? 1 : 0,
            'lat' => !empty($input[HOLZKATALOG_OPTIONS::LAT]) ? 1 : 0,
            'ger' => !empty($input[HOLZKATALOG_OPTIONS::GER]) ? 1 : 0

        ];
    }

    public static function register_settings()
    {
        register_setting(
            'hk_settings_group',
            HOLZKATALOG_OPTIONS::MAP_SETTINGS,
            [
                'sanitize_callback' => [self::class, 'sanitize_map']
            ]
        );

        register_setting(
            'hk_holzarten_settings_group',
            HOLZKATALOG_OPTIONS::HOLZARTEN_SETTINGS,
            [
                'sanitize_callback' => [self::class, 'sanitize_holzarten']
            ]
        );
        register_setting(
            'hk_katalog_settings_group',
            HOLZKATALOG_OPTIONS::KATALOG_SETTINGS,
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
                                    <?php selected($settings[HOLZKATALOG_OPTIONS::SORTIERUNG], 'name'); ?>>
                                    Deutscher Name
                                </option>
                                <option value="latin"
                                    <?php selected($settings[HOLZKATALOG_OPTIONS::SORTIERUNG], 'latin'); ?>>
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
                                <?php checked($settings[HOLZKATALOG_OPTIONS::BILDER], 1); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">nur Hölzer für die es Produkte gibt</th>
                        <td>
                            <input
                                type="checkbox"
                                name="hk_katalog_settings[nurprodukte]"
                                value=1
                                <?php checked($settings[HOLZKATALOG_OPTIONS::NURPRODUKTE], 1); ?>>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Anzahl der Spalten</th>
                        <td>
                            <select name="hk_katalog_settings[spalten]">
                                <?php for ($i = 1; $i <= 6; $i++) : ?>
                                    <option
                                        value="<?php echo absint($i); ?>"
                                        <?php selected($settings[HOLZKATALOG_OPTIONS::SPALTEN], $i); ?>>
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
                                <?php checked($settings[HOLZKATALOG_OPTIONS::CARD] ?? 0, 1); ?>>

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

            <h2>Vorschau der Holzartenübersicht:</h2>
            <p>Wird erst nach Abspeichern aktualisiert.</p>
            <div id="hk-katalog-preview">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is generated internally; dynamic values are escaped in render_catalog().
                echo HOLZKATALOG_Frontend::render_catalog($settings);
                ?>
            </div>
        </div>
    <?php
    }



    public static function render_fundorte()
    {
        $settings = wp_parse_args(
            get_option(HOLZKATALOG_OPTIONS::MAP_SETTINGS, []),
            [
                'height'        => 200,
                'width'         => 100,
                'radius'        => 0,
                HOLZKATALOG_OPTIONS::POSITION => 'center',
                'priority'      => 35,
                'tile_provider' => 'OpenStreetMap.Mapnik',
                'pingcolor'     => '#4285F4',
            ]
        );

        $providers = self::get_map_providers();
        $selected  = $settings[HOLZKATALOG_OPTIONS::TILE_PROVIDER];
    ?>

        <div class="wrap">

            <h1>Holzfundortkarten – Darstellungseinstellungen</h1>

            <p>
                Den Produkten kann eine Geoposition zugeordnet werden.
                In diesem Formular kann die Darstellung für alle Produkte
                konfiguriert werden.
            </p>

            <form method="post" action="options.php">

                <?php settings_fields('hk_settings_group'); ?>

                <table class="form-table">

                    <tr>
                        <th scope="row">
                            <label for="hk-tile-provider">
                                Kartenanbieter
                            </label>
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

                            <p class="description">
                                Beachte die jeweiligen Lizenz- und Nutzungsbedingungen
                                des Kartenanbieters.
                            </p>
                        </td>
                    </tr>

                    <tr id="hk-map-apikey-wrapper" style="display:none">
                        <th scope="row">Eingabe Schlüssel</th>
                        <td>
                            <input
                                type="hidden"
                                name="hk_map_settings[apikey_key]"
                                id="hk-map-apikey-key-form"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::APIKEY_KEY] ?? ''); ?>">
                            <input
                                type="text"
                                readonly
                                disabled
                                size="6"
                                id="hk-map-apikey-key"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::APIKEY_KEY] ?? ''); ?>">&nbsp;=&nbsp;
                            <input
                                size="20"
                                type="text"
                                id="hk-map-apikey-value"
                                name="hk_map_settings[apikey]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::APIKEY] ?? ''); ?>">
                        </td>
                    </tr>


                    <tr>
                        <th scope="row">
                            <label for="hk-map-height">
                                Kartenhöhe
                            </label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-height"
                                name="hk_map_settings[height]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::HEIGHT]); ?>"
                                min="80"
                                max="400"
                                step="10">
                            <span class="description">
                                Pixel (80–400)
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-width">
                                Kartenbreite
                            </label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-width"
                                name="hk_map_settings[width]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::WIDTH]); ?>"
                                min="20"
                                max="100"
                                step="5">
                            <span class="description">
                                Prozent (20–100 %)
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-radius">
                                Eckenradius
                            </label>
                        </th>
                        <td>
                            <input
                                type="range"
                                id="hk-map-radius"
                                name="hk_map_settings[radius]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::RADIUS]); ?>"
                                min="0"
                                max="50"
                                step="1">
                            <span class="description">
                                Pixel (0–50)
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-position">
                                Ausrichtung
                            </label>
                        </th>
                        <td>
                            <select
                                id="hk-map-position"
                                name="hk_map_settings[position]"
                                style="width: 12em;">
                                <option
                                    value="left"
                                    <?php selected($settings[HOLZKATALOG_OPTIONS::POSITION], 'left'); ?>>
                                    Links
                                </option>

                                <option
                                    value="center"
                                    <?php selected($settings[HOLZKATALOG_OPTIONS::POSITION], 'center'); ?>>
                                    Mittig
                                </option>

                                <option
                                    value="right"
                                    <?php selected($settings[HOLZKATALOG_OPTIONS::POSITION], 'right'); ?>>
                                    Rechts
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-priority">
                                Position in WooCommerce
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="hk-map-priority"
                                name="hk_map_settings[priority]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::PRIORITY]); ?>"
                                min="1"
                                max="100">
                            <p class="description">
                                Hook-Priorität: Ein kleinerer Wert bedeutet eine
                                frühere Ausgabe. Standard: 35.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-pingcolor">
                                Farbe des Geostandortes
                            </label>
                        </th>
                        <td>
                            <input
                                type="color"
                                id="hk-map-pingcolor"
                                name="hk_map_settings[pingcolor]"
                                value="<?php echo esc_attr($settings[HOLZKATALOG_OPTIONS::PINGCOLOR]); ?>">
                            <p class="description">
                                Farbe des Markierungseffekts auf der Karte.
                            </p>
                        </td>
                    </tr>

                </table>

                <div style="display: flex; align-items: center; gap: 10px;">

                    <button
                        onclick="window.location.reload()"
                        type="button"
                        class="button"
                        id="hk-settings-cancel">
                        Abbrechen
                    </button>

                    <?php submit_button('Einstellungen speichern'); ?>

                </div>

            </form>

            <fieldset>
<legend>Vorschau</legend>
            <div id="settings-preview-wrapper">
                <div id="settings-preview-map"></div>
            </div>
            </fieldset>                      
        </div>

<?php
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
            'Stadia.AlidadeSatellite'=>'Stadia Alidade Satellite',
            'Stadia.AlidadeSmooth' => 'Stadia Alidade Smooth',
            'Stadia.AlidadeSmoothDark' => 'Stadia Alidade SmoothDark',
            'Stadia.StamenTonerDark' => 'Stadia Stamen TonerDark',
            'Stadia.StamenWatercolor' => 'Stadia Stamen Watercolor',
            'Stadia.StamenTerrain' => 'Stadia Stamen Terrain',
            'Thunderforest.Landscape' => 'Thunderforest Landscape'
        ];
    }



    public static function sanitize_map($input)
    {
        $input = is_array($input) ? $input : [];

        $providers = self::get_map_providers();

        $provider = sanitize_text_field(
            $input[HOLZKATALOG_OPTIONS::TILE_PROVIDER] ?? ''
        );

        if (!array_key_exists($provider, $providers)) {
            $provider = 'OpenStreetMap.Mapnik';
        }

        // API-Key-Platzhalter
        $allowed_api_keys = [
            'key',
            'apikey',
            'apiKey',
            'accessToken',
            'subscriptionKey',
        ];

        $apikey_key = sanitize_text_field(
            $input[HOLZKATALOG_OPTIONS::APIKEY_KEY] ?? ''
        );

        $apikey_key = in_array(
            $apikey_key,
            $allowed_api_keys,
            true
        ) ? $apikey_key : '';

        // API-Key
        $apikey = sanitize_text_field(
            trim($input[HOLZKATALOG_OPTIONS::APIKEY] ?? '')
        );

        if (!$apikey_key) {
            $apikey = '';
        }

        return [
            HOLZKATALOG_OPTIONS::HEIGHT => max(100, min(800, absint($input[HOLZKATALOG_OPTIONS::HEIGHT] ?? 200))),
            HOLZKATALOG_OPTIONS::WIDTH => max(20, min(100, absint($input[HOLZKATALOG_OPTIONS::WIDTH] ?? 100))),
            HOLZKATALOG_OPTIONS::RADIUS => max(0, min(50, absint($input[HOLZKATALOG_OPTIONS::RADIUS] ?? 0))),

            HOLZKATALOG_OPTIONS::POSITION => in_array(
                $input[HOLZKATALOG_OPTIONS::POSITION] ?? '',
                ['left', 'center', 'right'],
                true
            ) ? $input[HOLZKATALOG_OPTIONS::POSITION] : 'center',

            HOLZKATALOG_OPTIONS::PRIORITY => max(1, min(100, absint($input[HOLZKATALOG_OPTIONS::PRIORITY] ?? 35))),

            HOLZKATALOG_OPTIONS::PINGCOLOR => sanitize_hex_color(
                $input[HOLZKATALOG_OPTIONS::PINGCOLOR] ?? '#4285F4'
            ) ?: '#4285F4',

            HOLZKATALOG_OPTIONS::TILE_PROVIDER => $provider,
            HOLZKATALOG_OPTIONS::APIKEY_KEY => $apikey_key,
            HOLZKATALOG_OPTIONS::APIKEY => $apikey,
        ];
    }
}
