
<?php
if (!defined('ABSPATH')) {
    exit;
}

class HK_Storage
{
    public static function directory()
    {
        $upload = wp_upload_dir();

        return trailingslashit($upload['basedir'])
            . 'holzarten-daten/';
    }

    public static function activate()
    {
        $base = self::directory();

        wp_mkdir_p($base . 'images/');
        wp_mkdir_p($base . 'backups/');

        $files = [
            'catalog.json'   => ['items' => []],
            'overrides.json' => ['items' => []],
            'images.json'    => ['items' => []],
        ];

        foreach ($files as $filename => $default) {
            $path = $base . $filename;

            if (!file_exists($path)) {
                self::write_json($path, $default);
            }
        }
    }

    public static function read_json($filename)
    {
        $path = self::directory() . $filename;

        if (!file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        return json_last_error() === JSON_ERROR_NONE
            ? $data
            : null;
    }

    public static function write_json($path, $data)
    {
        $json = wp_json_encode(
            $data,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        $temp = $path . '.tmp';

        if (file_put_contents($temp, $json, LOCK_EX) === false) {
            return false;
        }

        return rename($temp, $path);
    }
}
