<?php
/**
 * Encryption Utilities
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Encryption {

    private $key;

    public function __construct() {
        $this->key = $this->get_or_create_key();
    }

    public function get_or_create_key() {
        $key = get_option('wp_intelligence_enc_key', '');
        if (empty($key)) {
            $key = wp_generate_password(32, false);
            add_option('wp_intelligence_enc_key', $key, false);
        }
        return $key;
    }

    public function encrypt($plaintext) {
        if (empty($plaintext)) {
            return '';
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        if (false === $encrypted) {
            return '';
        }
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($encrypted) {
        if (empty($encrypted)) {
            return '';
        }
        $data = base64_decode($encrypted, true);
        if (false === $data) {
            return false;
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) < $iv_length) {
            return false;
        }
        $iv = substr($data, 0, $iv_length);
        $ciphertext = substr($data, $iv_length);
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        return $decrypted === false ? false : $decrypted;
    }

    public function is_encrypted($data) {
        if (empty($data)) {
            return false;
        }
        $decoded = base64_decode($data, true);
        return false !== $decoded && strlen($decoded) > openssl_cipher_iv_length('aes-256-cbc');
    }

    public function mask_key($key) {
        if (empty($key)) {
            return '****';
        }
        $len = strlen($key);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return str_repeat('*', $len - 4) . substr($key, -4);
    }

    public function secure_delete(&$variable) {
        if (is_string($variable)) {
            $variable = str_repeat("\0", strlen($variable));
        }
        $variable = null;
        unset($variable);
    }
}
