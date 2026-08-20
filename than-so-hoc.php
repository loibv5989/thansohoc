<?php
/**
 * Plugin Name: Thần Số Học
 * Plugin URI: https://solqi.net
 * Description: Tra cứu Thần Số Học - Vận Mệnh
 * Version: 26.7.26
 * Author: Loibv
 * Requires PHP: 8.2 or higher
 * License: GPL v2 or later
 * Text Domain: than-so-hoc
 */

if (!defined('ABSPATH')) exit;

define('THAN_SO_HOC_VERSION', '26.7.26');
define('THAN_SO_HOC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THAN_SO_HOC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THAN_SO_HOC_RATE_LIMIT', 0);
define('THS_DEBUG', 1);

require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/i18n.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/name.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/helper.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/settings.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/handle.php';

if (is_admin()) {
    require_once THAN_SO_HOC_PLUGIN_DIR . 'admin/admin.php';
    new ThanSoHoc_Admin(__FILE__);
}
