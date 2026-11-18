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
     * Importiert den mitgelieferten Initialbestand aus dem Plugin-Verzeichnis.
     */
    public static function import_defaults($source)
    {
        $source = trailingslashit($source);
        $target = self::directory();

        if (!is_dir($source)) {
            return new WP_Error(
                'defaults_directory',
                'Das Datenverzeichnis wurde nicht gefunden: ' . $source
            );
        }

        // JSON-Dateien übernehmen.
        foreach (['catalog.json', 'overrides.json', 'images.json'] as $filename) {
            $source_file = $source . $filename;
            $target_file = $target . $filename;

            if (file_exists($source_file)) {
                if (!copy($source_file, $target_file)) {
                    return new WP_Error(
                        'defaults_copy',
                        'Die Datei konnte nicht importiert werden: ' . $filename
                    );
                }
            }
        }

        // Bilder übernehmen.
        $source_images = $source . 'images/';
        $target_images = $target . 'images/';

        if (is_dir($source_images)) {
            wp_mkdir_p($target_images);

            $files = scandir($source_images);

            if ($files === false) {
                return new WP_Error(
                    'defaults_images',
                    'Das Bilderverzeichnis konnte nicht gelesen werden.'
                );
            }

            foreach ($files as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $source_file = $source_images . $filename;
                $target_file = $target_images . $filename;

                if (is_file($source_file) && !file_exists($target_file)) {
                    if (!copy($source_file, $target_file)) {
                        return new WP_Error(
                            'defaults_image_copy',
                            'Ein Bild konnte nicht importiert werden: ' . $filename
                        );
                    }
                }
            }
        }
        return true;
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

    public static function import_archive(
        $archive_path,
        $preserve_manual = true
    ) {
        if (!class_exists('ZipArchive')) {
            return new WP_Error(
                'zip_missing',
                'Die PHP-Erweiterung ZipArchive ist nicht verfügbar.'
            );
        }

        if (!is_file($archive_path) || !is_readable($archive_path)) {
            return new WP_Error(
                'archive_missing',
                'Die ZIP-Datei wurde nicht gefunden oder ist nicht lesbar.'
            );
        }

        $base = self::directory();

        if (
            !wp_mkdir_p($base . 'images/')
            || !wp_mkdir_p($base . 'backups/')
        ) {
            return new WP_Error(
                'directory',
                'Das Datenverzeichnis konnte nicht angelegt werden.'
            );
        }

        $zip = new ZipArchive();

        if ($zip->open($archive_path) !== true) {
            return new WP_Error(
                'zip_open',
                'Das ZIP-Archiv konnte nicht geöffnet werden.'
            );
        }

        // Katalogdatei lesen und prüfen.
        $catalog_json = $zip->getFromName('catalog.json');

        if ($catalog_json === false) {
            $zip->close();

            return new WP_Error(
                'catalog_missing',
                'Im ZIP-Archiv fehlt die Datei catalog.json.'
            );
        }

        $catalog = json_decode($catalog_json, true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || !is_array($catalog)
            || !isset($catalog['items'])
            || !is_array($catalog['items'])
        ) {
            $zip->close();

            return new WP_Error(
                'catalog_invalid',
                'Die catalog.json ist ungültig oder hat eine falsche Struktur.'
            );
        }

        // Vorhandene manuelle Felder anhand der ID sichern.
        if ($preserve_manual) {
            $existing_items = HK_Catalog::get_all();
            $existing_by_id = [];

            foreach ($existing_items as $item) {
                if (!empty($item['id'])) {
                    $existing_by_id[$item['id']] = $item;
                }
            }

            foreach ($catalog['items'] as &$item) {
                if (
                    !is_array($item)
                    || empty($item['id'])
                    || !isset($existing_by_id[$item['id']])
                ) {
                    continue;
                }

                $old = $existing_by_id[$item['id']];

                foreach (
                    ['image_file', 'description', 'active']
                    as $field
                ) {
                    if (array_key_exists($field, $old)) {
                        $item[$field] = $old[$field];
                    }
                }
            }

            unset($item);
        }

        // JSON-Dateien aus dem Archiv vorbereiten.
        $json_data = [
            'catalog.json' => $catalog,
        ];

        if (!$preserve_manual) {
            foreach (
                ['overrides.json', 'images.json']
                as $filename
            ) {
                $content = $zip->getFromName($filename);

                if ($content === false) {
                    continue;
                }

                $data = json_decode($content, true);

                if (
                    json_last_error() !== JSON_ERROR_NONE
                    || !is_array($data)
                ) {
                    $zip->close();

                    return new WP_Error(
                        'json_invalid',
                        'Die Datei ' . $filename . ' im ZIP-Archiv ist ungültig.'
                    );
                }

                $json_data[$filename] = $data;
            }
        }

        // Vorhandene Daten vor einem manuellen Import sichern.
        if ($preserve_manual) {
            $backup = self::create_backup($base);

            if (is_wp_error($backup)) {
                $zip->close();

                return $backup;
            }
        }

        // JSON-Dateien schreiben.
        foreach ($json_data as $filename => $data) {
            if (!self::write_json($base . $filename, $data)) {
                $zip->close();

                return new WP_Error(
                    'write_json',
                    'Die Datei ' . $filename . ' konnte nicht gespeichert werden.'
                );
            }
        }

        // Bilder aus dem ZIP übernehmen.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if (
                $filename === false
                || strpos($filename, 'images/') !== 0
                || substr($filename, -1) === '/'
            ) {
                continue;
            }

            $image_name = substr($filename, 7);

            // Nur Dateien direkt im images-Verzeichnis zulassen.
            if (
                $image_name === ''
                || basename($image_name) !== $image_name
                || $image_name === '.'
                || $image_name === '..'
            ) {
                continue;
            }

            $content = $zip->getFromIndex($i);

            if (
                $content === false
                || file_put_contents(
                    $base . 'images/' . $image_name,
                    $content,
                    LOCK_EX
                ) === false
            ) {
                $zip->close();

                return new WP_Error(
                    'image_write',
                    'Ein Bild konnte nicht gespeichert werden: ' . $image_name
                );
            }
        }

        $zip->close();

        return true;
    }


    /**
     * Sichert die vorhandenen Katalogdateien und Bilder.
     */
    private static function create_backup($base)
    {
        $backup_root = $base . 'backups/';

        $backup_name = 'import-' . gmdate('Ymd-His');
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

        // WordPress-Dateisystem initialisieren.
        require_once ABSPATH . 'wp-admin/includes/file.php';

        global $wp_filesystem;

        if (!WP_Filesystem()) {
            wp_delete_file($temp);
            return false;
        }

        // Temporäre Datei an den endgültigen Speicherort verschieben.
        if (!$wp_filesystem->move($temp, $path, true)) {
            wp_delete_file($temp);
            return false;
        }

        return true;
    }
}
