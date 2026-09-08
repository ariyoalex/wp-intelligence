<?php
/**
 * Plugin Name: Veyra
 * Plugin URI: https://github.com/ariyoalex/veyra
 * Description: Website-aware intelligence layer for WordPress. Diagnose errors, track changes, monitor health, document sites, and manage client requests — all from one dashboard.
 * Version: 1.0.0
 * Author: Ariyo Alex
 * Text Domain: veyra
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

define('VEYRA_VERSION', '1.0.0');
define('VEYRA_FILE', __FILE__);
define('VEYRA_DIR', plugin_dir_path(__FILE__));
define('VEYRA_URL', plugin_dir_url(__FILE__));
define('VEYRA_BASENAME', plugin_basename(__FILE__));

require_once VEYRA_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('Veyra_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Veyra_Plugin', 'deactivate'));

add_action('init', function () {
    Veyra_Plugin::instance();
});
