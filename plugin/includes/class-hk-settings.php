<?php

class HK_Settings
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function add_menu()
    {
        add_submenu_page(
            'holzarten-katalog',
            'Einstellungen',
            'Holzfundorte',
            'manage_options',
            'hk_settings',
            [self::class, 'render']
        );
    }

    public static function register_settings()
    {
        register_setting(
            'hk_settings_group',
            'hk_map_settings',
            [
                'sanitize_callback' => [self::class, 'sanitize']
            ]
        );
    }


    public static function render()
    {
        $settings = wp_parse_args(
            get_option('hk_map_settings', []),
            [
                'height'   => 200,
                'width'    => 100,
                'position' => 'center',
            ]
        );
?>
        <div class="wrap">
            <h1>Holzfundortkarten – Einstellungen</h1>

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

    public static function sanitize($input)
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
