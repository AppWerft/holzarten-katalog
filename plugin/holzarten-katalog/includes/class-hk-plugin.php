<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Plugin {
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Konstruktor.
     */
    private function __construct()
    {
        add_action(
            'admin_menu',
            [$this, 'register_admin_menu']
        );

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

        // WooCommerce initialisieren, sofern verfügbar.
        add_action(
            'plugins_loaded',
            function () {
                if (class_exists('WooCommerce')) {
                    HK_WooCommerce::instance();
                }
            },
            20
        );
        add_action(
            'wp_ajax_hk_toggle_status',
            [$this, 'ajax_toggle_status']
        );
    }

    /**
     * CSS nur auf unserer Plugin-Seite laden.
     */
    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_holzarten-katalog') {
            return;
        }

        wp_enqueue_style(
            'hk-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            [],
            '1.0.6'
        );
        wp_enqueue_script(
            'hk-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            ['jquery'],
            '1.0.2' .  time(),
            true
        );
        wp_localize_script(
            'hk-admin',
            'hkAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('hk_toggle_status'),
            ]
);
    }

    /**
     * Admin-Menü registrieren.
     */
    public function register_admin_menu()
    {
        add_menu_page(
            'Holzarten-Katalog',
            'Holzarten',
            'manage_options',
            'holzarten-katalog',
            [$this, 'admin_page'],
            'dashicons-palmtree',
            25
        );
    }

    /**
     * URL einer Bilddatei ermitteln.
     */
    private function get_image_url($filename)
    {
        if (empty($filename)) {
            return '';
        }

        $upload = wp_upload_dir();

        return trailingslashit($upload['baseurl'])
            . 'holzarten-daten/images/'
            . rawurlencode(basename($filename));
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

        foreach (HK_Catalog::get_all() as $item) {
            if (($item['image_file'] ?? '') === $filename) {
                return;
            }
        }

        $directory = trailingslashit(HK_Storage::directory())
            . 'images/';

        $path = $directory . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Admin-Seite darstellen.
     */
    public function admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        $edit_id = isset($_GET['edit'])
            ? sanitize_text_field(wp_unslash($_GET['edit']))
            : '';

        $item = $edit_id
            ? HK_Catalog::get($edit_id)
            : null;

        $search = isset($_GET['s'])
            ? sanitize_text_field(wp_unslash($_GET['s']))
            : '';

        $items = HK_Catalog::get_all();

        if ($search !== '') {
            $items = array_filter($items, function ($entry) use ($search) {
                $text = implode(' ', [
                    $entry['name_de'] ?? '',
                    $entry['name_latin'] ?? '',
                    $entry['code'] ?? '',
                ]);

                return stripos($text, $search) !== false;
            });
        }

        ?>

        <div class="wrap">

            <h1 class="wp-heading-inline">
                Holzarten-Katalog
            </h1>



            
            <hr class="wp-header-end">

            <?php
            if (isset($_GET['saved'])) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Die Holzart wurde gespeichert.</p>';
                echo '</div>';
            }

            if (isset($_GET['deleted'])) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Die Holzart wurde gelöscht.</p>';
                echo '</div>';
            }

            if (isset($_GET['error'])) {
                echo '<div class="notice notice-error">';
                echo '<p>Die Aktion konnte nicht ausgeführt werden.</p>';
                echo '</div>';
            }

            if (isset($_GET['imported'])) {
                $count = absint($_GET['imported']);

                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html(
                    $count . ' Holzarten wurden erfolgreich importiert.'
                ) . '</p>';
                echo '</div>';
            }

            if (isset($_GET['import_error'])) {
                $errors = [
                    'upload'           => 'Keine gültige ZIP-Datei hochgeladen.',
                    'type'             => 'Es wurde keine ZIP-Datei ausgewählt.',
                    'zip_missing'      => 'Die PHP-Erweiterung ZipArchive ist nicht verfügbar.',
                    'archive_missing'  => 'Die ZIP-Datei wurde nicht gefunden oder ist nicht lesbar.',
                    'zip_open'         => 'Das ZIP-Archiv konnte nicht geöffnet werden.',
                    'catalog_missing'  => 'Im Archiv fehlt catalog.json.',
                    'catalog_invalid'  => 'Die catalog.json ist ungültig.',
                    'json_invalid'     => 'Eine JSON-Datei im Archiv ist ungültig.',
                    'directory'        => 'Das Datenverzeichnis konnte nicht angelegt werden.',
                    'backup_directory' => 'Das Backup-Verzeichnis konnte nicht angelegt werden.',
                    'backup_file'      => 'Eine Katalogdatei konnte nicht gesichert werden.',
                    'backup_images'    => 'Das Bilder-Backup-Verzeichnis konnte nicht angelegt werden.',
                    'backup_scan'      => 'Das Bilderverzeichnis konnte nicht gelesen werden.',
                    'backup_image'     => 'Ein vorhandenes Bild konnte nicht gesichert werden.',
                    'write_json'       => 'Eine JSON-Datei konnte nicht gespeichert werden.',
                    'image_write'      => 'Ein Bild konnte nicht gespeichert werden.',
                ];

                $error = sanitize_key(
                    wp_unslash($_GET['import_error'])
                );

                echo '<div class="notice notice-error">';
                echo '<p>Der Import ist fehlgeschlagen. ';

                if (isset($errors[$error])) {
                    echo esc_html($errors[$error]);
                }

                echo '</p></div>';
            }
            ?>

            <?php
            if ($item || isset($_GET['new'])) {

                $this->render_form($item);

            } else {

                ?>

                <h2>Datensicherung und Klonen</h2>

                <form method="post"
                      action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                    <input type="hidden"
                           name="action"
                           value="hk_export_catalog">

                    <?php
                    wp_nonce_field(
                        'hk_export_catalog',
                        'hk_export_nonce'
                    );

                    submit_button(
                        'Holzartenkatalog exportieren',
                        'secondary',
                        'submit',
                        false
                    );
                    ?>

                </form>

                <?php

                $this->render_import_form();
                $this->render_table($items, $search);
            }
            ?>

        </div>

        <?php
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

        $base = trailingslashit(HK_Storage::directory());

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
            unlink($temp_file);
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
                unlink($temp_file);

                wp_die(
                    'Datei fehlt oder ist nicht lesbar: '
                    . esc_html($filename)
                );
            }

            if (!$zip->addFile($path, $filename)) {
                $zip->close();
                unlink($temp_file);

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
                    unlink($temp_file);

                    wp_die('Bild konnte nicht ins ZIP aufgenommen werden.');
                }
            }
        }

        $zip->close();

        // Download ausliefern.
        $download_name = 'holzarten-export-' . date('Y-m-d-His') . '.zip';

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

        header('Content-Length: ' . filesize($temp_file));

        readfile($temp_file);

        unlink($temp_file);
        exit;
    }

    /**
     * Eingabeformular darstellen.
     */
    private function render_form($item)
    {
        $is_edit = is_array($item);

        $image_file = $item['image_file'] ?? '';
        $image_url = $this->get_image_url($image_file);

        ?>

        <h2>
            <?php echo $is_edit
                ? 'Holzart bearbeiten'
                : 'Neue Holzart anlegen'; ?>
        </h2>

        <form method="post"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              enctype="multipart/form-data">

            <input type="hidden"
                   name="action"
                   value="hk_save_item">

            <input type="hidden"
                   name="id"
                   value="<?php echo esc_attr($item['id'] ?? ''); ?>">

            <?php wp_nonce_field('hk_save_item'); ?>

            <table class="form-table">

                <tr>
                    <th>
                        <label for="name_de">Deutscher Name</label>
                    </th>
                    <td>
                        <input type="text"
                               id="name_de"
                               name="name_de"
                               class="regular-text"
                               required
                               value="<?php echo esc_attr(
                                   $item['name_de'] ?? ''
                               ); ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="name_latin">Botanischer Name</label>
                    </th>
                    <td>
                        <input type="text"
                               id="name_latin"
                               name="name_latin"
                               class="regular-text"
                               value="<?php echo esc_attr(
                                   $item['name_latin'] ?? ''
                               ); ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="code">EN-13556-Code</label>
                    </th>
                    <td>
                        <input type="text"
                               id="code"
                               name="code"
                               class="regular-text"
                               value="<?php echo esc_attr(
                                   $item['code'] ?? ''
                               ); ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="description">Beschreibung</label>
                    </th>
                    <td>
                        <textarea id="description"
                                  name="description"
                                  rows="5"
                                  class="large-text"><?php
                            echo esc_textarea(
                                $item['description'] ?? ''
                            );
                        ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="image_file">Bilddatei</label>
                    </th>
                    <td>

                        <?php if ($image_url): ?>

                            <p>
                                <img src="<?php echo esc_url($image_url); ?>"
                                     alt="<?php echo esc_attr(
                                         $item['name_de'] ?? ''
                                     ); ?>"
                                     style="max-width:180px;height:auto;">
                            </p>

                            <p class="description">
                                Aktuelles Bild:
                                <?php echo esc_html($image_file); ?>
                            </p>

                        <?php else: ?>

                            <p class="description">
                                Noch kein Bild zugeordnet.
                            </p>

                        <?php endif; ?>

                        <input type="file"
                               id="image_file"
                               name="image_file"
                               accept=".jpg,.jpeg,.png,.webp">

                        <p class="description">
                            Erlaubt: JPG, PNG und WebP.
                            Ein neues Bild ersetzt das bisherige.
                        </p>

                    </td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="active"
                                   value="1"
                                <?php checked(
                                    $item['active'] ?? true,
                                    true
                                ); ?>>
                            Aktiv
                        </label>
                    </td>
                </tr>

            </table>

            <?php submit_button('Speichern'); ?>

            <a href="<?php echo esc_url(
                admin_url('admin.php?page=holzarten-katalog')
            ); ?>" class="button">
                Abbrechen
            </a>

        </form>

        <?php
    }

    /**
     * JSON-Importformular darstellen.
     *
    
     */
   
    /**
     * ZIP-Importformular darstellen.
     */
    private function render_import_form()
    {
        ?>

        <hr>

        <h2>Holzartenkatalog importieren</h2>
<p class="description">
            Importiert einen vollständigen Export mit Bäumen und ihren Holzbildern (*.zip).
        </p>
        <form method="post"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              enctype="multipart/form-data">

            <input type="hidden"
                   name="action"
                   value="hk_import_catalog">

            <?php wp_nonce_field('hk_import_catalog'); ?>

            <input type="file"
                   name="catalog_zip"
                   accept=".zip,application/zip"
                   required>

            <?php
            submit_button(
                'Holzartenkatalog importieren',
                'secondary',
                'submit',
                false
            );
            ?>
        </form>
        <hr>
        <?php
    }


    /**
     * Tabelle mit allen Holzarten darstellen.
     */
    private function render_table($items, $search)
    {
        ?>
        <table width="100%"><tr><td>


            <a href="<?php echo esc_url(
                admin_url('admin.php?page=holzarten-katalog&new=1')
            ); ?>" class="page-title-action">
                Neue Holzart anlegen
            </a>
</td><td>



<label class="hk-switch">
    <input type="checkbox" id="hk-only-active">
    <span class="hk-slider"></span>
    <span class="hk-switch-label">Nur aktive Hölzer</span>
</label>
                        </td><td>
        <form method="get">

            <input type="hidden"
                   name="page"
                   value="holzarten-katalog">

            <p class="search-box">

                <label class="screen-reader-text" for="hk-search">
                    Holzartenfilter
                </label>

                <input type="search"
                            placeholder="Baumart"
                       id="hk-search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>">

                <input type="submit"
                       class="button"
                       value="Filtern">

            </p>

        </form>
         <td></tr></table>                   
        <table id="hk-catalog-table" class="wp-list-table widefat fixed striped">

            <thead>
                <tr>
                    <th style="width:70px;">Holzbild</th>
                    <th>Deutscher Name</th>
                    <th>Botanischer Name</th>
                    <th>EN13556 - Code</th>
                    <th>Status</th>
                    <th style="width:100px;">Aktionen</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($items)): ?>

                <tr>
                    <td colspan="6">
                        Keine Holzarten gefunden.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($items as $item): ?>
                    <tr>
                      <td>
    <?php
    $image_url = $this->get_image_url(
        $item['image_file'] ?? ''
    );
    ?>

   
   <?php if ($image_url && file_exists(
    trailingslashit(HK_Storage::directory())
    . 'images/'
    . basename($item['image_file'])
)): ?>

    <span class="hk-image-zoom">
        <img src="<?php echo esc_url($image_url); ?>"
             alt="<?php echo esc_attr($item['name_de'] ?? ''); ?>"
             class="hk-wood-image">
    </span>

<?php else: ?>

    <div>
        <img class="hk-image-placeholder" src="<?php echo esc_url(
            plugin_dir_url(dirname(__FILE__)) . 'assets/images/holz.png'
        ); ?>" alt="Kein Bild verfügbar">
    </div>

<?php endif; ?>
</td>
                        <td>
                            <strong>
                                <?php echo esc_html(
                                    $item['name_de'] ?? ''
                                ); ?>
                            </strong>

                            <?php if (!empty($item['description'])): ?>
                                <div tabindex="0"  
                                 style="
                                  height:100px;
             width:150px;
             overflow:scroll;
             overflow-wrap:anywhere;
             background:#fff;">
                                        <?php echo esc_html(
                                            $item['description']
                                        ); ?>
                                    
                              </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <em>
                                <?php echo esc_html(
                                    $item['name_latin'] ?? ''
                                ); ?>
                            </em>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $item['code'] ?? ''
                            ); ?>
                        </td>

                       <td>
    <label>
        <input type="checkbox"
               class="hk-status-toggle"
               data-id="<?php echo esc_attr($item['id']); ?>"
               <?php checked(!empty($item['active'])); ?>>

        <span class="hk-status-label">
            <?php echo !empty($item['active']) ? 'Aktiv' : 'Inaktiv'; ?>
        </span>
    </label>
</td>

                        <td>

                            <a href="<?php echo esc_url(
                                admin_url(
                                    'admin.php?page=holzarten-katalog&edit='
                                    . rawurlencode($item['id'])
                                )
                            ); ?>">
                                <img style="width:18px;"  src="<?php echo esc_url(
            plugin_dir_url(dirname(__FILE__)) . 'assets/images/edit.svg'
        ); ?>" />
                            </a>
                            &nbsp;
                            <a href="<?php echo esc_url(
                                wp_nonce_url(
                                    admin_url(
                                        'admin-post.php?action=hk_delete_item&id='
                                        . rawurlencode($item['id'])
                                    ),
                                    'hk_delete_item_' . $item['id']
                                )
                            ); ?>"
                               onclick="return confirm('Diese Holzart wirklich löschen?');">
                                <img style="width:16px;" src="<?php echo esc_url(
                                plugin_dir_url(dirname(__FILE__)) . 'assets/images/delete.svg'
        ); ?>" />
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

        <?php
    }

    /**
 * Aktiv-Status per AJAX ändern.
 */
public function ajax_toggle_status()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Keine Berechtigung.'
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
            'message' => 'Ungültige Holzarten-ID.'
        ]);
    }

    $result = HK_Catalog::set_active($id, $active);

    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message()
        ]);
    }

    wp_send_json_success([
        'active' => $active
    ]);
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
            ? HK_Catalog::get($id)
            : null;

        $old_image_file = is_array($existing)
            ? ($existing['image_file'] ?? '')
            : '';

        // Vorhandenes Bild zunächst beibehalten.
        $image_file = $old_image_file;
        $new_image_path = '';

        // Bild-Upload.
        if (
            isset($_FILES['image_file']) &&
            $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES['image_file'];

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

            $directory = trailingslashit(HK_Storage::directory())
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

            $filename = $base_name . '.' . $checked['ext'];

            $filename = wp_unique_filename(
                $directory,
                $filename
            );

            $new_image_path = $directory . $filename;

            if (!move_uploaded_file(
                $file['tmp_name'],
                $new_image_path
            )) {
                wp_die('Die Bilddatei konnte nicht gespeichert werden.');
            }

            $image_file = $filename;
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

        $result = HK_Catalog::save(
            $data,
            $id ?: null
        );

        $url = admin_url('admin.php?page=holzarten-katalog');

        if (is_wp_error($result) || $result === false) {

            // Neu hochgeladenes Bild bei fehlgeschlagenem Speichern entfernen.
            if (
                $new_image_path !== '' &&
                is_file($new_image_path)
            ) {
                unlink($new_image_path);
            }

            wp_safe_redirect(
                add_query_arg('error', '1', $url)
            );
            exit;
        }

        // Altes Bild nur löschen, wenn es nicht mehr verwendet wird.
        if (
            $old_image_file !== '' &&
            $old_image_file !== $image_file
        ) {
            $this->delete_image_if_unused($old_image_file);
        }

        /*
         * Die WooCommerce-Synchronisierung wird bereits zentral
         * in HK_Catalog::save() vorgemerkt.
         */

        wp_safe_redirect(
            add_query_arg('saved', '1', $url)
        );
        exit;
    }

    /**
     * Holzart löschen.
     */
    public function handle_delete()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        $id = isset($_GET['id'])
            ? sanitize_text_field(wp_unslash($_GET['id']))
            : '';

        if ($id === '') {
            wp_die('Ungültige Holzarten-ID.');
        }

        check_admin_referer('hk_delete_item_' . $id);

        $item = HK_Catalog::get($id);

        $result = HK_Catalog::delete($id);

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
         * in HK_Catalog::delete() vorgemerkt.
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

        // Upload prüfen.
        if (
            !isset($_FILES['catalog_zip'])
            || $_FILES['catalog_zip']['error'] !== UPLOAD_ERR_OK
        ) {
            wp_safe_redirect(
                add_query_arg('import_error', 'upload', $url)
            );
            exit;
        }

        $file = $_FILES['catalog_zip'];

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
        $result = HK_Storage::import_archive(
            $file['tmp_name'],
            true
        );

        if (is_wp_error($result)) {
            error_log(
                'Holzarten-Katalog: ZIP-Import fehlgeschlagen: '
                . $result->get_error_message()
            );

            wp_safe_redirect(
                add_query_arg(
                    'import_error',
                    $result->get_error_code(),
                    $url
                )
            );
            exit;
        }

        // Anzahl der importierten Holzarten ermitteln.
        $catalog = HK_Storage::read_json('catalog.json');

        $count = count($catalog['items'] ?? []);

        // WooCommerce-Synchronisierung vormerken.
        if (class_exists('HK_WooCommerce')) {
            HK_WooCommerce::schedule_sync();
        }

        wp_safe_redirect(
            add_query_arg('imported', $count, $url)
        );
        exit;
    }

}