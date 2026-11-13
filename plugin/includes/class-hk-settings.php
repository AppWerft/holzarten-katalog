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
            'Einstellungen',
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
?>
        <div class="wrap">
            <h1>Holzarten – Einstellungen</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hk_settings_group');
                submit_button();
                ?>
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
        ];
    }
}
