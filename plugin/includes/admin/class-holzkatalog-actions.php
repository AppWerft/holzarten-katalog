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
}
