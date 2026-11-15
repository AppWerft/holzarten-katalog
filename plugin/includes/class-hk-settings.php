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
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    public static function enqueue_scripts($hook)
{
    error_log('HK Settings Hook: ' . $hook);

    wp_enqueue_script(
        'hk-holzarten-settings',
        HK_PLUGIN_URL . 'assets/js/admin-holzarten-settings.js',
        [],
        time(), // Cache vorübergehend umgehen
        true
    );
}
    public static function render_holzarten()
    {
?>
        <div class="wrap">
            <h1>Holzarten – Einstellungen in Detailanzeige</h1>
            <form method="post" action="options.php">
                <?php settings_fields('hk_holzarten_settings_group'); ?>
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
                </table>
                <?php
                $settings = get_option('hk_holzarten_settings', [
                    'size'  => 120,
                    'round' => 0,
                ]);

                $size = absint($settings['size']);
                $round = !empty($settings['round']);
                ?>

                <div class="hk-preview-wrapper">
                    <p><strong>Vorschau der Holzart in der Detailanzeige</strong></p>

                    <div class="hk-preview">
                        <img
                            id="hk-wood-preview"
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
                <?php submit_button('Einstellungen speichern'); ?>

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

            <form method="post" action="options.php">
                <?php settings_fields('hk_settings_group'); ?>

                <table class="form-table">

                    <tr>
                        <th scope="row">
                            <label for="hk-map-height">Kartenhöhe</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="hk-map-height"
                                name="hk_map_settings[height]"
                                value="<?php echo esc_attr($settings['height']); ?>"
                                min="100"
                                max="800"
                                step="10">
                            <span class="description">Pixel (100–800)</span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-width">Kartenbreite</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="hk-map-width"
                                name="hk_map_settings[width]"
                                value="<?php echo esc_attr($settings['width']); ?>"
                                min="20"
                                max="100"
                                step="5">
                            <span class="description">Prozent (20–100)</span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="hk-map-position">Ausrichtung</label>
                        </th>
                        <td>
                            <select
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
