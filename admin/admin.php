<?php

if (!defined('ABSPATH')) exit;

class ThanSoHoc_Admin {
    private $plugin_file;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_filter('plugin_action_links_' . plugin_basename($this->plugin_file), [$this, 'settings_link']);
    }

    public function settings_link($links) {
        $settings_link = '<a href="admin.php?page=than-so-hoc">' . esc_html(ThsI18n::lookup('admin', 'settings_link')) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function register_menu() {
        add_submenu_page(
                'fortune-settings',
                ThsI18n::lookup('admin', 'page_title'),
                ThsI18n::lookup('admin', 'page_title'),
                'manage_options',
                'than-so-hoc',
                [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $allow_ai = get_option('ths_allow_ai', '0');

        ?>
        <div class="wrap">
            <h1><?php ThsI18n::e(ThsI18n::lookup('admin', 'page_title')); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ths_settings_form'); ?>

                <h2><?php ThsI18n::e(ThsI18n::lookup('admin', 'section_ai_title')); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php ThsI18n::e(ThsI18n::lookup('admin', 'allow_ai_label')); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_ai" value="1" <?php checked($allow_ai, '1'); ?>>
                                <?php ThsI18n::e(ThsI18n::lookup('admin', 'allow_ai_desc')); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(ThsI18n::lookup('admin', 'btn_save')); ?>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_POST['_wpnonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['_wpnonce'], 'ths_settings_form')) {
            return;
        }

        $allow_ai = isset($_POST['allow_ai']) ? '1' : '0';
        update_option('ths_allow_ai', $allow_ai);
    }
}
