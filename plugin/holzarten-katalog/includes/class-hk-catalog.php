
<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_Catalog
{
    /**
     * Gesamten Katalog laden und Overrides anwenden.
     */
    public static function get_all()
    {
        $catalog = HK_Storage::read_json('catalog.json');
        $overrides = HK_Storage::read_json('overrides.json');

        $items = $catalog['items'] ?? [];
        $changes = $overrides['items'] ?? [];

        $result = [];

        foreach ($items as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $id = $item['id'];

            if (isset($changes[$id]['deleted'])
                && $changes[$id]['deleted']) {
                continue;
            }

            if (isset($changes[$id])) {
                $item = array_merge($item, $changes[$id]);
            }

            $result[$id] = $item;
        }

        // Manuell hinzugefügte Holzarten ergänzen.
        foreach ($changes as $id => $change) {
            if (!empty($change['custom'])
                && empty($change['deleted'])) {
                $result[$id] = $change;
            }
        }

        return array_values($result);
    }

    /**
     * Einzelne Holzart anhand ihrer ID laden.
     */
    public static function get($id)
    {
        foreach (self::get_all() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Holzart neu anlegen oder manuell bearbeiten.
     */
    public static function save($data, $id = null)
    {
        $overrides = HK_Storage::read_json('overrides.json');

        if (!is_array($overrides)) {
            $overrides = ['items' => []];
        }

        if (!isset($overrides['items'])
            || !is_array($overrides['items'])) {
            $overrides['items'] = [];
        }

        if (!$id) {
            $id = 'custom-' . wp_generate_uuid4();
        }

        $item = [
            'id' => sanitize_text_field($id),
            'name_de' => sanitize_text_field($data['name_de'] ?? ''),
            'name_latin' => sanitize_text_field($data['name_latin'] ?? ''),
            'code' => sanitize_text_field($data['code'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'active' => !empty($data['active']),
        ];
        // Vorhandene Bildzuordnung erhalten, wenn kein neues Bild übergeben wird.
        $existing = $overrides['items'][$id]['image_file'] ?? '';

        if (!empty($data['image_file'])) {
            $item['image_file'] = sanitize_file_name($data['image_file']);
        } elseif ($existing !== '') {
            $item['image_file'] = $existing;
        }

        if ($item['name_de'] === '') {
            return new WP_Error(
                'missing_name',
                'Der deutsche Name darf nicht leer sein.'
            );
        }

        // Bei neuen Holzarten kennzeichnen wir den Datensatz.
        $catalog = HK_Storage::read_json('catalog.json');
        $catalog_items = $catalog['items'] ?? [];

        $is_imported = false;

        foreach ($catalog_items as $catalog_item) {
            if (($catalog_item['id'] ?? '') === $id) {
                $is_imported = true;
                break;
            }
        }

        if (!$is_imported) {
            $item['custom'] = true;
        }

        $overrides['items'][$id] = $item;

        $path = HK_Storage::directory() . 'overrides.json';

        if (!HK_Storage::write_json($path, $overrides)) {
            return new WP_Error(
                'write_failed',
                'Die Holzart konnte nicht gespeichert werden.'
            );
        }

        return $id;
    }

    /**
     * Holzart löschen.
     *
     * Importierte Datensätze werden nur ausgeblendet.
     */
    public static function delete($id)
    {
        $overrides = HK_Storage::read_json('overrides.json');

        if (!is_array($overrides)) {
            $overrides = ['items' => []];
        }

        if (!isset($overrides['items'])
            || !is_array($overrides['items'])) {
            $overrides['items'] = [];
        }

        $catalog = HK_Storage::read_json('catalog.json');
        $catalog_items = $catalog['items'] ?? [];

        $is_imported = false;

        foreach ($catalog_items as $item) {
            if (($item['id'] ?? '') === $id) {
                $is_imported = true;
                break;
            }
        }

        if ($is_imported) {
            $overrides['items'][$id] = [
                'id' => $id,
                'deleted' => true,
            ];
        } else {
            unset($overrides['items'][$id]);
        }

        $path = HK_Storage::directory() . 'overrides.json';

        if (!HK_Storage::write_json($path, $overrides)) {
            return new WP_Error(
                'write_failed',
                'Die Holzart konnte nicht gelöscht werden.'
            );
        }

        return true;
    }
}
