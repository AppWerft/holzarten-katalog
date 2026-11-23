<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOLZKATALOG_Storage
{
    public static function directory()
    {
        $upload = wp_upload_dir();
        return trailingslashit($upload['basedir'])
            . 'holzarten-daten/';
    }
    public static function write_json($path, $data, $filesystem = null)
    {
        if ($filesystem === null) {
            $filesystem = self::get_filesystem();
        }

        if (is_wp_error($filesystem)) {
            return false;
        }

        $json = wp_json_encode(
            $data,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        $temp = $path . '.tmp-' . wp_generate_password(8, false, false);

        if (!$filesystem->put_contents($temp, $json, FS_CHMOD_FILE)) {
            return false;
        }

        if (!$filesystem->move($temp, $path, true)) {
            $filesystem->delete($temp);
            return false;
        }

        return true;
    }
    /**
     * WordPress-Dateisystem initialisieren.
     */
    private static function get_filesystem()
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        global $wp_filesystem;

        if (!WP_Filesystem()) {
            return new WP_Error(
                'filesystem_unavailable',
                'Das WordPress-Dateisystem konnte nicht initialisiert werden.'
            );
        }

        return $wp_filesystem;
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
            $source = HOLZKATALOG_PLUGIN_DIR . 'data/';
            $result = self::import_defaults($source);

            if (is_wp_error($result)) {
                wp_die(
                    esc_html(
                        'Holzarten-Katalog: Die Standarddaten konnten nicht importiert werden. '
                            . $result->get_error_message()
                    )
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
        // Grenzen gegen übergroße Archive und ZIP-Bomben.
        $max_archive_size = 50 * MB_IN_BYTES;
        $max_total_size   = 100 * MB_IN_BYTES;
        $max_entry_size   = 20 * MB_IN_BYTES;
        $max_json_size    = 10 * MB_IN_BYTES;
        $max_entries      = 1000;

        if (!class_exists('ZipArchive')) {
            return new WP_Error(
                'zip_missing',
                'Die PHP-Erweiterung ZipArchive ist nicht verfügbar.'
            );
        }

        if (
            !is_file($archive_path)
            || !is_readable($archive_path)
            || filesize($archive_path) === false
            || filesize($archive_path) > $max_archive_size
        ) {
            return new WP_Error(
                'archive_missing',
                'Die ZIP-Datei fehlt, ist nicht lesbar oder überschreitet die zulässige Größe.'
            );
        }

        $zip = new ZipArchive();
        $opened = $zip->open($archive_path);

        if ($opened !== true) {
            return new WP_Error(
                'zip_open',
                'Das ZIP-Archiv konnte nicht geöffnet werden.'
            );
        }

        if ($zip->numFiles < 1 || $zip->numFiles > $max_entries) {
            $zip->close();

            return new WP_Error(
                'zip_entries',
                'Das ZIP-Archiv enthält keine Dateien oder zu viele Einträge.'
            );
        }

        /*
         * Zuerst das komplette Archiv prüfen und alle erlaubten Inhalte
         * einlesen. Vor Abschluss dieser Prüfung wird nichts gespeichert.
         */
        $allowed_json = [
            'catalog.json',
            'overrides.json',
            'images.json',
        ];

        $json_data = [];
        $images = [];
        $seen_names = [];
        $total_size = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $zip->getNameIndex($i);

            if (
                $stat === false
                || $filename === false
                || !isset($stat['size'])
                || !is_int($stat['size'])
                || $stat['size'] < 0
            ) {
                $zip->close();

                return new WP_Error(
                    'zip_entry_invalid',
                    'Ein Eintrag im ZIP-Archiv ist ungültig.'
                );
            }

            // Doppelte Namen können Dateien im Archiv gezielt überschreiben.
            $normalized_name = strtolower($filename);

            if (isset($seen_names[$normalized_name])) {
                $zip->close();

                return new WP_Error(
                    'zip_duplicate',
                    'Das ZIP-Archiv enthält doppelte Dateinamen.'
                );
            }
            $seen_names[$normalized_name] = true;

            // Symlinks im ZIP-Archiv grundsätzlich ablehnen.
            if (method_exists($zip, 'getExternalAttributesIndex')) {
                $opsys = 0;
                $attributes = 0;

                if ($zip->getExternalAttributesIndex(
                    $i,
                    $opsys,
                    $attributes
                )) {
                    $file_type = ($attributes >> 16) & 0170000;

                    if ($file_type === 0120000) {
                        $zip->close();

                        return new WP_Error(
                            'zip_symlink',
                            'Das ZIP-Archiv enthält einen nicht erlaubten symbolischen Link.'
                        );
                    }
                }
            }

            $total_size += $stat['size'];

            if (
                $stat['size'] > $max_entry_size
                || $total_size > $max_total_size
            ) {
                $zip->close();

                return new WP_Error(
                    'zip_too_large',
                    'Das ZIP-Archiv oder ein einzelner Eintrag ist zu groß.'
                );
            }

            // Verzeichnis-Einträge sind nur für images/ erlaubt.
            if (substr($filename, -1) === '/') {
                if ($filename !== 'images/') {
                    $zip->close();

                    return new WP_Error(
                        'zip_path',
                        'Das ZIP-Archiv enthält einen nicht erlaubten Verzeichnispfad.'
                    );
                }
                continue;
            }

            if (in_array($filename, $allowed_json, true)) {
                if ($stat['size'] > $max_json_size) {
                    $zip->close();

                    return new WP_Error(
                        'json_too_large',
                        'Eine JSON-Datei im ZIP-Archiv ist zu groß.'
                    );
                }

                $content = $zip->getFromIndex($i);

                if ($content === false) {
                    $zip->close();

                    return new WP_Error(
                        'zip_read',
                        'Eine Datei im ZIP-Archiv konnte nicht gelesen werden.'
                    );
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

                $json_data[$filename] = self::sanitize_import_data($data);
                continue;
            }

            // Bilder ausschließlich direkt unter images/ erlauben.
            if (strpos($filename, 'images/') !== 0) {
                $zip->close();

                return new WP_Error(
                    'zip_path',
                    'Das ZIP-Archiv enthält einen nicht erlaubten Dateipfad.'
                );
            }

            $image_name = substr($filename, 7);

            if (
                $image_name === ''
                || basename($image_name) !== $image_name
                || !preg_match(
                    '/^[A-Za-z0-9][A-Za-z0-9._-]{0,179}\\.(?:jpe?g|png|webp)$/i',
                    $image_name
                )
            ) {
                $zip->close();

                return new WP_Error(
                    'image_name',
                    'Das Archiv enthält einen ungültigen Bilddateinamen.'
                );
            }

            if ($stat['size'] < 1) {
                $zip->close();

                return new WP_Error(
                    'image_empty',
                    'Das Archiv enthält eine leere Bilddatei.'
                );
            }

            $content = $zip->getFromIndex($i);

            if ($content === false) {
                $zip->close();

                return new WP_Error(
                    'image_read',
                    'Ein Bild im ZIP-Archiv konnte nicht gelesen werden.'
                );
            }

            if (!function_exists('getimagesizefromstring')) {
                $zip->close();

                return new WP_Error(
                    'image_validation_unavailable',
                    'Die Bildprüfung ist auf diesem Server nicht verfügbar.'
                );
            }

            $image_info = getimagesizefromstring($content);
            $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            $allowed_mimes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp',
            ];

            if (
                $image_info === false
                || empty($image_info['mime'])
                || empty($image_info[0])
                || empty($image_info[1])
                || ($image_info[0] * $image_info[1]) > 40000000
                || !isset($allowed_mimes[$extension])
                || $image_info['mime'] !== $allowed_mimes[$extension]
            ) {
                $zip->close();

                return new WP_Error(
                    'image_type',
                    'Eine Datei ist kein gültiges JPEG-, PNG- oder WebP-Bild: '
                        . $image_name
                );
            }

            $images[$image_name] = $content;
        }

        $zip->close();

        // Die Katalogdatei ist zwingend erforderlich.
        if (
            !isset($json_data['catalog.json'])
            || !isset($json_data['catalog.json']['items'])
            || !is_array($json_data['catalog.json']['items'])
        ) {
            return new WP_Error(
                'catalog_invalid',
                'Die catalog.json fehlt oder hat eine falsche Struktur.'
            );
        }

        $catalog = $json_data['catalog.json'];

        // Alle Einträge müssen Arrays sein. Bildreferenzen dürfen keine Pfade enthalten.
        foreach ($catalog['items'] as $item) {
            if (!is_array($item)) {
                return new WP_Error(
                    'catalog_item_invalid',
                    'Der Katalog enthält einen ungültigen Eintrag.'
                );
            }
        }

        foreach ($json_data as $json_file => $data) {
            $reference_error = self::validate_import_image_references($data);

            if (is_wp_error($reference_error)) {
                return $reference_error;
            }
        }

        // Vorhandene manuelle Felder anhand der ID sichern.
        if ($preserve_manual) {
            $existing_items = HOLZKATALOG_Catalog::get_all();
            $existing_by_id = [];

            foreach ($existing_items as $item) {
                if (!empty($item['id'])) {
                    $existing_by_id[$item['id']] = $item;
                }
            }

            foreach ($catalog['items'] as &$item) {
                if (
                    empty($item['id'])
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

            // Beim manuellen Import werden Zusatzdateien nicht überschrieben.
            $json_data = [
                'catalog.json' => $catalog,
            ];
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

        // Vorhandene Daten vor einem manuellen Import sichern.
        if ($preserve_manual) {
            $backup = self::create_backup($base);

            if (is_wp_error($backup)) {
                return $backup;
            }
        }

        $filesystem = self::get_filesystem();

        if (is_wp_error($filesystem)) {
            return $filesystem;
        }

        // Erst nach vollständiger Archivprüfung werden Daten geschrieben.
        foreach ($json_data as $filename => $data) {
            if (!self::write_json($base . $filename, $data, $filesystem)) {
                return new WP_Error(
                    'write_json',
                    'Die Datei ' . $filename . ' konnte nicht gespeichert werden.'
                );
            }
        }

        foreach ($images as $image_name => $content) {
            $image_path = $base . 'images/' . $image_name;

            if (!$filesystem->put_contents(
                $image_path,
                $content,
                FS_CHMOD_FILE
            )) {
                return new WP_Error(
                    'image_write',
                    'Ein geprüftes Bild konnte nicht gespeichert werden: '
                        . $image_name
                );
            }
        }

        return true;
    }

    /**
     * Prüft Bildreferenzen in allen importierten JSON-Daten.
     */
    private static function validate_import_image_references($data)
    {
        if (!is_array($data)) {
            return true;
        }

        foreach ($data as $key => $value) {
            if ($key === 'image_file' && $value !== '' && $value !== null) {
                if (
                    !is_string($value)
                    || basename($value) !== $value
                    || !preg_match(
                        '/^[A-Za-z0-9][A-Za-z0-9._-]{0,179}\\.(?:jpe?g|png|webp)$/i',
                        $value
                    )
                ) {
                    return new WP_Error(
                        'catalog_image_reference',
                        'Der Katalog enthält einen ungültigen Bilddateinamen.'
                    );
                }
            }

            $result = self::validate_import_image_references($value);

            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Bereinigt rekursiv alle Textwerte aus importierten JSON-Dateien.
     */
    private static function sanitize_import_data($data, $key = '')
    {
        if (is_array($data)) {
            $clean = [];

            foreach ($data as $child_key => $value) {
                $clean[$child_key] = self::sanitize_import_data(
                    $value,
                    is_string($child_key) ? $child_key : ''
                );
            }

            return $clean;
        }

        if (!is_string($data)) {
            return $data;
        }

        $url_fields = [
            'image_source_url',
            'image_license_url',
            'url',
        ];

        if (in_array($key, $url_fields, true)) {
            return esc_url_raw($data);
        }

        $textarea_fields = [
            'description',
            'characteristics',
            'uses',
        ];

        if (in_array($key, $textarea_fields, true)) {
            return sanitize_textarea_field($data);
        }

        return sanitize_text_field($data);
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
}
