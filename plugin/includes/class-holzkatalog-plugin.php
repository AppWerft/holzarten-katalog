<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOLZKATALOG_Plugin
{
    private static $instance = null;
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    private $frontend;
    /**
     * Konstruktor.
     */
    private function __construct()
    {
        
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_admin_assets']
        );

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

       HOLZKATALOG_Admins::instance();
       HOLZKATALOG_Actions::instance();
       $this->frontend = HOLZKATALOG_Frontend::instance();

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
        // Download ausliefern.
        $download_name = 'holzarten-export-' . gmdate('Y-m-d-His') . '.zip';

        // WordPress-Dateisystem initialisieren.
        global $wp_filesystem;

        if (!WP_Filesystem()) {
            wp_delete_file($temp_file);
            wp_die('WordPress-Dateisystem konnte nicht initialisiert werden.');
        }

        // ZIP-Datei einlesen.
        $file_contents = $wp_filesystem->get_contents($temp_file);

        if ($file_contents === false) {
            wp_delete_file($temp_file);
            wp_die('Exportdatei konnte nicht gelesen werden.');
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

        header('Content-Length: ' . strlen($file_contents));

        // ZIP-Datei binär und unverändert ausgeben.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary ZIP contents must not be escaped.
        echo $file_contents;

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
