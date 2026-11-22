<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOLZKATALOG_Actions
{

    private static $instance = null;
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    private function __construct()
    {
        add_action(
            'admin_post_hk_save_item',
            [$this, 'handle_save']
        );

        add_action(
            'admin_post_hk_delete_item',
            [$this, 'handle_delete']
        );

        add_action(
            'admin_post_hk_import_catalog',
            [$this, 'handle_import']
        );

        add_action(
            'admin_post_hk_export_catalog',
            [$this, 'handle_export']
        );

        add_action(
            'wp_ajax_hk_toggle_status',
            [$this, 'ajax_toggle_status']
        );
    }

    /**
     * Prüfen, ob ein Bild noch von einer Holzart verwendet wird.
     * Falls nicht, wird die Datei gelöscht.
     */
    private function delete_image_if_unused($filename)
    {
        if (empty($filename)) {
            return;
        }
        $filename = basename($filename);
        foreach (HOLZKATALOG_Catalog::get_all() as $item) {
            if (($item['image_file'] ?? '') === $filename) {
                return;
            }
        }
        $directory = trailingslashit(HOLZKATALOG_Storage::directory())
            . 'images/';
        $path = $directory . $filename;
        if (is_file($path)) {
            wp_delete_file($path);
        }
    }
    /**
     * Holzart speichern und Bilddatei verarbeiten.
     */
    public function handle_save()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        check_admin_referer('hk_save_item');

        $id = isset($_POST['id'])
            ? sanitize_text_field(wp_unslash($_POST['id']))
            : '';

        $existing = $id
            ? HOLZKATALOG_Catalog::get($id)
            : null;

        $old_image_file = is_array($existing)
            ? ($existing['image_file'] ?? '')
            : '';

        // Vorhandenes Bild zunächst beibehalten.
        $image_file = $old_image_file;
        $new_image_path = '';

        // Löschanforderung prüfen.
        $delete_image = isset($_POST['delete_image'])
            && $_POST['delete_image'] === '1';

        // Bild-Upload.
        $has_upload = isset($_FILES['image_file']['error'])
            && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($has_upload) {

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload-Array wird anschließend strukturell validiert und der Dateityp geprüft.
            $file = isset($_FILES['image_file']) && is_array($_FILES['image_file']) ? $_FILES['image_file'] : [];
            if (
                !isset(
                    $file['error'],
                    $file['tmp_name'],
                    $file['name'],
                    $file['size']
                )
                || !is_string($file['tmp_name'])
                || !is_string($file['name'])
                || !is_numeric($file['size'])
                || (int) $file['error'] !== UPLOAD_ERR_OK
                || !is_uploaded_file($file['tmp_name'])
            ) {
                wp_die('Ungültige Bilddatei.');
            }

            $file['name'] = sanitize_file_name($file['name']);

            if ($file['error'] !== UPLOAD_ERR_OK) {
                wp_die(
                    'Beim Hochladen der Bilddatei ist ein Fehler aufgetreten.'
                );
            }

            $allowed_types = [
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'webp'     => 'image/webp',
            ];

            $checked = wp_check_filetype_and_ext(
                $file['tmp_name'],
                $file['name'],
                $allowed_types
            );

            if (empty($checked['type'])) {
                wp_die(
                    'Ungültiger Bildtyp. Erlaubt sind JPG, PNG und WebP.'
                );
            }

            $directory = trailingslashit(HOLZKATALOG_Storage::directory())
                . 'images/';

            if (!wp_mkdir_p($directory)) {
                wp_die('Das Bildverzeichnis konnte nicht angelegt werden.');
            }

            $base_name = sanitize_title(
                wp_unslash($_POST['name_de'] ?? 'holzart')
            );

            if ($base_name === '') {
                $base_name = 'holzart';
            }

            // Immer WebP verwenden.
            $filename = wp_unique_filename(
                $directory,
                $base_name . '.webp'
            );

            $new_image_path = $directory . $filename;

            // WordPress-Bildeditor laden.
            $editor = wp_get_image_editor($file['tmp_name']);

            if (is_wp_error($editor)) {
                wp_die(
                    esc_html(
                        'Das Bild konnte nicht verarbeitet werden: '
                            . $editor->get_error_message()
                    )
                );
            }

            // Originalgröße ermitteln.
            $size = $editor->get_size();

            $width  = $size['width'];
            $height = $size['height'];

            // Kleinere Dimension auf maximal 512 Pixel begrenzen.
            $min_dimension = min($width, $height);

            if ($min_dimension > 512) {

                $factor = 512 / $min_dimension;

                $new_width = (int) round($width * $factor);
                $new_height = (int) round($height * $factor);

                $result = $editor->resize(
                    $new_width,
                    $new_height,
                    false
                );

                if (is_wp_error($result)) {
                    wp_die(
                        esc_html(
                            'Das Bild konnte nicht als WebP gespeichert werden: '
                                . $result->get_error_message()
                        )
                    );
                }
            }

            // Als WebP speichern.
            $result = $editor->save(
                $new_image_path,
                'image/webp'
            );

            if (is_wp_error($result)) {
                wp_die(
                    esc_html(
                        'Das Bild konnte nicht als WebP gespeichert werden: '
                            . $result->get_error_message()
                    )
                );
            }

            $image_file = $filename;
        } elseif ($delete_image) {

            // Nur löschen, wenn kein neues Bild hochgeladen wurde.
            $image_file = '';
        }

        // Katalogdaten zusammenstellen.
        $data = [
            'name_de' => sanitize_text_field(
                wp_unslash($_POST['name_de'] ?? '')
            ),

            'name_latin' => sanitize_text_field(
                wp_unslash($_POST['name_latin'] ?? '')
            ),

            'code' => sanitize_text_field(
                wp_unslash($_POST['code'] ?? '')
            ),

            'description' => sanitize_textarea_field(
                wp_unslash($_POST['description'] ?? '')
            ),

            'image_file' => $image_file,

            'active' => !empty($_POST['active']),
        ];

        $result = HOLZKATALOG_Catalog::save(
            $data,
            $id ?: null
        );

        $url = admin_url('admin.php?page=holzarten-katalog');

        if (is_wp_error($result) || $result === false) {

            // Neu hochgeladenes Bild bei fehlgeschlagenem Speichern entfernen.
            if (
                $new_image_path !== ''
                && is_file($new_image_path)
            ) {
                wp_delete_file($new_image_path);
            }

            wp_safe_redirect(
                add_query_arg('error', '1', $url)
            );
            exit;
        }

        // Altes Bild nur löschen, wenn es nicht mehr verwendet wird.
        if (
            $old_image_file !== ''
            && $old_image_file !== $image_file
        ) {
            $this->delete_image_if_unused($old_image_file);
        }

        /*
         * Die WooCommerce-Synchronisierung wird bereits zentral
         * in HOLZKATALOG_Catalog::save() vorgemerkt.
         */

        wp_safe_redirect(
            add_query_arg('saved', '1', $url)
        );
        exit;
    }
    /**
     * Gesamten Holzartenkatalog als ZIP exportieren.
     */
    public function handle_export()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        check_admin_referer(
            'hk_export_catalog',
            'hk_export_nonce'
        );

        if (!class_exists('ZipArchive')) {
            wp_die('Die PHP-Erweiterung ZipArchive ist nicht verfügbar.');
        }

        $base = trailingslashit(HOLZKATALOG_Storage::directory());

        $temp_file = tempnam(sys_get_temp_dir(), 'hk-export-');

        if ($temp_file === false) {
            wp_die('Temporäre Exportdatei konnte nicht erstellt werden.');
        }

        $zip = new ZipArchive();

        $opened = $zip->open(
            $temp_file,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );

        if ($opened !== true) {
            wp_delete_file($temp_file);
            wp_die('ZIP-Archiv konnte nicht erstellt werden.');
        }

        // JSON-Dateien hinzufügen.
        $json_files = [
            'catalog.json',
            'overrides.json',
            'images.json',
        ];

        foreach ($json_files as $filename) {
            $path = $base . $filename;

            if (!file_exists($path) || !is_readable($path)) {
                $zip->close();
                wp_delete_file($temp_file);

                wp_die(
                    'Datei fehlt oder ist nicht lesbar: '
                        . esc_html($filename)
                );
            }

            if (!$zip->addFile($path, $filename)) {
                $zip->close();
                wp_delete_file($temp_file);

                wp_die(
                    'Datei konnte nicht ins ZIP aufgenommen werden: '
                        . esc_html($filename)
                );
            }
        }

        // Sämtliche Bilder hinzufügen.
        $image_dir = $base . 'images/';

        if (is_dir($image_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $image_dir,
                    FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->isLink()) {
                    continue;
                }

                $path = $file->getPathname();

                $relative_path = 'images/' . str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($path, strlen($image_dir))
                );

                if (!$zip->addFile($path, $relative_path)) {
                    $zip->close();
                    wp_delete_file($temp_file);

                    wp_die('Bild konnte nicht ins ZIP aufgenommen werden.');
                }
            }
        }
        $zip->close();

        // Download ausliefern.
        $download_name = 'holzarten-export-' . gmdate('Y-m-d-His') . '.zip';

        $filesize = filesize($temp_file);

        if ($filesize === false) {
            wp_delete_file($temp_file);
            wp_die('Exportdateigröße konnte nicht ermittelt werden.');
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();

        header('Content-Type: application/zip');
        header(
            'Content-Disposition: attachment; filename="'
                . $download_name
                . '"'
        );
        header('Content-Length: ' . $filesize);

        // ZIP-Datei direkt ausgeben.
        readfile($temp_file);

        wp_delete_file($temp_file);
        exit;
    }



    /**
     * Aktiv-Status per AJAX ändern.
     */
    public function ajax_toggle_status()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Keine Berechtigung.',
            ], 403);
        }

        check_ajax_referer('hk_toggle_status', 'nonce');

        $id = isset($_POST['id'])
            ? sanitize_text_field(wp_unslash($_POST['id']))
            : '';

        $active = isset($_POST['active'])
            && $_POST['active'] === '1';

        if ($id === '') {
            wp_send_json_error([
                'message' => 'Ungültige Holzarten-ID.',
            ]);
        }

        $result = HOLZKATALOG_Catalog::set_active($id, $active);
        if ($result !== true) {
            wp_send_json_error([
                'message' => 'Der Status konnte nicht geändert werden.',
            ]);
        }

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }

        wp_send_json_success([
            'active' => $active,
        ]);
    }
    /**
     * Holzart löschen.
     */
    public function handle_delete()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter; no data is modified.                                
        $id = isset($_GET['id'])
            ? sanitize_text_field(wp_unslash($_GET['id']))
            : '';

        if ($id === '') {
            wp_die('Ungültige Holzarten-ID.');
        }

        check_admin_referer('hk_delete_item_' . $id);

        $item = HOLZKATALOG_Catalog::get($id);

        $result = HOLZKATALOG_Catalog::delete($id);

        $url = admin_url('admin.php?page=holzarten-katalog');

        if (is_wp_error($result) || !$result) {
            wp_safe_redirect(
                add_query_arg('error', '1', $url)
            );
            exit;
        }

        // Bild erst nach erfolgreichem Löschen des Katalogeintrags prüfen.
        if (is_array($item)) {
            $this->delete_image_if_unused(
                $item['image_file'] ?? ''
            );
        }

        /*
         * Die WooCommerce-Synchronisierung wird bereits zentral
         * in HOLZKATALOG_Catalog::delete() vorgemerkt.
         */

        wp_safe_redirect(
            add_query_arg('deleted', '1', $url)
        );
        exit;
    }

    /**
     * ZIP-Archiv mit dem Holzartenkatalog importieren.
     */
    public function handle_import()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        if (
            !check_admin_referer(
                'hk_import_catalog',
                '_wpnonce',
                false
            )
        ) {
            wp_die('Import abgebrochen: Ungültige Sicherheitsprüfung.');
        }

        $url = admin_url('admin.php?page=holzarten-katalog');

        $file = [];

        if (
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload-Array wird anschließend strukturell validiert
            isset($_FILES['catalog_zip'])
            && is_array($_FILES['catalog_zip'])
        ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload-Array wird anschließend strukturell validiert
            $file = $_FILES['catalog_zip'];
        }

        // Upload-Daten prüfen.
        if (
            !isset(
                $file['error'],
                $file['name'],
                $file['tmp_name'],
                $file['size']
            )
            || !is_string($file['name'])
            || !is_string($file['tmp_name'])
            || $file['tmp_name'] === ''
            || !isset($file['size'])
            || !is_int($file['size'])
            || $file['size'] < 1
            || $file['size'] > (50 * MB_IN_BYTES)
            || !isset($file['error'])
            || !is_int($file['error'])
            || $file['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($file['tmp_name'])
        ) {
            wp_safe_redirect(
                add_query_arg('import_error', 'upload', $url)
            );
            exit;
        }


        // Dateinamen bereinigen.
        $file['name'] = sanitize_file_name($file['name']);

        // Dateiendung prüfen.
        if (
            strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
            !== 'zip'
        ) {
            wp_safe_redirect(
                add_query_arg('import_error', 'type', $url)
            );
            exit;
        }

        // Gemeinsame Importfunktion der Speicherklasse aufrufen.
        $result = HOLZKATALOG_Storage::import_archive(
            $file['tmp_name'],
            true
        );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    'import_error',
                    sanitize_key($result->get_error_code()),
                    $url
                )
            );
            exit;
        }

        if ($result !== true) {
            wp_safe_redirect(
                add_query_arg('import_error', 'import_failed', $url)
            );
            exit;
        }

        $count = count(HOLZKATALOG_Catalog::get_all());

        wp_safe_redirect(
            add_query_arg('imported', $count, $url)
        );
        exit;
    }
}
