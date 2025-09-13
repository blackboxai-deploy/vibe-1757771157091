<?php

namespace MRWP\Agent;

/**
 * Status class - Collects and returns site status information
 */
class Status {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize update checks
        add_action('init', [$this, 'maybe_check_for_updates']);
    }
    
    /**
     * Get complete site status
     * 
     * @return array
     */
    public function get_site_status() {
        // Basic site information
        $status = [
            'site_name' => get_bloginfo('name'),
            'home_url' => home_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
        ];
        
        // Get update counts
        $updates = $this->get_update_counts();
        $status = array_merge($status, $updates);
        
        // Get plugin options
        $agent = Agent::instance();
        $options = $agent->get_options();
        
        // Add maintenance and debug states
        $status['maintenance_enabled'] = !empty($options['maintenance_enabled']);
        $status['debug_enabled'] = !empty($options['debug_enabled']);
        
        // Add bypass link
        $status['bypass_link'] = $this->get_bypass_link();
        
        // Add last sync timestamp
        $status['last_synced_at'] = time();
        
        return $status;
    }
    
    /**
     * Get counts of available updates
     * 
     * @return array
     */
    public function get_update_counts() {
        // Ensure we have the latest update information
        $this->refresh_update_data();
        
        return [
            'core_updates_count' => $this->get_core_updates_count(),
            'plugin_updates_count' => $this->get_plugin_updates_count(),
            'theme_updates_count' => $this->get_theme_updates_count(),
        ];
    }
    
    /**
     * Get core update count
     * 
     * @return int
     */
    private function get_core_updates_count() {
        $core_updates = get_core_updates();
        
        if (empty($core_updates) || !is_array($core_updates)) {
            return 0;
        }
        
        // Check if there's a newer version available
        foreach ($core_updates as $update) {
            if (isset($update->response) && $update->response === 'upgrade') {
                return 1;
            }
        }
        
        return 0;
    }
    
    /**
     * Get plugin update count
     * 
     * @return int
     */
    private function get_plugin_updates_count() {
        $plugin_updates = get_plugin_updates();
        return is_array($plugin_updates) ? count($plugin_updates) : 0;
    }
    
    /**
     * Get theme update count
     * 
     * @return int
     */
    private function get_theme_updates_count() {
        $theme_updates = get_theme_updates();
        return is_array($theme_updates) ? count($theme_updates) : 0;
    }
    
    /**
     * Refresh update data from WordPress.org
     */
    private function refresh_update_data() {
        // Include necessary files
        if (!function_exists('wp_version_check')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        // Force check for updates
        wp_version_check();
        wp_update_plugins();
        wp_update_themes();
    }
    
    /**
     * Maybe check for updates (to avoid too frequent checks)
     */
    public function maybe_check_for_updates() {
        // Check if we need to refresh update data
        $last_check = get_option('mrwp_last_update_check', 0);
        $check_interval = 3600; // 1 hour
        
        if ((time() - $last_check) > $check_interval) {
            $this->refresh_update_data();
            update_option('mrwp_last_update_check', time());
        }
    }
    
    /**
     * Get bypass link for maintenance mode
     * 
     * @return string
     */
    public function get_bypass_link() {
        $agent = Agent::instance();
        $bypass_code = $agent->get_option('bypass_code', '');
        
        if (empty($bypass_code)) {
            return '';
        }
        
        return home_url('/?bypass_code=' . $bypass_code);
    }
    
    /**
     * Get site basic information
     * 
     * @return array
     */
    public function get_basic_info() {
        return [
            'ok' => true,
            'site' => home_url(),
            'name' => get_bloginfo('name'),
            'version' => MRWP_TOOLS_VERSION
        ];
    }
    
    /**
     * Get server environment information
     * 
     * @return array
     */
    public function get_environment_info() {
        global $wpdb;
        
        return [
            'wp_version' => get_bloginfo('version'),
            'wp_multisite' => is_multisite(),
            'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'php_version' => PHP_VERSION,
            'php_memory_limit' => ini_get('memory_limit'),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        ];
    }
    
    /**
     * Get plugin status information
     * 
     * @return array
     */
    public function get_plugin_info() {
        // Get all plugins
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $plugin_info = [
            'total_plugins' => count($all_plugins),
            'active_plugins' => count($active_plugins),
            'inactive_plugins' => count($all_plugins) - count($active_plugins),
        ];
        
        // Add current plugin info
        $plugin_info['mrwp_tools'] = [
            'version' => MRWP_TOOLS_VERSION,
            'active' => true
        ];
        
        return $plugin_info;
    }
    
    /**
     * Get theme information
     * 
     * @return array
     */
    public function get_theme_info() {
        $current_theme = wp_get_theme();
        $all_themes = wp_get_themes();
        
        return [
            'current_theme' => [
                'name' => $current_theme->get('Name'),
                'version' => $current_theme->get('Version'),
                'template' => $current_theme->get_template(),
                'stylesheet' => $current_theme->get_stylesheet()
            ],
            'total_themes' => count($all_themes)
        ];
    }
}