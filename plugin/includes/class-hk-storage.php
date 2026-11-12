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

    /**
     * Initialisierung bei der Plugin-Aktivierung.
     *
     * Der mitgelieferte Export wird nur importiert,
     * wenn noch kein Katalog vorhanden ist.
     */
    
    
/**
 * Initialisierung bei der Plugin-Aktivierung.
 *
 * Der mitgelieferte Katalog wird nur importiert,
 * wenn noch kein gefüllter Katalog vorhanden ist.
 */
public static function activate()
{
    $base = self::directory();

    wp_mkdir_p($base . 'images/');
    wp_mkdir_p($base . 'backups/');

    // Vorhandenen Katalog prüfen.
    $catalog = self::read_json('catalog.json');

    // Nur bei leerem Katalog den Initialbestand importieren.
    if (
        !is_array($catalog)
        || empty($catalog['items'])
    ) {
        $source = HK_PLUGIN_DIR . 'data/';

        $result = self::import_defaults($source);

        if (is_wp_error($result)) {
            error_log(
                'Holzarten-Katalog: Erstimport fehlgeschlagen: '
                . $result->get_error_message()
            );
        }
    }

    // Fehlende Zusatzdateien anlegen.
    $files = [
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
    /**
 * URL eines Holzbildes zurückgeben.
 */
public static function image_url($filename)
{
    if (empty($filename)) {
        return '';
    }

    $upload = wp_upload_dir();

    return trailingslashit($upload['baseurl'])
        . 'holzarten-daten/images/'
        . rawurlencode($filename);
}
    /**
     * Sichert die vorhandenen Katalogdateien und Bilder.
     */
    private static function create_backup($base)
    {
        $backup_root = $base . 'backups/';

        $backup_name = 'import-' . date('Ymd-His');
        $backup_dir = $backup_root . $backup_name . '/';

        $counter = 1;

        while (file_exists($backup_dir)) {
            $backup_dir = $backup_root
                . $backup_name . '-' . $counter . '/';

            $counter++;
        }

        if (!wp_mkdir_p($backup_dir)) {
            return new WP_Error(
                'backup_directory',
                'Das Backup-Verzeichnis konnte nicht angelegt werden.'
            );
        }

        // Vorhandene JSON-Dateien sichern.
        foreach (
            ['catalog.json', 'overrides.json', 'images.json']
            as $filename
        ) {
            $source = $base . $filename;

            if (
                file_exists($source)
                && !copy($source, $backup_dir . $filename)
            ) {
                return new WP_Error(
                    'backup_file',
                    'Die Datei ' . $filename . ' konnte nicht gesichert werden.'
                );
            }
        }

        // Vorhandene Bilder sichern.
        $image_dir = $base . 'images/';

        if (is_dir($image_dir)) {
            $backup_images = $backup_dir . 'images/';

            if (!wp_mkdir_p($backup_images)) {
                return new WP_Error(
                    'backup_images',
                    'Das Backup-Verzeichnis für Bilder konnte nicht angelegt werden.'
                );
            }

            $files = scandir($image_dir);

            if ($files === false) {
                return new WP_Error(
                    'backup_scan',
                    'Das Bilderverzeichnis konnte nicht gelesen werden.'
                );
            }

            foreach ($files as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $source = $image_dir . $filename;

                if (
                    is_file($source)
                    && !copy($source, $backup_images . $filename)
                ) {
                    return new WP_Error(
                        'backup_image',
                        'Ein vorhandenes Bild konnte nicht gesichert werden: '
                            . $filename
                    );
                }
            }
        }

        return true;
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
