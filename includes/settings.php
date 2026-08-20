<?php

if (!defined('ABSPATH')) exit;

class ThanSoHoc_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {}

    public function allowAI() {
        return get_option('ths_allow_ai', '0');
    }

    public function getTimezone(): float {
        $gmt_offset = get_option('gmt_offset');
        if ($gmt_offset !== '') {
            return (float)$gmt_offset;
        }

        return 7.0;
    }
}

ThanSoHoc_Settings::get_instance();
