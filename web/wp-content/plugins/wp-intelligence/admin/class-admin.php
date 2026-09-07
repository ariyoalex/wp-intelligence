<?php
/**
 * Admin Menu and Pages
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Admin {

    private static $instance = null;
    private $assets_version;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->assets_version = WP_INTELLIGENCE_VERSION;
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_wpi_run_scan', array($this, 'ajax_run_scan'));
        add_action('wp_ajax_wpi_dismiss_notice', array($this, 'ajax_dismiss_notice'));
    }

    public function register_menus() {
        add_menu_page(
            __('WP Intelligence', 'wp-intelligence'),
            __('WP Intelligence', 'wp-intelligence'),
            'manage_wp_intelligence',
            'wp-intelligence',
            array($this, 'render_dashboard'),
            'dashicons-chart-area',
            30
        );
        add_submenu_page('wp-intelligence', __('Dashboard', 'wp-intelligence'), __('Dashboard', 'wp-intelligence'), 'manage_wp_intelligence', 'wp-intelligence', array($this, 'render_dashboard'));
        add_submenu_page('wp-intelligence', __('Analyzer', 'wp-intelligence'), __('Analyzer', 'wp-intelligence'), 'manage_wp_intelligence', 'wp-intelligence-analyzer', array($this, 'render_analyzer'));
        add_submenu_page('wp-intelligence', __('Changes', 'wp-intelligence'), __('Changes', 'wp-intelligence'), 'manage_wp_intelligence', 'wp-intelligence-changes', array($this, 'render_changes'));
        add_submenu_page('wp-intelligence', __('Doctor', 'wp-intelligence'), __('Doctor', 'wp-intelligence'), 'manage_wp_intelligence_diagnostics', 'wp-intelligence-doctor', array($this, 'render_doctor'));
        add_submenu_page('wp-intelligence', __('Requests', 'wp-intelligence'), __('Requests', 'wp-intelligence'), 'manage_wp_intelligence_requests', 'wp-intelligence-requests', array($this, 'render_requests'));
        add_submenu_page('wp-intelligence', __('Handover', 'wp-intelligence'), __('Handover', 'wp-intelligence'), 'manage_wp_intelligence', 'wp-intelligence-handover', array($this, 'render_handover'));
        add_submenu_page('wp-intelligence', __('Settings', 'wp-intelligence'), __('Settings', 'wp-intelligence'), 'manage_wp_intelligence_settings', 'wp-intelligence-settings', array($this, 'render_settings'));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-intelligence') === false) {
            return;
        }
        wp_enqueue_style('wp-intelligence-admin', WP_INTELLIGENCE_URL . 'admin/css/admin.css', array(), $this->assets_version);
        wp_enqueue_script('wp-intelligence-admin', WP_INTELLIGENCE_URL . 'admin/js/admin.js', array('jquery'), $this->assets_version, true);
        wp_localize_script('wp-intelligence-admin', 'wpiAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wp_intelligence'),
            'i18n'    => array(
                'scanning'  => __('Scanning...', 'wp-intelligence'),
                'error'     => __('An error occurred.', 'wp-intelligence'),
                'confirm'   => __('Are you sure?', 'wp-intelligence'),
            ),
        ));
    }

    public function render_dashboard() {
        $this->check_access();
        ?>
        <div class="wrap wpi-wrap">
            <?php $this->render_page_header(__('Dashboard', 'wp-intelligence'), __('Intelligence overview for your WordPress website.', 'wp-intelligence')); ?>
            <div class="wpi-dashboard-grid">
                <?php $this->render_dashboard_health(); ?>
                <?php $this->render_dashboard_attention(); ?>
                <?php $this->render_dashboard_activity(); ?>
                <?php $this->render_dashboard_business(); ?>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_health() {
        $scan_running = get_transient('wpi_scan_status');
        ?>
        <div class="wpi-card wpi-card-health">
            <h2><?php esc_html_e('Website Health', 'wp-intelligence'); ?></h2>
            <?php if ('running' === $scan_running) : ?>
                <div class="wpi-health-scanning">
                    <p><?php esc_html_e('Scanning...', 'wp-intelligence'); ?></p>
                </div>
            <?php else : ?>
                <div class="wpi-health-score">
                    <span class="wpi-score-number">--</span>
                    <span class="wpi-score-label"><?php esc_html_e('Run a scan to see your score', 'wp-intelligence'); ?></span>
                </div>
                <button type="button" class="button button-primary wpi-run-scan"><?php esc_html_e('Run First Scan', 'wp-intelligence'); ?></button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_dashboard_attention() {
        ?>
        <div class="wpi-card wpi-card-attention">
            <h2><?php esc_html_e('Attention Required', 'wp-intelligence'); ?></h2>
            <div class="wpi-attention-items">
                <p class="wpi-empty-text"><?php esc_html_e('No issues detected. Run a scan to check your website.', 'wp-intelligence'); ?></p>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_activity() {
        ?>
        <div class="wpi-card wpi-card-activity">
            <h2><?php esc_html_e('Recent Activity', 'wp-intelligence'); ?></h2>
            <div class="wpi-activity-feed">
                <p class="wpi-empty-text"><?php esc_html_e('No recent activity recorded.', 'wp-intelligence'); ?></p>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_business() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="wpi-card wpi-card-business">
                <h2><?php esc_html_e('Business Snapshot', 'wp-intelligence'); ?></h2>
                <div class="wpi-empty-state">
                    <p><?php esc_html_e('WooCommerce was not detected.', 'wp-intelligence'); ?></p>
                    <p><?php esc_html_e('Install or connect a supported commerce system to unlock business insights.', 'wp-intelligence'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        ?>
        <div class="wpi-card wpi-card-business">
            <h2><?php esc_html_e('Business Snapshot', 'wp-intelligence'); ?></h2>
            <p class="wpi-empty-text"><?php esc_html_e('Business data will appear here after a scan.', 'wp-intelligence'); ?></p>
        </div>
        <?php
    }

    public function render_analyzer() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Website Analyzer', 'wp-intelligence'), __('Scan and understand your WordPress website.', 'wp-intelligence'));
        $this->render_empty_state(
            __('Website Analyzer', 'wp-intelligence'),
            __('This module scans your entire WordPress installation and builds a human-readable map of how your website works — including plugins, themes, content, integrations, and dependencies.', 'wp-intelligence')
        );
        $this->render_page_wrapper_end();
    }

    public function render_changes() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Change Recorder', 'wp-intelligence'), __('Track all meaningful changes to your website.', 'wp-intelligence'));
        $this->render_empty_state(
            __('Change Recorder', 'wp-intelligence'),
            __('This module tracks plugin updates, theme changes, content modifications, user activity, and WooCommerce events — giving you a complete history of what changed and who changed it.', 'wp-intelligence')
        );
        $this->render_page_wrapper_end();
    }

    public function render_doctor() {
        $this->check_access('manage_wp_intelligence_diagnostics');
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Emergency Doctor', 'wp-intelligence'), __('Detect, explain, and help resolve WordPress problems.', 'wp-intelligence'));
        $this->render_empty_state(
            __('Emergency Doctor', 'wp-intelligence'),
            __('This module monitors PHP errors, plugin conflicts, and website issues — then provides evidence-based diagnostics and safe recovery actions to help you fix problems quickly.', 'wp-intelligence')
        );
        $this->render_page_wrapper_end();
    }

    public function render_requests() {
        $this->check_access('manage_wp_intelligence_requests');
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Client Requests', 'wp-intelligence'), __('Manage client requests and track their progress.', 'wp-intelligence'));
        $this->render_empty_state(
            __('Client Request Inbox', 'wp-intelligence'),
            __('This module provides a ticket system where clients can submit change requests without needing WordPress admin access. Track status, communicate, and manage requests in one place.', 'wp-intelligence')
        );
        $this->render_page_wrapper_end();
    }

    public function render_handover() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Client Handover', 'wp-intelligence'), __('Generate documentation for your WordPress website.', 'wp-intelligence'));
        $this->render_empty_state(
            __('Client Handover', 'wp-intelligence'),
            __('This module automatically generates comprehensive website documentation based on your actual installation — including how to manage content, WooCommerce, users, and maintenance.', 'wp-intelligence')
        );
        $this->render_page_wrapper_end();
    }

    public function render_settings() {
        if (!class_exists('WP_Intelligence_Settings')) {
            return;
        }
        $settings = new WP_Intelligence_Settings();
        $settings->render_settings_page();
    }

    public function render_page_header($title, $description = '') {
        ?>
        <div class="wpi-page-header">
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($description) : ?>
                <p class="wpi-page-description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_page_wrapper_start() {
        echo '<div class="wrap wpi-wrap">';
    }

    public function render_page_wrapper_end() {
        echo '</div>';
    }

    public function render_empty_state($title, $message) {
        ?>
        <div class="wpi-empty-state-box">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    public function render_error_state($title, $message) {
        ?>
        <div class="wpi-error-state">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    public function ajax_run_scan() {
        check_ajax_referer('wp_intelligence');
        if (!current_user_can('manage_wp_intelligence')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-intelligence')));
        }
        set_transient('wpi_scan_status', 'running', 300);
        wp_send_json_success(array('message' => __('Scan started.', 'wp-intelligence')));
    }

    public function ajax_dismiss_notice() {
        check_ajax_referer('wp_intelligence');
        $notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';
        if ($notice_id) {
            $dismissed = get_option('wpi_dismissed_notices', array());
            $dismissed[] = $notice_id;
            update_option('wpi_dismissed_notices', array_unique($dismissed));
        }
        wp_send_json_success();
    }

    public function check_access($capability = 'manage_wp_intelligence') {
        if (!current_user_can($capability)) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'wp-intelligence'),
                esc_html__('Access Denied', 'wp-intelligence'),
                array('response' => 403)
            );
        }
    }
}
