<?php
/**
 * Structured Logger
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

class Veyra_Logger {

    const DEBUG    = 'debug';
    const INFO     = 'info';
    const WARNING  = 'warning';
    const ERROR    = 'error';
    const CRITICAL = 'critical';

    private $min_level;
    private $levels = array(
        'debug'    => 0,
        'info'     => 1,
        'warning'  => 2,
        'error'    => 3,
        'critical' => 4,
    );

    public function __construct() {
        $this->min_level = get_option('veyra_log_level', 'warning');
    }

    public function log($level, $message, $context = array()) {
        $level = strtolower($level);
        if (!isset($this->levels[$level])) {
            return false;
        }
        if (!$this->should_log($level)) {
            return false;
        }
        return $this->store($level, $message, $context);
    }

    public function debug($message, $context = array()) {
        return $this->log(self::DEBUG, $message, $context);
    }

    public function info($message, $context = array()) {
        return $this->log(self::INFO, $message, $context);
    }

    public function warning($message, $context = array()) {
        return $this->log(self::WARNING, $message, $context);
    }

    public function error($message, $context = array()) {
        return $this->log(self::ERROR, $message, $context);
    }

    public function critical($message, $context = array()) {
        return $this->log(self::CRITICAL, $message, $context);
    }

    private function should_log($level) {
        if (!isset($this->levels[$level]) || !isset($this->levels[$this->min_level])) {
            return false;
        }
        return $this->levels[$level] >= $this->levels[$this->min_level];
    }

    private function store($level, $message, $context) {
        global $wpdb;
        $table = $wpdb->prefix . 'veyra_events';
        $result = $wpdb->insert(
            $table,
            array(
                'event_type' => 'log',
                'severity'   => $level,
                'summary'    => sanitize_text_field($message),
                'details'    => wp_json_encode($this->sanitize_context($context)),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        return $result ? $wpdb->insert_id : false;
    }

    private function sanitize_context($context) {
        if (!is_array($context)) {
            return array('data' => $context);
        }
        $sensitive_keys = array('password', 'api_key', 'apikey', 'token', 'secret', 'key', 'credential', 'auth', 'nonce');
        $sanitized = array();
        foreach ($context as $k => $v) {
            if (in_array(strtolower($k), $sensitive_keys, true)) {
                $sanitized[$k] = '***REDACTED***';
            } elseif (is_array($v)) {
                $sanitized[$k] = $this->sanitize_context($v);
            } else {
                $sanitized[$k] = $v;
            }
        }
        return $sanitized;
    }

    public function get_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'veyra_events';
        $defaults = array(
            'level'     => '',
            'date_from' => '',
            'date_to'   => '',
            'search'    => '',
            'per_page'  => 20,
            'page'      => 1,
            'orderby'   => 'created_at',
            'order'     => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        $where = array("event_type = 'log'");
        $values = array();

        if (!empty($args['level'])) {
            $where[] = 'severity = %s';
            $values[] = $args['level'];
        }
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'];
        }
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'];
        }
        if (!empty($args['search'])) {
            $where[] = 'summary LIKE %s';
            $values[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        if (!empty($values)) {
            $values[] = $args['per_page'];
            $values[] = $offset;
            $sql = $wpdb->prepare("SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d", $values);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d", $args['per_page'], $offset);
        }
        return $wpdb->get_results($sql);
    }

    public function get_log($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}veyra_events WHERE id = %d AND event_type = 'log'",
            absint($id)
        ));
    }

    public function count_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'veyra_events';
        $where = array("event_type = 'log'");
        $values = array();
        if (!empty($args['level'])) {
            $where[] = 'severity = %s';
            $values[] = $args['level'];
        }
        $where_clause = 'WHERE ' . implode(' AND ', $where);
        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} {$where_clause}", $values));
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");
    }

    public function get_recent_logs($count = 10) {
        return $this->get_logs(array('per_page' => $count, 'page' => 1));
    }

    public function cleanup($retention_days = null) {
        global $wpdb;
        if (null === $retention_days) {
            $retention_days = (int) get_option('veyra_retention_period', 90);
        }
        if ($retention_days <= 0) {
            return 0;
        }
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log' AND created_at < %s",
            $date
        ));
    }

    public function get_oldest_log_date() {
        global $wpdb;
        return $wpdb->get_var("SELECT MIN(created_at) FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log'");
    }

    public function get_total_log_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log'");
    }

    public function get_log_counts_by_level() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT severity, COUNT(*) as cnt FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log' GROUP BY severity");
        $counts = array_fill_keys(array_keys($this->levels), 0);
        foreach ($results as $r) {
            if (isset($counts[$r->severity])) {
                $counts[$r->severity] = (int) $r->cnt;
            }
        }
        return $counts;
    }

    public function get_logs_today() {
        global $wpdb;
        $today = current_time('Y-m-d');
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log' AND created_at >= %s",
            $today
        ));
    }

    public function has_critical_errors() {
        global $wpdb;
        $yesterday = gmdate('Y-m-d H:i:s', strtotime('-24 hours'));
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}veyra_events WHERE event_type = 'log' AND severity = 'critical' AND created_at >= %s",
            $yesterday
        ));
        return (int) $count > 0;
    }

    public function get_level_class($level) {
        $classes = array(
            'debug'    => 'veyra-log-debug',
            'info'     => 'veyra-log-info',
            'warning'  => 'veyra-log-warning',
            'error'    => 'veyra-log-error',
            'critical' => 'veyra-log-critical',
        );
        return isset($classes[$level]) ? $classes[$level] : 'veyra-log-info';
    }

    public function get_level_label($level) {
        $labels = array(
            'debug'    => __('Debug', 'veyra'),
            'info'     => __('Info', 'veyra'),
            'warning'  => __('Warning', 'veyra'),
            'error'    => __('Error', 'veyra'),
            'critical' => __('Critical', 'veyra'),
        );
        return isset($labels[$level]) ? $labels[$level] : $level;
    }

    public function format_log_entry($log) {
        $context = json_decode($log->details, true);
        return array(
            'time'    => $log->created_at,
            'level'   => $this->get_level_label($log->severity),
            'class'   => $this->get_level_class($log->severity),
            'message' => $log->summary,
            'context' => $context,
        );
    }
}
