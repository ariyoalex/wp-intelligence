<?php
/**
 * Database Abstraction Layer
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Database {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function events_table() {
        return $this->wpdb->prefix . 'wpi_events';
    }

    public function errors_table() {
        return $this->wpdb->prefix . 'wpi_errors';
    }

    public function insights_table() {
        return $this->wpdb->prefix . 'wpi_insights';
    }

    public function requests_table() {
        return $this->wpdb->prefix . 'wpi_requests';
    }

    public function request_comments_table() {
        return $this->wpdb->prefix . 'wpi_request_comments';
    }

    public function scans_table() {
        return $this->wpdb->prefix . 'wpi_scans';
    }

    public function scan_results_table() {
        return $this->wpdb->prefix . 'wpi_scan_results';
    }

    public function ai_logs_table() {
        return $this->wpdb->prefix . 'wpi_ai_logs';
    }

    public function table_exists($table) {
        $result = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table));
        return null !== $result;
    }

    public function insert_event($event_type, $object_type, $object_id, $user_id, $severity, $summary, $details = '', $previous_data = '', $new_data = '', $ip_hash = '') {
        if (!$this->table_exists($this->events_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->events_table(),
            array(
                'event_type'    => sanitize_text_field($event_type),
                'object_type'   => sanitize_text_field($object_type),
                'object_id'     => absint($object_id),
                'user_id'       => absint($user_id),
                'severity'      => sanitize_text_field($severity),
                'summary'       => sanitize_text_field($summary),
                'details'       => wp_json_encode($details),
                'previous_data' => wp_json_encode($previous_data),
                'new_data'      => wp_json_encode($new_data),
                'ip_hash'       => sanitize_text_field($ip_hash),
                'created_at'    => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_events($args = array()) {
        if (!$this->table_exists($this->events_table())) {
            return array();
        }
        $defaults = array(
            'event_type' => '',
            'object_type' => '',
            'user_id'    => 0,
            'severity'   => '',
            'date_from'  => '',
            'date_to'    => '',
            'per_page'   => 20,
            'page'       => 1,
            'orderby'    => 'created_at',
            'order'      => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->events_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = esc_sql($args['orderby']);
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT * FROM {$this->events_table()} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function get_event($id) {
        if (!$this->table_exists($this->events_table())) {
            return null;
        }
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->events_table()} WHERE id = %d", absint($id)));
    }

    public function count_events($args = array()) {
        if (!$this->table_exists($this->events_table())) {
            return 0;
        }
        $where = $this->build_where_clause($args, $this->events_table());
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->events_table()} {$where}");
    }

    public function insert_error($error_hash, $error_type, $message, $file, $line, $component, $severity) {
        if (!$this->table_exists($this->errors_table())) {
            return false;
        }
        $existing = $this->get_error_by_hash($error_hash);
        if ($existing) {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->errors_table()} SET occurrence_count = occurrence_count + 1, last_seen = %s WHERE id = %d",
                current_time('mysql'),
                $existing->id
            ));
            return $existing->id;
        }
        $result = $this->wpdb->insert(
            $this->errors_table(),
            array(
                'error_hash'   => sanitize_text_field($error_hash),
                'error_type'   => sanitize_text_field($error_type),
                'message'      => sanitize_textarea_field($message),
                'file'         => sanitize_text_field($file),
                'line'         => absint($line),
                'component'    => sanitize_text_field($component),
                'severity'     => sanitize_text_field($severity),
                'first_seen'   => current_time('mysql'),
                'last_seen'    => current_time('mysql'),
                'status'       => 'new',
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_errors($args = array()) {
        if (!$this->table_exists($this->errors_table())) {
            return array();
        }
        $defaults = array(
            'error_type' => '',
            'severity'   => '',
            'status'     => '',
            'component'  => '',
            'per_page'   => 20,
            'page'       => 1,
            'orderby'    => 'last_seen',
            'order'      => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->errors_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = esc_sql($args['orderby']);
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT * FROM {$this->errors_table()} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function get_error($id) {
        if (!$this->table_exists($this->errors_table())) {
            return null;
        }
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->errors_table()} WHERE id = %d", absint($id)));
    }

    public function get_error_by_hash($hash) {
        if (!$this->table_exists($this->errors_table())) {
            return null;
        }
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->errors_table()} WHERE error_hash = %s", sanitize_text_field($hash)));
    }

    public function update_error_status($id, $status) {
        return $this->wpdb->update(
            $this->errors_table(),
            array('status' => sanitize_text_field($status)),
            array('id' => absint($id)),
            array('%s'),
            array('%d')
        );
    }

    public function count_errors($args = array()) {
        if (!$this->table_exists($this->errors_table())) {
            return 0;
        }
        $where = $this->build_where_clause($args, $this->errors_table());
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->errors_table()} {$where}");
    }

    public function insert_insight($insight_type, $metric_key, $metric_value, $context, $period_start, $period_end) {
        if (!$this->table_exists($this->insights_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->insights_table(),
            array(
                'insight_type' => sanitize_text_field($insight_type),
                'metric_key'   => sanitize_text_field($metric_key),
                'metric_value' => sanitize_text_field($metric_value),
                'context'      => wp_json_encode($context),
                'period_start' => sanitize_text_field($period_start),
                'period_end'   => sanitize_text_field($period_end),
                'created_at'   => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_insights($args = array()) {
        if (!$this->table_exists($this->insights_table())) {
            return array();
        }
        $defaults = array(
            'insight_type' => '',
            'metric_key'   => '',
            'per_page'     => 20,
            'page'         => 1,
            'orderby'      => 'created_at',
            'order'        => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->insights_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT * FROM {$this->insights_table()} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function insert_request($data) {
        if (!$this->table_exists($this->requests_table())) {
            return false;
        }
        $now = current_time('mysql');
        $insert = array(
            'client_user_id'  => absint($data['client_user_id'] ?? 0),
            'assigned_user_id' => absint($data['assigned_user_id'] ?? 0),
            'title'           => sanitize_text_field($data['title'] ?? ''),
            'description'     => sanitize_textarea_field($data['description'] ?? ''),
            'category'        => sanitize_text_field($data['category'] ?? 'other'),
            'priority'        => sanitize_text_field($data['priority'] ?? 'normal'),
            'status'          => sanitize_text_field($data['status'] ?? 'new'),
            'created_at'      => $now,
            'updated_at'      => $now,
        );
        $result = $this->wpdb->insert($this->requests_table(), $insert);
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_requests($args = array()) {
        if (!$this->table_exists($this->requests_table())) {
            return array();
        }
        $defaults = array(
            'status'          => '',
            'priority'        => '',
            'category'        => '',
            'client_user_id'  => 0,
            'assigned_user_id' => 0,
            'per_page'        => 20,
            'page'            => 1,
            'orderby'         => 'created_at',
            'order'           => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->requests_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT * FROM {$this->requests_table()} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function get_request($id) {
        if (!$this->table_exists($this->requests_table())) {
            return null;
        }
        $request = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->requests_table()} WHERE id = %d", absint($id)));
        if ($request) {
            $request->comments = $this->get_comments($id);
        }
        return $request;
    }

    public function update_request($id, $data) {
        if (!$this->table_exists($this->requests_table())) {
            return false;
        }
        $update = array();
        $format = array();
        $allowed = array('client_user_id', 'assigned_user_id', 'title', 'description', 'category', 'priority', 'status', 'completed_at');
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $update[$field] = sanitize_text_field($data[$field]);
                $format[] = '%s';
            }
        }
        $update['updated_at'] = current_time('mysql');
        $format[] = '%s';
        return $this->wpdb->update($this->requests_table(), $update, array('id' => absint($id)), $format, array('%d'));
    }

    public function delete_request($id) {
        return $this->wpdb->delete($this->requests_table(), array('id' => absint($id)), array('%d'));
    }

    public function insert_comment($request_id, $user_id, $comment, $attachment_url = '') {
        if (!$this->table_exists($this->request_comments_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->request_comments_table(),
            array(
                'request_id'     => absint($request_id),
                'user_id'        => absint($user_id),
                'comment'        => sanitize_textarea_field($comment),
                'attachment_url' => esc_url_raw($attachment_url),
                'created_at'     => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_comments($request_id) {
        if (!$this->table_exists($this->request_comments_table())) {
            return array();
        }
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT c.*, u.display_name FROM {$this->request_comments_table()} c LEFT JOIN {$this->wpdb->users} u ON c.user_id = u.ID WHERE c.request_id = %d ORDER BY c.created_at ASC",
            absint($request_id)
        ));
    }

    public function insert_scan($scan_type) {
        if (!$this->table_exists($this->scans_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->scans_table(),
            array(
                'scan_type'  => sanitize_text_field($scan_type),
                'status'     => 'running',
                'started_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function update_scan($id, $data) {
        if (!$this->table_exists($this->scans_table())) {
            return false;
        }
        return $this->wpdb->update($this->scans_table(), $data, array('id' => absint($id)));
    }

    public function get_scans($args = array()) {
        if (!$this->table_exists($this->scans_table())) {
            return array();
        }
        $defaults = array('scan_type' => '', 'status' => '', 'per_page' => 20, 'page' => 1);
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->scans_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT * FROM {$this->scans_table()} {$where} ORDER BY started_at DESC LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function insert_scan_result($scan_id, $category, $item_key, $item_label, $status, $details, $severity) {
        if (!$this->table_exists($this->scan_results_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->scan_results_table(),
            array(
                'scan_id'    => absint($scan_id),
                'category'   => sanitize_text_field($category),
                'item_key'   => sanitize_text_field($item_key),
                'item_label' => sanitize_text_field($item_label),
                'status'     => sanitize_text_field($status),
                'details'    => wp_json_encode($details),
                'severity'   => sanitize_text_field($severity),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_scan_results($scan_id, $category = '') {
        if (!$this->table_exists($this->scan_results_table())) {
            return array();
        }
        if ($category) {
            return $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM {$this->scan_results_table()} WHERE scan_id = %d AND category = %s ORDER BY id ASC",
                absint($scan_id),
                sanitize_text_field($category)
            ));
        }
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->scan_results_table()} WHERE scan_id = %d ORDER BY id ASC",
            absint($scan_id)
        ));
    }

    public function insert_ai_log($provider, $model, $prompt_hash, $response_summary, $tokens_used, $duration_ms, $status) {
        if (!$this->table_exists($this->ai_logs_table())) {
            return false;
        }
        $result = $this->wpdb->insert(
            $this->ai_logs_table(),
            array(
                'provider'         => sanitize_text_field($provider),
                'model'            => sanitize_text_field($model),
                'prompt_hash'      => sanitize_text_field($prompt_hash),
                'response_summary' => sanitize_textarea_field($response_summary),
                'tokens_used'      => absint($tokens_used),
                'duration_ms'      => absint($duration_ms),
                'status'           => sanitize_text_field($status),
                'created_at'       => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        return $result ? $this->wpdb->insert_id : false;
    }

    public function get_ai_logs($args = array()) {
        if (!$this->table_exists($this->ai_logs_table())) {
            return array();
        }
        $defaults = array('provider' => '', 'status' => '', 'per_page' => 20, 'page' => 1);
        $args = wp_parse_args($args, $defaults);
        $where = $this->build_where_clause($args, $this->ai_logs_table());
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = "SELECT * FROM {$this->ai_logs_table()} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $sql = $this->wpdb->prepare($sql, $args['per_page'], $offset);
        return $this->wpdb->get_results($sql);
    }

    public function cleanup_old_events($retention_days) {
        if (!$this->table_exists($this->events_table()) || $retention_days <= 0) {
            return 0;
        }
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        return $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->events_table()} WHERE created_at < %s", $date));
    }

    public function cleanup_old_errors($retention_days) {
        if (!$this->table_exists($this->errors_table()) || $retention_days <= 0) {
            return 0;
        }
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        return $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->errors_table()} WHERE last_seen < %s", $date));
    }

    public function cleanup_old_scans($retention_days) {
        if (!$this->table_exists($this->scans_table()) || $retention_days <= 0) {
            return 0;
        }
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $old_scans = $this->wpdb->get_col($this->wpdb->prepare("SELECT id FROM {$this->scans_table()} WHERE started_at < %s", $date));
        if (!empty($old_scans)) {
            $placeholders = implode(',', array_fill(0, count($old_scans), '%d'));
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->scan_results_table()} WHERE scan_id IN ({$placeholders})", $old_scans));
            return $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->scans_table()} WHERE id IN ({$placeholders})", $old_scans));
        }
        return 0;
    }

    public function cleanup_old_ai_logs($retention_days) {
        if (!$this->table_exists($this->ai_logs_table()) || $retention_days <= 0) {
            return 0;
        }
        $date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        return $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->ai_logs_table()} WHERE created_at < %s", $date));
    }

    private function build_where_clause($args, $table) {
        $where = array();
        $map = array(
            'event_type'      => 'event_type',
            'object_type'     => 'object_type',
            'user_id'         => 'user_id',
            'severity'        => 'severity',
            'error_type'      => 'error_type',
            'status'          => 'status',
            'component'       => 'component',
            'insight_type'    => 'insight_type',
            'metric_key'      => 'metric_key',
            'scan_type'       => 'scan_type',
            'provider'        => 'provider',
            'client_user_id'  => 'client_user_id',
            'assigned_user_id' => 'assigned_user_id',
            'category'        => 'category',
            'priority'        => 'priority',
            'date_from'       => 'created_at >=',
            'date_to'         => 'created_at <=',
        );
        foreach ($map as $key => $column) {
            if (!empty($args[$key]) && isset($args[$key])) {
                if (strpos($column, '>=') !== false || strpos($column, '<=') !== false) {
                    $col = trim(str_replace(array('>=', '<='), '', $column));
                    $op = strpos($column, '>=') !== false ? '>=' : '<=';
                    $where[] = "{$table}.{$col} {$op} '%s'";
                } elseif (is_numeric($args[$key])) {
                    $where[] = "{$table}.{$column} = %d";
                } else {
                    $where[] = "{$table}.{$column} = %s";
                }
            }
        }
        return !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    }
}
