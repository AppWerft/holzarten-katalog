<?php

if (!defined('ABSPATH')) {
    exit;
}

class HOLZKATALOG_Admins
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
            HOLZKATALOG_HOOKS::WP_ADMIN_MENU,
            [$this, 'register_admin_menu']
        );

        add_action(
            HOLZKATALOG_HOOKS::WP_ADMIN_ENQUEUE_SCRIPTS,
            [$this, 'enqueue_admin_assets']
        );
        add_action(
            HOLZKATALOG_HOOKS::WC_AJAX_SAVE_ONLYACTIVE,
            [$this, 'save_only_active']
        );
    }

    /**
     * CSS und JavaScript nur auf unserer Plugin-Seite laden.
     */
    public function enqueue_admin_assets($hook)
    {
        if (
            !in_array(
                $hook,
                [
                    HOLZKATALOG_HOOKSUFFIXES::TOPLEVEL_PAGE_HOLZARTENKATALOG,
                    HOLZKATALOG_HOOKSUFFIXES::HOLZARTENKATALOG_PAGE_IMPORTEXPORT,
                ],
                true
            )
        ) {
            return;
        }

        $plugin_dir = HOLZKATALOG_PLUGIN_DIR;
        $plugin_url = HOLZKATALOG_PLUGIN_URL;
        $css_file = $plugin_dir . 'assets/css/admin.css';
        wp_enqueue_style(
            HOLZKATALOG_HANDLES::ADMIN,
            $plugin_url . 'assets/css/admin.css',
            [],
            file_exists($css_file) ? filemtime($css_file) : '1.0.0'
        );
        $jsfile = $plugin_dir . 'assets/js/admin-holzartenkatalog.js';
        error_log($jsfile);
        wp_enqueue_script(
            HOLZKATALOG_HANDLES::ADMIN,
            $plugin_url .'assets/js/admin-holzartenkatalog.js',
            ['jquery'],
            file_exists($jsfile) ? filemtime($jsfile) : HOLZKATALOG_VERSION,
            true
        );
        wp_localize_script(
            HOLZKATALOG_HANDLES::ADMIN,
            'hkAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce(HOLZKATALOG_NONCES::TOGGLE_STATUS),
                'catalogNonce' => wp_create_nonce(HOLZKATALOG_NONCES::CAT_NONCE),
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
            'Holzartenkatalog',
            'manage_options',
            'holzarten-katalog',
            [$this, 'admin_page'],
            'dashicons-palmtree',
            56
        );
        add_submenu_page(
            'holzarten-katalog',
            'Import / Export',
            'Import / Export',
            'manage_options',
            'holzarten-import-export',
            [$this, 'import_export_page']
        );
    }
    /**
     * Import- und Exportseite anzeigen.
     */
    public function import_export_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        echo '<div class="wrap">';
        echo '
            <div class="hk-import-export-header">
                <img
                    src="' . esc_url(HOLZKATALOG_URL . 'assets/images/exportimport.webp')
            . '" 
                    width="72"
                    height="72"
                    alt="ExportImport"
                >
                <h1>Holzarten – Import / Export</h1>
            </div>
            <p>
                Hier können erfasste Datenbestände, einschließlich der Holzbilder,
                zwischen zwei WordPress-Instanzen kopiert werden.
            </p>
        ';

        $this->render_importexport_form();

        echo '</div>';
    }
    /**
     * Admin-Seite darstellen.
     */
    public function admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
        $edit_id = isset($_GET['edit']) ? sanitize_text_field(wp_unslash($_GET['edit'])) : '';

        $item = $edit_id ? HOLZKATALOG_Catalog::get($edit_id) : null;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $items = HOLZKATALOG_Catalog::get_all();

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

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
            if (isset($_GET['saved'])) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Die Holzart wurde gespeichert.</p>';
                echo '</div>';
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
            if (isset($_GET['deleted'])) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Die Holzart wurde gelöscht.</p>';
                echo '</div>';
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET
            if (isset($_GET['error'])) {
                echo '<div class="notice notice-error">';
                echo '<p>Die Aktion konnte nicht ausgeführt werden.</p>';
                echo '</div>';
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
            if (isset($_GET['imported'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
                $count = absint(wp_unslash($_GET['imported']));
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html(
                    $count . ' Holzarten wurden erfolgreich importiert.'
                ) . '</p>';
                echo '</div>';
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
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
                    'image_write'      => 'Ein geprüftes Bild konnte nicht gespeichert werden.',
                    'zip_entries'      => 'Das Archiv enthält keine Dateien oder zu viele Einträge.',
                    'zip_entry_invalid' => 'Ein Archiveintrag ist ungültig.',
                    'zip_duplicate'    => 'Das Archiv enthält doppelte Dateinamen.',
                    'zip_symlink'      => 'Das Archiv enthält einen nicht erlaubten symbolischen Link.',
                    'zip_too_large'    => 'Das Archiv oder ein Eintrag ist zu groß.',
                    'json_too_large'   => 'Eine JSON-Datei im Archiv ist zu groß.',
                    'zip_read'         => 'Eine Datei im Archiv konnte nicht gelesen werden.',
                    'zip_path'         => 'Das Archiv enthält einen nicht erlaubten Dateipfad.',
                    'image_name'       => 'Das Archiv enthält einen ungültigen Bilddateinamen.',
                    'image_empty'      => 'Das Archiv enthält eine leere Bilddatei.',
                    'image_read'       => 'Ein Bild im Archiv konnte nicht gelesen werden.',
                    'image_validation_unavailable' => 'Die Bildprüfung ist auf diesem Server nicht verfügbar.',
                    'image_type'       => 'Eine Datei ist kein gültiges JPEG-, PNG- oder WebP-Bild.',
                    'catalog_item_invalid' => 'Der Katalog enthält einen ungültigen Eintrag.',
                    'catalog_image_reference' => 'Der Katalog enthält einen ungültigen Bilddateinamen.',
                    'import_failed'    => 'Der Import konnte nicht abgeschlossen werden.',
                ];

                $error = sanitize_key(
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET
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

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET dient hier nur der Navigation, nicht der Verarbeitung einer Aktion.
            if ($item || isset($_GET['new'])) $this->render_form($item);
            else $this->render_table($items, $search);
            ?>
        </div>
    <?php
    }
    /**
     * Eingabeformular darstellen.
     */
    private function render_form($item)
    {
        $is_edit = is_array($item);

        $image_file = $item['image_file'] ?? '';
        $image_url = HOLZKATALOG_Storage::image_url($image_file);

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
                value="holzkatalog_save_item">

            <input type="hidden"
                name="id"
                value="<?php echo esc_attr($item['id'] ?? ''); ?>">

            <?php wp_nonce_field('holzkatalog_save_item'); ?>

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

                <!-- Bilderhochladen -->
                <tr>
                    <th>
                        <label>Bilddatei</label>
                    </th>

                    <td>
                        <div style="display:flex; gap:20px; align-items:flex-start;">

                            <!-- Zwischenablage -->
                            <div style="flex:1;">

                                <div id="hk-image-paste"
                                    tabindex="0"
                                    style="min-height:100px; padding:12px; border:2px dashed #aaa;">

                                    <?php if ($image_url): ?>

                                        <img src="<?php echo esc_url($image_url); ?>"
                                            alt="<?php echo esc_attr($item['name_de'] ?? ''); ?>"
                                            style="max-width:180px;height:auto;">

                                        <p class="description">
                                            Aktuelles Bild:
                                            <?php echo esc_html($image_file); ?>
                                        </p>

                                    <?php else: ?>

                                        Bild hier einfügen (⌘ V / Strg+V)

                                    <?php endif; ?>

                                    <div id="hk-image-preview"></div>

                                </div>

                            </div>

                            <!-- Datei-Upload -->
                            <div style="flex:1;">

                                <input type="file"
                                    id="image_file"
                                    name="image_file"
                                    accept=".jpg,.jpeg,.png,.webp">

                                <p class="description">
                                    JPG, PNG oder WebP auswählen.
                                </p>

                            </div>

                        </div>

                        <input type="hidden"
                            id="hk-delete-image"
                            name="delete_image"
                            value="0">

                        <button type="button"
                            id="hk-delete-image-button"
                            class="button">
                            Bild löschen
                        </button>

                    </td>
                </tr>
                <!-- /Bilderhochladen -->

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
    private function render_importexport_form()
    {
    ?>
        <div class="hk-import-export-grid">

            <div class="hk-import-export-box">
                <h2>Export – Datensicherung und Klonen</h2>

                <p class="description">
                    Erstellt einen vollständigen Export des Holzartenkatalogs
                    einschließlich der Holzbilder.
                </p>

                <form method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                    <input type="hidden"
                        name="action"
                        value="holzkatalog_export_catalog">

                    <?php
                    wp_nonce_field(
                        'holzkatalog_export_catalog',
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
            </div>

            <div class="hk-import-export-box">
                <h2>Import – Holzartenkatalog</h2>

                <p class="description">
                    Importiert einen vollständigen Export mit Bäumen
                    und ihren Holzbildern (*.zip).
                </p>

                <form method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                    enctype="multipart/form-data">

                    <input type="hidden"
                        name="action"
                        value="holzkatalog_import_catalog">

                    <?php wp_nonce_field('holzkatalog_import_catalog'); ?>

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
            </div>

        </div>
    <?php
    }


    /**
     * Tabelle mit allen Holzarten darstellen.
     */
    private function render_table($items, $search)
    {
    ?>

        <table width="100%">
            <tr>
                <td>
                    <a href="<?php echo esc_url(
                                    admin_url('admin.php?page=holzarten-katalog&new=1')
                                ); ?>" class="page-title-action">
                        Neue Holzart anlegen
                    </a>
                </td>

                <td>

                    <?php
                    $only_active = get_user_meta(
                        get_current_user_id(),
                        '_hk_only_active',
                        true
                    ) === '1';
                    ?>


                    <label class="hk-switch">
                        <input
                            type="checkbox"
                            id="hk-only-active"
                            <?php checked($only_active); ?>>
                        <span class="hk-slider"></span>
                        <span class="hk-switch-label">Nur als aktiv markierte Hölzer</span>
                    </label>
                </td>

                <td>
                    <form method="get">

                        <input type="hidden"
                            name="page"
                            value="holzarten-katalog">

                        <p class="search-box">

                            <label class="screen-reader-text"
                                for="hk-search">
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
                </td>
            </tr>
        </table>

        <table id="hk-catalog-table"
            class="wp-list-table widefat fixed striped">

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
                                $image_file = $item['image_file'] ?? '';

                                $image_url = HOLZKATALOG_Storage::image_url(
                                    $image_file
                                );

                                $image_path = trailingslashit(
                                    HOLZKATALOG_Storage::directory()
                                ) . 'images/' . basename($image_file);
                                ?>

                                <?php if ($image_url && is_file($image_path)): ?>

                                    <span class="hk-image-zoom">
                                        <img src="<?php echo esc_url($image_url); ?>"
                                            alt="<?php echo esc_attr($item['name_de'] ?? ''); ?>"
                                            class="hk-wood-image">
                                    </span>

                                <?php else: ?>

                                    <div>
                                        <img
                                            class="hk-image-placeholder"
                                            src="<?php echo esc_url(
                                                        HOLZKATALOG_URL . 'assets/images/holz.png'
                                                    ); ?>"
                                            alt="[Kein Bild verfügbar]">
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
                                         background:#fff;
                                     ">
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
                                        <?php echo !empty($item['active'])
                                            ? 'Aktiv'
                                            : 'Inaktiv'; ?>
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
                                    <img
                                        style="width:18px;"
                                        src="<?php echo esc_url(
                                                    HOLZKATALOG_URL . 'assets/images/edit.svg'
                                                ); ?>"
                                        alt="Bearbeiten">
                                </a>

                                &nbsp;

                                <a href="<?php echo esc_url(
                                                wp_nonce_url(
                                                    admin_url(
                                                        'admin-post.php?action=hk_delete_item&id='
                                                            . rawurlencode($item['id'])
                                                    ),
                                                    'holzkatalog_delete_item_' . $item['id']
                                                )
                                            ); ?>"
                                    onclick="return confirm('Diese Holzart wirklich löschen?');">

                                    <img
                                        style="width:16px;"
                                        src="<?php echo esc_url(
                                                    HOLZKATALOG_URL . 'assets/images/delete.svg'
                                                ); ?>"
                                        alt="Löschen">

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
     * Filterzustand "Nur aktive" speichern.
     */
    public function save_only_active()
    {
        check_ajax_referer('hk_catalog_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung', 403);
        }

        $only_active = isset($_POST['only_active'])
            && sanitize_text_field(
                wp_unslash($_POST['only_active'])
            ) === '1';

        update_user_meta(
            get_current_user_id(),
            '_hk_only_active',
            $only_active ? '1' : '0'
        );

        wp_send_json_success([
            'only_active' => $only_active,
        ]);
    }
}
