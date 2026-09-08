<?php
/**
 * Plugin Installer
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

class Veyra_Installer {

    public function install() {
        $this->create_tables();
        $this->set_default_options();
        $this->create_capabilities();
        $this->schedule_events();
        update_option('veyra_version', VEYRA_VERSION);
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = $this->get_table_definitions($charset_collate);

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    private function get_table_definitions($charset_collate) {
        global $wpdb;
        $p = $wpdb->prefix;

        return array(
            "{$p}veyra_events" => "CREATE TABLE {$p}veyra_events (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_type VARCHAR(100) NOT NULL,
                object_type VARCHAR(100) DEFAULT '',
                object_id BIGINT(20) UNSIGNED DEFAULT 0,
                user_id BIGINT(20) UNSIGNED DEFAULT 0,
                severity VARCHAR(20) DEFAULT 'info',
                summary TEXT NOT NULL,
                details LONGTEXT,
                previous_data LONGTEXT,
                new_data LONGTEXT,
                ip_hash VARCHAR(64) DEFAULT '',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY object_type_object_id (object_type, object_id),
                KEY user_id (user_id),
                KEY created_at (created_at)
            ) $charset_collate;",

            "{$p}veyra_errors" => "CREATE TABLE {$p}veyra_errors (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                error_hash VARCHAR(64) NOT NULL,
                error_type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                file VARCHAR(500) DEFAULT '',
                line INT(11) DEFAULT 0,
                component VARCHAR(200) DEFAULT '',
                severity VARCHAR(20) DEFAULT 'error',
                occurrence_count INT(11) DEFAULT 1,
                first_seen DATETIME NOT NULL,
                last_seen DATETIME NOT NULL,
                status VARCHAR(50) DEFAULT 'new',
                resolution TEXT,
                PRIMARY KEY (id),
                UNIQUE KEY error_hash (error_hash),
                KEY severity (severity),
                KEY status (status),
                KEY component (component),
                KEY last_seen (last_seen)
            ) $charset_collate;",

            "{$p}veyra_insights" => "CREATE TABLE {$p}veyra_insights (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                insight_type VARCHAR(100) NOT NULL,
                metric_key VARCHAR(200) NOT NULL,
                metric_value TEXT,
                context LONGTEXT,
                period_start DATETIME,
                period_end DATETIME,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY insight_type (insight_type),
                KEY metric_key (metric_key),
                KEY created_at (created_at)
            ) $charset_collate;",

            "{$p}veyra_requests" => "CREATE TABLE {$p}veyra_requests (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                client_user_id BIGINT(20) UNSIGNED DEFAULT 0,
                assigned_user_id BIGINT(20) UNSIGNED DEFAULT 0,
                title VARCHAR(500) NOT NULL,
                description LONGTEXT,
                category VARCHAR(100) DEFAULT 'other',
                priority VARCHAR(50) DEFAULT 'normal',
                status VARCHAR(50) DEFAULT 'new',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                completed_at DATETIME,
                PRIMARY KEY (id),
                KEY status (status),
                KEY priority (priority),
                KEY client_user_id (client_user_id),
                KEY assigned_user_id (assigned_user_id),
                KEY created_at (created_at)
            ) $charset_collate;",

            "{$p}veyra_request_comments" => "CREATE TABLE {$p}veyra_request_comments (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                request_id BIGINT(20) UNSIGNED NOT NULL,
                user_id BIGINT(20) UNSIGNED DEFAULT 0,
                comment TEXT NOT NULL,
                attachment_url VARCHAR(500) DEFAULT '',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY request_id (request_id)
            ) $charset_collate;",

            "{$p}veyra_scans" => "CREATE TABLE {$p}veyra_scans (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                scan_type VARCHAR(50) NOT NULL,
                status VARCHAR(50) DEFAULT 'running',
                started_at DATETIME NOT NULL,
                completed_at DATETIME,
                results_summary LONGTEXT,
                PRIMARY KEY (id),
                KEY scan_type (scan_type),
                KEY status (status)
            ) $charset_collate;",

            "{$p}veyra_scan_results" => "CREATE TABLE {$p}veyra_scan_results (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                scan_id BIGINT(20) UNSIGNED NOT NULL,
                category VARCHAR(100) NOT NULL,
                item_key VARCHAR(200) NOT NULL,
                item_label VARCHAR(500) DEFAULT '',
                status VARCHAR(50) DEFAULT 'ok',
                details LONGTEXT,
                severity VARCHAR(20) DEFAULT 'info',
                PRIMARY KEY (id),
                KEY scan_id (scan_id),
                KEY category (category)
            ) $charset_collate;",

            "{$p}veyra_ai_logs" => "CREATE TABLE {$p}veyra_ai_logs (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                provider VARCHAR(100) DEFAULT '',
                model VARCHAR(100) DEFAULT '',
                prompt_hash VARCHAR(64) DEFAULT '',
                response_summary TEXT,
                tokens_used INT(11) DEFAULT 0,
                duration_ms INT(11) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'success',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY provider (provider),
                KEY status (status),
                KEY created_at (created_at)
            ) $charset_collate;",
        );
    }

    public function set_default_options() {
        $defaults = array(
            'veyra_enable_scanner'         => '1',
            'veyra_enable_change_recorder'  => '1',
            'veyra_enable_emergency_doctor' => '1',
            'veyra_enable_business_brain'   => '1',
            'veyra_enable_handover'         => '1',
            'veyra_enable_requests'         => '1',
            'veyra_scan_frequency'          => 'daily',
            'veyra_retention_period'        => '90',
            'veyra_log_level'               => 'warning',
            'veyra_error_monitoring'        => '1',
            'veyra_event_tracking'          => '1',
            'veyra_debug_mode'              => '0',
            'veyra_enable_caching'          => '1',
            'veyra_cache_duration'          => '300',
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }

    public function create_capabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $caps = array(
                'manage_veyra',
                'view_veyra',
                'manage_veyra_settings',
                'view_veyra_logs',
                'manage_veyra_requests',
                'manage_veyra_diagnostics',
            );
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public function schedule_events() {
        if (!wp_next_scheduled('veyra_daily_scan')) {
            wp_schedule_event(time(), 'daily', 'veyra_daily_scan');
        }
        if (!wp_next_scheduled('veyra_hourly_health_check')) {
            wp_schedule_event(time(), 'hourly', 'veyra_hourly_health_check');
        }
        if (!wp_next_scheduled('veyra_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'veyra_cleanup_logs');
        }
    }
}
