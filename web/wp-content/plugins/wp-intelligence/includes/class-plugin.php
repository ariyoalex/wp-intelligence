<?php
/**
 * Plugin Orchestrator
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Plugin {

    private static $instance = null;
    private $db = null;
    private $settings = null;
    private $logger = null;
    private $security = null;
    private $capabilities = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    public static function activate() {
        require_once WP_INTELLIGENCE_DIR . 'includes/class-installer.php';
        $installer = new WP_Intelligence_Installer();
        $installer->install();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('wpi_daily_scan');
        wp_clear_scheduled_hook('wpi_hourly_health_check');
        wp_clear_scheduled_hook('wpi_cleanup_logs');

        $transients = array(
            'wpi_health_score',
            'wpi_scan_status',
            'wpi_recent_events',
            'wpi_plugin_scan',
            'wpi_theme_scan',
        );
        foreach ($transients as $t) {
            delete_transient($t);
        }
    }

    private function init() {
        $this->load_dependencies();
        $this->db          = new WP_Intelligence_Database();
        $this->settings    = new WP_Intelligence_Settings();
        $this->logger      = new WP_Intelligence_Logger();
        $this->security    = new WP_Intelligence_Security();
        $this->capabilities = new WP_Intelligence_Capabilities();

        if (is_admin()) {
            require_once WP_INTELLIGENCE_DIR . 'admin/class-admin.php';
            new WP_Intelligence_Admin();
        }
    }

    private function load_dependencies() {
        $files = array(
            'includes/class-database.php',
            'includes/class-settings.php',
            'includes/class-logger.php',
            'includes/class-security.php',
            'includes/class-capabilities.php',
            'includes/class-encryption.php',
        );
        foreach ($files as $file) {
            $path = WP_INTELLIGENCE_DIR . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function get_db() {
        return $this->db;
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_logger() {
        return $this->logger;
    }

    public function get_security() {
        return $this->security;
    }

    public function get_capabilities() {
        return $this->capabilities;
    }
}
