<?php
/**
 * Security Utilities
 *
 * @package Veyra
 */

defined('ABSPATH') || exit;

class Veyra_Security {

    public function verify_nonce_action($action, $nonce = null) {
        if (null === $nonce) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
            if (empty($nonce) && isset($_REQUEST['_wpnonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
            }
        }
        return (bool) wp_verify_nonce($nonce, $action);
    }

    public function create_nonce_action($action) {
        wp_nonce_field($action);
    }

    public function create_nonce_url($action, $url) {
        return wp_nonce_url($url, $action);
    }

    public function sanitize_text($input) {
        return sanitize_text_field($input);
    }

    public function sanitize_email($input) {
        return sanitize_email($input);
    }

    public function sanitize_url($input) {
        return esc_url_raw($input);
    }

    public function sanitize_integer($input) {
        return absint($input);
    }

    public function sanitize_boolean($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    public function sanitize_array($input) {
        if (!is_array($input)) {
            return $this->sanitize_text($input);
        }
        return array_map(array($this, 'sanitize_array'), $input);
    }

    public function sanitize_html($input) {
        return wp_kses_post($input);
    }

    public function sanitize_file_name($input) {
        return sanitize_file_name($input);
    }

    public function escape_text($input) {
        return esc_html($input);
    }

    public function escape_attr($input) {
        return esc_attr($input);
    }

    public function escape_url($input) {
        return esc_url($input);
    }

    public function escape_js($input) {
        return esc_js($input);
    }

    public function escape_html($input) {
        return wp_kses_post($input);
    }

    public function validate_email($input) {
        return (bool) is_email($input);
    }

    public function validate_url($input) {
        return filter_var($input, FILTER_VALIDATE_URL) !== false;
    }

    public function validate_ip($input) {
        return filter_var($input, FILTER_VALIDATE_IP) !== false;
    }

    public function check_admin_referer($action = 'veyra') {
        check_admin_referer($action);
    }

    public function check_ajax_referer($action = 'veyra') {
        check_ajax_referer($action);
    }

    public function check_rate_limit($key, $max_requests = 60, $time_window = 60) {
        $transient_key = 'veyra_rl_' . md5($key);
        $current = get_transient($transient_key);
        if (false === $current) {
            return true;
        }
        return (int) $current < $max_requests;
    }

    public function increment_rate_limit($key, $time_window = 60) {
        $transient_key = 'veyra_rl_' . md5($key);
        $current = get_transient($transient_key);
        if (false === $current) {
            set_transient($transient_key, 1, $time_window);
            return;
        }
        set_transient($transient_key, (int) $current + 1, $time_window);
    }

    public function validate_upload($file, $allowed_types = array(), $max_size = 0) {
        if (empty($file) || !is_array($file)) {
            return array('valid' => false, 'message' => __('No file provided.', 'veyra'));
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('valid' => false, 'message' => __('Upload error occurred.', 'veyra'));
        }
        if (!empty($allowed_types)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_types, true)) {
                return array('valid' => false, 'message' => __('File type not allowed.', 'veyra'));
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed_mimes = array(
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                'gif' => 'image/gif', 'pdf' => 'application/pdf', 'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain',
            );
            $valid_mimes = array();
            foreach ($allowed_types as $t) {
                if (isset($allowed_mimes[$t])) {
                    $valid_mimes[] = $allowed_mimes[$t];
                }
            }
            if (!empty($valid_mimes) && !in_array($mime, $valid_mimes, true)) {
                return array('valid' => false, 'message' => __('File MIME type not allowed.', 'veyra'));
            }
        }
        if ($max_size > 0 && $file['size'] > $max_size) {
            return array('valid' => false, 'message' => __('File too large.', 'veyra'));
        }
        return array('valid' => true, 'message' => '');
    }

    public function get_client_ip_hash() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return hash('sha256', $ip . wp_salt('auth'));
    }

    public function get_safe_identifier() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        return hash('sha256', $ip . $ua . wp_salt('auth'));
    }

    public function send_security_headers() {
        if (!is_admin()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }

    public function is_get() {
        return isset($_SERVER['REQUEST_METHOD']) && 'GET' === strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function is_post() {
        return isset($_SERVER['REQUEST_METHOD']) && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}
