
<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Plugin
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
        add_action('admin_menu', [$this, 'register_admin_menu']);

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
     * CSS und JavaScript nur auf unserer Plugin-Seite laden.
     */
    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_holzarten-katalog') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'hk-admin',
            HK_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            HK_VERSION,
            true
        );
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

            <a href="<?php echo esc_url(
                admin_url('admin.php?page=holzarten-katalog&new=1')
            ); ?>" class="page-title-action">
                Neue Holzart
            </a>

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
            ?>

            <?php
            if ($item || isset($_GET['new'])) {
                $this->render_form($item);
            } else {
                $this->render_table($items, $search);
            }
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

        $image_id = $item['image_id'] ?? 0;

        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, 'thumbnail')
            : '';

        ?>

        <h2>
            <?php echo $is_edit
                ? 'Holzart bearbeiten'
                : 'Neue Holzart anlegen'; ?>
        </h2>

        <form method="post"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

            <input type="hidden" name="action" value="hk_save_item">

            <input type="hidden"
                   name="id"
                   value="<?php echo esc_attr($item['id'] ?? ''); ?>">

            <?php wp_nonce_field('hk_save_item'); ?>

            <table class="form-table">

                <tr>
                    <th><label for="name_de">Deutscher Name</label></th>
                    <td>
                        <input type="text"
                               id="name_de"
                               name="name_de"
                               class="regular-text"
                               required
                               value="<?php echo esc_attr($item['name_de'] ?? ''); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="name_latin">Botanischer Name</label></th>
                    <td>
                        <input type="text"
                               id="name_latin"
                               name="name_latin"
                               class="regular-text"
                               value="<?php echo esc_attr($item['name_latin'] ?? ''); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="code">EN-13556-Code</label></th>
                    <td>
                        <input type="text"
                               id="code"
                               name="code"
                               class="regular-text"
                               value="<?php echo esc_attr($item['code'] ?? ''); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="description">Beschreibung</label></th>
                    <td>
                        <textarea id="description"
                                  name="description"
                                  rows="5"
                                  class="large-text"><?php
                            echo esc_textarea($item['description'] ?? '');
                        ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th>Bild</th>
                    <td>
                        <input type="hidden"
                               id="image_id"
                               name="image_id"
                               value="<?php echo esc_attr($image_id); ?>">

                        <div id="hk-image-preview">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>"
                                     style="max-width:180px;height:auto;">
                            <?php endif; ?>
                        </div>

                        <p>
                            <button type="button"
                                    class="button"
                                    id="hk-select-image">
                                Bild auswählen
                            </button>

                            <button type="button"
                                    class="button"
                                    id="hk-remove-image">
                                Bild entfernen
                            </button>
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
     * Tabelle mit allen Holzarten darstellen.
     */
    private function render_table($items, $search)
    {
        ?>

        <form method="get">
            <input type="hidden"
                   name="page"
                   value="holzarten-katalog">

            <p class="search-box">
                <label class="screen-reader-text" for="hk-search">
                    Holzarten suchen
                </label>

                <input type="search"
                       id="hk-search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>">

                <input type="submit"
                       class="button"
                       value="Suchen">
            </p>
        </form>

        <table class="wp-list-table widefat fixed striped">

            <thead>
                <tr>
                    <th style="width:70px;">Bild</th>
                    <th>Deutscher Name</th>
                    <th>Botanischer Name</th>
                    <th>EN-Code</th>
                    <th>Status</th>
                    <th style="width:150px;">Aktionen</th>
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
                            if (!empty($item['image_id'])) {
                                echo wp_get_attachment_image(
                                    (int) $item['image_id'],
                                    [50, 50]
                                );
                            }
                            ?>
                        </td>

                        <td>
                            <strong>
                                <?php echo esc_html($item['name_de'] ?? ''); ?>
                            </strong>
                        </td>

                        <td>
                            <em>
                                <?php echo esc_html($item['name_latin'] ?? ''); ?>
                            </em>
                        </td>

                        <td>
                            <?php echo esc_html($item['code'] ?? ''); ?>
                        </td>

                        <td>
                            <?php echo !empty($item['active'])
                                ? 'Aktiv'
                                : 'Inaktiv'; ?>
                        </td>

                        <td>

                            <a href="<?php echo esc_url(
                                admin_url(
                                    'admin.php?page=holzarten-katalog&edit='
                                    . rawurlencode($item['id'])
                                )
                            ); ?>">
                                Bearbeiten
                            </a>

                            |

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
                                Löschen
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
     * Speichern verarbeiten.
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
            'image_id' => absint($_POST['image_id'] ?? 0),
            'active' => !empty($_POST['active']),
        ];

        $result = HK_Catalog::save($data, $id ?: null);

        $url = admin_url('admin.php?page=holzarten-katalog');

        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('error', '1', $url));
            exit;
        }

        wp_safe_redirect(add_query_arg('saved', '1', $url));
        exit;
    }

    /**
     * Löschen verarbeiten.
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

        $result = HK_Catalog::delete($id);

        $url = admin_url('admin.php?page=holzarten-katalog');

        if (is_wp_error($result) || !$result) {
            wp_safe_redirect(add_query_arg('error', '1', $url));
            exit;
        }

        wp_safe_redirect(add_query_arg('deleted', '1', $url));
        exit;
    }
}
