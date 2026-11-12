<?php

class HK_Frontend
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
        add_shortcode(
            'hk_holzarten',
            [$this, 'render_wood_catalog']
        );
    }

    public function render_wood_catalog($atts = [])
    {
        // Darstellung der Holzarten
    }
}