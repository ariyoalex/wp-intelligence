<?php
/**
 * Settings Management
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Settings {

    private $option_prefix = 'wp_intelligence_';

    public function __construct() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'register_all_settings'));
        }
    }

    public function register_all_settings() {
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_scanner', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_change_recorder', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_emergency_doctor', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_business_brain', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_handover', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'enable_requests', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'scan_frequency', array('default' => 'daily', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_general', $this->option_prefix . 'retention_period', array('default' => '90', 'sanitize_callback' => 'sanitize_text_field'));

        register_setting('wp_intelligence_monitoring', $this->option_prefix . 'error_monitoring', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_monitoring', $this->option_prefix . 'event_tracking', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_monitoring', $this->option_prefix . 'log_level', array('default' => 'warning', 'sanitize_callback' => 'sanitize_text_field'));

        register_setting('wp_intelligence_notifications', $this->option_prefix . 'email_notifications', array('default' => '0', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_notifications', $this->option_prefix . 'notify_critical_errors', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_notifications', $this->option_prefix . 'notify_plugin_conflicts', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_notifications', $this->option_prefix . 'severity_threshold', array('default' => 'high', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_notifications', $this->option_prefix . 'notification_recipients', array('default' => '', 'sanitize_callback' => 'sanitize_textarea_field'));

        register_setting('wp_intelligence_security', $this->option_prefix . 'enable_rate_limiting', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_security', $this->option_prefix . 'rate_limit_max', array('default' => '60', 'sanitize_callback' => 'absint'));
        register_setting('wp_intelligence_security', $this->option_prefix . 'rate_limit_window', array('default' => '60', 'sanitize_callback' => 'absint'));

        register_setting('wp_intelligence_advanced', $this->option_prefix . 'debug_mode', array('default' => '0', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_advanced', $this->option_prefix . 'enable_caching', array('default' => '1', 'sanitize_callback' => 'sanitize_text_field'));
        register_setting('wp_intelligence_advanced', $this->option_prefix . 'cache_duration', array('default' => '300', 'sanitize_callback' => 'absint'));

        $this->register_settings_fields();
    }

    private function register_settings_fields() {
        add_settings_section('wpi_general_section', '', null, 'wp-intelligence-settings-general');
        add_settings_field($this->option_prefix . 'scan_frequency', __('Scan Frequency', 'wp-intelligence'), array($this, 'render_select_field'), 'wp-intelligence-settings-general', 'wpi_general_section', array(
            'name'    => $this->option_prefix . 'scan_frequency',
            'group'   => 'wp_intelligence_general',
            'options' => array('hourly' => __('Hourly', 'wp-intelligence'), 'twice_daily' => __('Twice Daily', 'wp-intelligence'), 'daily' => __('Daily', 'wp-intelligence'), 'weekly' => __('Weekly', 'wp-intelligence')),
        ));
        add_settings_field($this->option_prefix . 'retention_period', __('Data Retention (days)', 'wp-intelligence'), array($this, 'render_select_field'), 'wp-intelligence-settings-general', 'wpi_general_section', array(
            'name'    => $this->option_prefix . 'retention_period',
            'group'   => 'wp_intelligence_general',
            'options' => array('7' => '7', '30' => '30', '90' => '90', '180' => '180', '365' => '365', '0' => __('Unlimited', 'wp-intelligence')),
        ));

        add_settings_section('wpi_monitoring_section', '', null, 'wp-intelligence-settings-monitoring');
        add_settings_field($this->option_prefix . 'log_level', __('Log Level', 'wp-intelligence'), array($this, 'render_select_field'), 'wp-intelligence-settings-monitoring', 'wpi_monitoring_section', array(
            'name'    => $this->option_prefix . 'log_level',
            'group'   => 'wp_intelligence_monitoring',
            'options' => array('debug' => __('Debug', 'wp-intelligence'), 'info' => __('Info', 'wp-intelligence'), 'warning' => __('Warning', 'wp-intelligence'), 'error' => __('Error', 'wp-intelligence'), 'critical' => __('Critical', 'wp-intelligence')),
        ));

        add_settings_section('wpi_notifications_section', '', null, 'wp-intelligence-settings-notifications');
        add_settings_field($this->option_prefix . 'severity_threshold', __('Notify at Severity', 'wp-intelligence'), array($this, 'render_select_field'), 'wp-intelligence-settings-notifications', 'wpi_notifications_section', array(
            'name'    => $this->option_prefix . 'severity_threshold',
            'group'   => 'wp_intelligence_notifications',
            'options' => array('critical' => __('Critical Only', 'wp-intelligence'), 'high' => __('High and above', 'wp-intelligence'), 'medium' => __('Medium and above', 'wp-intelligence'), 'low' => __('All', 'wp-intelligence')),
        ));
        add_settings_field($this->option_prefix . 'notification_recipients', __('Email Recipients', 'wp-intelligence'), array($this, 'render_textarea_field'), 'wp-intelligence-settings-notifications', 'wpi_notifications_section', array(
            'name'  => $this->option_prefix . 'notification_recipients',
            'group' => 'wp_intelligence_notifications',
            'desc'  => __('One email per line.', 'wp-intelligence'),
        ));

        add_settings_section('wpi_advanced_section', '', null, 'wp-intelligence-settings-advanced');
        add_settings_field($this->option_prefix . 'cache_duration', __('Cache Duration (seconds)', 'wp-intelligence'), array($this, 'render_number_field'), 'wp-intelligence-settings-advanced', 'wpi_advanced_section', array(
            'name'  => $this->option_prefix . 'cache_duration',
            'group' => 'wp_intelligence_advanced',
        ));
    }

    public function get($key, $default = false) {
        return get_option($this->option_prefix . $key, $default);
    }

    public function set($key, $value) {
        return update_option($this->option_prefix . $key, $value);
    }

    public function delete($key) {
        return delete_option($this->option_prefix . $key);
    }

    public function is_module_enabled($module) {
        $key = 'enable_' . $module;
        return '1' === $this->get($key, '1');
    }

    public function get_retention_days() {
        return (int) $this->get('retention_period', 90);
    }

    public function is_debug_mode() {
        return '1' === $this->get('debug_mode', '0');
    }

    public function is_caching_enabled() {
        return '1' === $this->get('enable_caching', '1');
    }

    public function get_notification_recipients() {
        $raw = $this->get('notification_recipients', '');
        $emails = array_map('trim', explode("\n", $raw));
        return array_filter($emails, 'is_email');
    }

    public function render_select_field($args) {
        $value = $this->get($args['name'], '');
        printf('<select name="%s" id="%s">', esc_attr($args['group'] . '[' . $args['name'] . ']'), esc_attr($args['name']));
        foreach ($args['options'] as $k => $v) {
            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($value, $k, false), esc_html($v));
        }
        echo '</select>';
    }

    public function render_textarea_field($args) {
        $value = $this->get($args['name'], '');
        printf('<textarea name="%s" id="%s" rows="3" class="large-text">%s</textarea>', esc_attr($args['group'] . '[' . $args['name'] . ']'), esc_attr($args['name']), esc_textarea($value));
        if (!empty($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    public function render_number_field($args) {
        $value = $this->get($args['name'], '');
        printf('<input type="number" name="%s" id="%s" value="%s" min="0" class="small-text" />', esc_attr($args['group'] . '[' . $args['name'] . ']'), esc_attr($args['name']), esc_attr($value));
    }

    public function render_settings_page() {
        $this->admin_only();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Intelligence Settings', 'wp-intelligence'); ?></h1>
            <?php settings_errors(); ?>
            <nav class="nav-tab-wrapper">
                <a href="?page=wp-intelligence-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'wp-intelligence'); ?></a>
                <a href="?page=wp-intelligence-settings&tab=monitoring" class="nav-tab <?php echo $active_tab === 'monitoring' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Monitoring', 'wp-intelligence'); ?></a>
                <a href="?page=wp-intelligence-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Notifications', 'wp-intelligence'); ?></a>
                <a href="?page=wp-intelligence-settings&tab=security" class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Security', 'wp-intelligence'); ?></a>
                <a href="?page=wp-intelligence-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced', 'wp-intelligence'); ?></a>
            </nav>
            <form method="post" action="options.php">
                <?php
                $group = 'wp_intelligence_' . $active_tab;
                settings_fields($group);
                do_settings_sections('wp-intelligence-settings-' . $active_tab);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function admin_only() {
        if (!current_user_can('manage_wp_intelligence_settings')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wp-intelligence'));
        }
    }
}
