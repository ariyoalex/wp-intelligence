<?php
/**
 * Plugin Name: WP Intelligence
 * Description: AI-Powered WordPress Intelligence, Diagnostics, Documentation, Monitoring and Client Management Platform
 * Version: 1.0.0
 * Author: Ariyo Alex
 * Text Domain: wp-intelligence
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

define('WP_INTELLIGENCE_VERSION', '1.0.0');
define('WP_INTELLIGENCE_FILE', __FILE__);
define('WP_INTELLIGENCE_DIR', plugin_dir_path(__FILE__));
define('WP_INTELLIGENCE_URL', plugin_dir_url(__FILE__));
define('WP_INTELLIGENCE_BASENAME', plugin_basename(__FILE__));

require_once WP_INTELLIGENCE_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('WP_Intelligence_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Intelligence_Plugin', 'deactivate'));

add_action('init', function () {
    WP_Intelligence_Plugin::instance();
});
