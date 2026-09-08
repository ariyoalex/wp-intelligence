<?php
/**
 * Admin Menu and Pages
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

class Veyra_Admin {

    private static $instance = null;
    private $assets_version;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->assets_version = VEYRA_VERSION;
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_veyra_run_scan', array($this, 'ajax_run_scan'));
        add_action('wp_ajax_veyra_dismiss_notice', array($this, 'ajax_dismiss_notice'));
    }

    public function register_menus() {
        add_menu_page(
            __('Veyra', 'veyra'),
            __('Veyra', 'veyra'),
            'manage_veyra',
            'veyra',
            array($this, 'render_dashboard'),
            'dashicons-chart-area',
            30
        );
        add_submenu_page('veyra', __('Dashboard', 'veyra'), __('Dashboard', 'veyra'), 'manage_veyra', 'veyra', array($this, 'render_dashboard'));
        add_submenu_page('veyra', __('Analyzer', 'veyra'), __('Analyzer', 'veyra'), 'manage_veyra', 'veyra-analyzer', array($this, 'render_analyzer'));
        add_submenu_page('veyra', __('Changes', 'veyra'), __('Changes', 'veyra'), 'manage_veyra', 'veyra-changes', array($this, 'render_changes'));
        add_submenu_page('veyra', __('Doctor', 'veyra'), __('Doctor', 'veyra'), 'manage_veyra_diagnostics', 'veyra-doctor', array($this, 'render_doctor'));
        add_submenu_page('veyra', __('Requests', 'veyra'), __('Requests', 'veyra'), 'manage_veyra_requests', 'veyra-requests', array($this, 'render_requests'));
        add_submenu_page('veyra', __('Handover', 'veyra'), __('Handover', 'veyra'), 'manage_veyra', 'veyra-handover', array($this, 'render_handover'));
        add_submenu_page('veyra', __('Settings', 'veyra'), __('Settings', 'veyra'), 'manage_veyra_settings', 'veyra-settings', array($this, 'render_settings'));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'veyra') === false) {
            return;
        }
        wp_enqueue_style('veyra-admin', VEYRA_URL . 'admin/css/admin.css', array(), $this->assets_version);
        wp_enqueue_script('veyra-admin', VEYRA_URL . 'admin/js/admin.js', array('jquery'), $this->assets_version, true);
        wp_localize_script('veyra-admin', 'veyraAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('veyra'),
            'i18n'    => array(
                'scanning' => __('Scanning...', 'veyra'),
                'error'    => __('An error occurred.', 'veyra'),
                'confirm'  => __('Are you sure?', 'veyra'),
            ),
        ));
    }

    public function render_dashboard() {
        $this->check_access();
        ?>
        <div class="wrap veyra-wrap">
            <?php $this->render_page_header(__('Dashboard', 'veyra'), __('Intelligence overview for your WordPress website.', 'veyra')); ?>
            <div class="veyra-dashboard-grid">
                <?php $this->render_dashboard_health(); ?>
                <?php $this->render_dashboard_attention(); ?>
                <?php $this->render_dashboard_activity(); ?>
                <?php $this->render_dashboard_business(); ?>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_health() {
        $scan_running = get_transient('veyra_scan_status');
        ?>
        <div class="veyra-card veyra-card-health">
            <h2><?php esc_html_e('Website Health', 'veyra'); ?></h2>
            <?php if ('running' === $scan_running) : ?>
                <div class="veyra-health-scanning">
                    <p><?php esc_html_e('Scanning...', 'veyra'); ?></p>
                </div>
            <?php else : ?>
                <div class="veyra-health-score">
                    <span class="veyra-score-number">--</span>
                    <span class="veyra-score-label"><?php esc_html_e('Run a scan to see your score', 'veyra'); ?></span>
                </div>
                <button type="button" class="button button-primary veyra-run-scan"><?php esc_html_e('Run First Scan', 'veyra'); ?></button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_dashboard_attention() {
        ?>
        <div class="veyra-card veyra-card-attention">
            <h2><?php esc_html_e('Attention Required', 'veyra'); ?></h2>
            <div class="veyra-attention-items">
                <p class="veyra-empty-text"><?php esc_html_e('No issues detected. Run a scan to check your website.', 'veyra'); ?></p>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_activity() {
        ?>
        <div class="veyra-card veyra-card-activity">
            <h2><?php esc_html_e('Recent Activity', 'veyra'); ?></h2>
            <div class="veyra-activity-feed">
                <p class="veyra-empty-text"><?php esc_html_e('No recent activity recorded.', 'veyra'); ?></p>
            </div>
        </div>
        <?php
    }

    private function render_dashboard_business() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="veyra-card veyra-card-business">
                <h2><?php esc_html_e('Business Snapshot', 'veyra'); ?></h2>
                <div class="veyra-empty-state">
                    <p><?php esc_html_e('WooCommerce was not detected.', 'veyra'); ?></p>
                    <p><?php esc_html_e('Install or connect a supported commerce system to unlock business insights.', 'veyra'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        ?>
        <div class="veyra-card veyra-card-business">
            <h2><?php esc_html_e('Business Snapshot', 'veyra'); ?></h2>
            <p class="veyra-empty-text"><?php esc_html_e('Business data will appear here after a scan.', 'veyra'); ?></p>
        </div>
        <?php
    }

    public function render_analyzer() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Website Analyzer', 'veyra'), __('Scan and understand your WordPress website.', 'veyra'));
        $this->render_empty_state(
            __('Website Analyzer', 'veyra'),
            __('This module scans your entire WordPress installation and builds a human-readable map of how your website works — including plugins, themes, content, integrations, and dependencies.', 'veyra')
        );
        $this->render_page_wrapper_end();
    }

    public function render_changes() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Change Recorder', 'veyra'), __('Track all meaningful changes to your website.', 'veyra'));
        $this->render_empty_state(
            __('Change Recorder', 'veyra'),
            __('This module tracks plugin updates, theme changes, content modifications, user activity, and WooCommerce events — giving you a complete history of what changed and who changed it.', 'veyra')
        );
        $this->render_page_wrapper_end();
    }

    public function render_doctor() {
        $this->check_access('manage_veyra_diagnostics');
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Emergency Doctor', 'veyra'), __('Detect, explain, and help resolve WordPress problems.', 'veyra'));
        $this->render_empty_state(
            __('Emergency Doctor', 'veyra'),
            __('This module monitors PHP errors, plugin conflicts, and website issues — then provides evidence-based diagnostics and safe recovery actions to help you fix problems quickly.', 'veyra')
        );
        $this->render_page_wrapper_end();
    }

    public function render_requests() {
        $this->check_access('manage_veyra_requests');
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Client Requests', 'veyra'), __('Manage client requests and track their progress.', 'veyra'));
        $this->render_empty_state(
            __('Client Request Inbox', 'veyra'),
            __('This module provides a ticket system where clients can submit change requests without needing WordPress admin access. Track status, communicate, and manage requests in one place.', 'veyra')
        );
        $this->render_page_wrapper_end();
    }

    public function render_handover() {
        $this->check_access();
        $this->render_page_wrapper_start();
        $this->render_page_header(__('Client Handover', 'veyra'), __('Generate documentation for your WordPress website.', 'veyra'));
        $this->render_empty_state(
            __('Client Handover', 'veyra'),
            __('This module automatically generates comprehensive website documentation based on your actual installation — including how to manage content, WooCommerce, users, and maintenance.', 'veyra')
        );
        $this->render_page_wrapper_end();
    }

    public function render_settings() {
        if (!class_exists('Veyra_Settings')) {
            return;
        }
        $settings = new Veyra_Settings();
        $settings->render_settings_page();
    }

    public function render_page_header($title, $description = '') {
        ?>
        <div class="veyra-page-header">
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($description) : ?>
                <p class="veyra-page-description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_page_wrapper_start() {
        echo '<div class="wrap veyra-wrap">';
    }

    public function render_page_wrapper_end() {
        echo '</div>';
    }

    public function render_empty_state($title, $message) {
        ?>
        <div class="veyra-empty-state-box">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    public function render_error_state($title, $message) {
        ?>
        <div class="veyra-error-state">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    public function ajax_run_scan() {
        check_ajax_referer('veyra');
        if (!current_user_can('manage_veyra')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'veyra')));
        }
        set_transient('veyra_scan_status', 'running', 300);
        wp_send_json_success(array('message' => __('Scan started.', 'veyra')));
    }

    public function ajax_dismiss_notice() {
        check_ajax_referer('veyra');
        $notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';
        if ($notice_id) {
            $dismissed = get_option('veyra_dismissed_notices', array());
            $dismissed[] = $notice_id;
            update_option('veyra_dismissed_notices', array_unique($dismissed));
        }
        wp_send_json_success();
    }

    public function check_access($capability = 'manage_veyra') {
        if (!current_user_can($capability)) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'veyra'),
                esc_html__('Access Denied', 'veyra'),
                array('response' => 403)
            );
        }
    }
}
