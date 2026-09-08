<?php
/**
 * Plugin Orchestrator
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

class Veyra_Plugin {

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
        require_once VEYRA_DIR . 'includes/class-installer.php';
        $installer = new Veyra_Installer();
        $installer->install();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('veyra_daily_scan');
        wp_clear_scheduled_hook('veyra_hourly_health_check');
        wp_clear_scheduled_hook('veyra_cleanup_logs');

        $transients = array(
            'veyra_health_score',
            'veyra_scan_status',
            'veyra_recent_events',
            'veyra_plugin_scan',
            'veyra_theme_scan',
        );
        foreach ($transients as $t) {
            delete_transient($t);
        }
    }

    private function init() {
        $this->load_dependencies();
        $this->db          = new Veyra_Database();
        $this->settings    = new Veyra_Settings();
        $this->logger      = new Veyra_Logger();
        $this->security    = new Veyra_Security();
        $this->capabilities = new Veyra_Capabilities();

        if (is_admin()) {
            require_once VEYRA_DIR . 'admin/class-admin.php';
            new Veyra_Admin();
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
            'includes/class-ai-safety.php',
        );
        foreach ($files as $file) {
            $path = VEYRA_DIR . $file;
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
