<?php
/**
 * Capabilities Management
 *
 * @package WP_Intelligence
 */

defined('ABSPATH') || exit;

class WP_Intelligence_Capabilities {

    const MANAGE            = 'manage_wp_intelligence';
    const VIEW              = 'view_wp_intelligence';
    const MANAGE_SETTINGS   = 'manage_wp_intelligence_settings';
    const VIEW_LOGS         = 'view_wp_intelligence_logs';
    const MANAGE_REQUESTS   = 'manage_wp_intelligence_requests';
    const MANAGE_DIAGNOSTICS = 'manage_wp_intelligence_diagnostics';

    public function install() {
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($this->get_capabilities() as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public function remove() {
        foreach ($this->get_capabilities() as $cap) {
            $roles = get_roles_for_cap($cap);
            foreach ($roles as $role) {
                $role->remove_cap($cap);
            }
        }
    }

    public function get_capabilities() {
        return array(
            self::MANAGE,
            self::VIEW,
            self::MANAGE_SETTINGS,
            self::VIEW_LOGS,
            self::MANAGE_REQUESTS,
            self::MANAGE_DIAGNOSTICS,
        );
    }

    public function check($capability, $user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        return user_can($user_id, $capability);
    }

    public function check_any($capabilities, $user_id = null) {
        foreach ($capabilities as $cap) {
            if ($this->check($cap, $user_id)) {
                return true;
            }
        }
        return false;
    }

    public function check_all($capabilities, $user_id = null) {
        foreach ($capabilities as $cap) {
            if (!$this->check($cap, $user_id)) {
                return false;
            }
        }
        return true;
    }

    public function add_to_role($role_name, $capabilities) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    public function remove_from_role($role_name, $capabilities) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    public function get_roles_with_capability($capability) {
        $roles = get_option('wp_roles', array());
        $found = array();
        foreach ($roles as $key => $role) {
            if (isset($role['capabilities'][$capability]) && $role['capabilities'][$capability]) {
                $found[] = $key;
            }
        }
        return $found;
    }

    public function admin_only($capability = null) {
        if (null === $capability) {
            $capability = self::MANAGE;
        }
        if (!current_user_can($capability)) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'wp-intelligence'),
                esc_html__('Access Denied', 'wp-intelligence'),
                array('response' => 403)
            );
        }
    }

    public function get_current_user_capability_status() {
        $status = array();
        foreach ($this->get_capabilities() as $cap) {
            $status[$cap] = current_user_can($cap);
        }
        return $status;
    }
}

function get_roles_for_cap($capability) {
    global $wp_roles;
    if (!$wp_roles) {
        $wp_roles = new WP_Roles();
    }
    $roles = array();
    foreach ($wp_roles->roles as $key => $role) {
        if (isset($role['capabilities'][$capability]) && $role['capabilities'][$capability]) {
            $roles[$key] = get_role($key);
        }
    }
    return $roles;
}
