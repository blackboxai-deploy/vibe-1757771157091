<?php

namespace MRWP\Agent;

/**
 * Debug class - Handles debug mode functionality
 */
class Debug {
    
    /**
     * Debug constants that we manage
     */
    const MANAGED_CONSTANTS = [
        'WP_DEBUG',
        'WP_DEBUG_LOG', 
        'WP_DEBUG_DISPLAY'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize debug mode state
    }
    
    /**
     * Initialize debug mode based on current settings
     */
    public function init_debug_mode() {
        $agent = Agent::instance();
        $debug_enabled = $agent->get_option('debug_enabled', false);
        
        if ($debug_enabled) {
            $this->enable_debug_mode();
        } else {
            $this->disable_debug_mode();
        }
    }
    
    /**
     * Enable debug mode
     */
    private function enable_debug_mode() {
        // Only set constants if they're not already defined
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        } elseif (!WP_DEBUG) {
            // If already defined as false, we can't change it
            $this->add_debug_notice('WP_DEBUG is already defined as false in wp-config.php');
        }
        
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        } elseif (WP_DEBUG_DISPLAY) {
            // If display is enabled, we should disable it for security
            $this->add_debug_notice('WP_DEBUG_DISPLAY should be disabled for security reasons');
        }
        
        // Ensure log directory exists and is writable
        $this->ensure_log_directory();
        
        // Set error reporting level
        if (WP_DEBUG) {
            error_reporting(E_ALL);
            ini_set('log_errors', 1);
            
            // Set custom error log location if possible
            $log_file = $this->get_debug_log_path();
            if ($log_file && is_writable(dirname($log_file))) {
                ini_set('error_log', $log_file);
            }
        }
    }
    
    /**
     * Disable debug mode
     */
    private function disable_debug_mode() {
        // We can't undefine constants, but we can set ini settings
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // If WP_DEBUG is defined as true in wp-config, we can't disable it completely
            $this->add_debug_notice('WP_DEBUG is defined as true in wp-config.php and cannot be disabled');
            return;
        }
        
        // Reset error reporting to default if we're not in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('log_errors', 0);
            ini_set('display_errors', 0);
        }
    }
    
    /**
     * Toggle debug mode
     * 
     * @return array Response data
     */
    public function toggle_debug() {
        $agent = Agent::instance();
        $current_state = $agent->get_option('debug_enabled', false);
        $new_state = !$current_state;
        
        // Update the option
        $agent->set_option('debug_enabled', $new_state);
        
        // Apply the new debug state
        if ($new_state) {
            $this->enable_debug_mode();
        } else {
            $this->disable_debug_mode();
        }
        
        return [
            'ok' => true,
            'action' => 'toggle_debug',
            'state' => [
                'debug_enabled' => $new_state,
                'debug_info' => $this->get_debug_info()
            ]
        ];
    }
    
    /**
     * Get current debug information
     * 
     * @return array
     */
    public function get_debug_info() {
        return [
            'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false,
            'error_reporting' => error_reporting(),
            'log_errors' => ini_get('log_errors'),
            'display_errors' => ini_get('display_errors'),
            'error_log_path' => ini_get('error_log'),
            'debug_log_exists' => $this->debug_log_exists(),
            'debug_log_size' => $this->get_debug_log_size()
        ];
    }
    
    /**
     * Get debug log file path
     * 
     * @return string|false
     */
    private function get_debug_log_path() {
        // WordPress default debug log location
        $wp_content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
        return $wp_content_dir . '/debug.log';
    }
    
    /**
     * Check if debug log file exists
     * 
     * @return bool
     */
    private function debug_log_exists() {
        $log_path = $this->get_debug_log_path();
        return $log_path && file_exists($log_path);
    }
    
    /**
     * Get debug log file size
     * 
     * @return int|false File size in bytes or false if not exists
     */
    private function get_debug_log_size() {
        $log_path = $this->get_debug_log_path();
        return $log_path && file_exists($log_path) ? filesize($log_path) : 0;
    }
    
    /**
     * Ensure log directory exists and is writable
     */
    private function ensure_log_directory() {
        $log_path = $this->get_debug_log_path();
        if (!$log_path) {
            return;
        }
        
        $log_dir = dirname($log_path);
        
        // Create directory if it doesn't exist
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Check if directory is writable
        if (!is_writable($log_dir)) {
            $this->add_debug_notice('Debug log directory is not writable: ' . $log_dir);
        }
    }
    
    /**
     * Get debug log content (last N lines)
     * 
     * @param int $lines Number of lines to read from end of file
     * @return array|false
     */
    public function get_debug_log_content($lines = 100) {
        $log_path = $this->get_debug_log_path();
        
        if (!$log_path || !file_exists($log_path)) {
            return false;
        }
        
        // Read last N lines efficiently
        $content = [];
        $handle = fopen($log_path, 'r');
        
        if ($handle) {
            // Go to end of file
            fseek($handle, 0, SEEK_END);
            $pos = ftell($handle);
            $line_count = 0;
            
            // Read backwards
            while ($pos > 0 && $line_count < $lines) {
                $pos--;
                fseek($handle, $pos);
                $char = fgetc($handle);
                
                if ($char === "\n" || $pos === 0) {
                    $line = fgets($handle);
                    if ($line !== false) {
                        array_unshift($content, trim($line));
                        $line_count++;
                    }
                }
            }
            
            fclose($handle);
        }
        
        return $content;
    }
    
    /**
     * Clear debug log file
     * 
     * @return bool Success status
     */
    public function clear_debug_log() {
        $log_path = $this->get_debug_log_path();
        
        if (!$log_path || !file_exists($log_path)) {
            return false;
        }
        
        return file_put_contents($log_path, '') !== false;
    }
    
    /**
     * Add debug notice (for internal tracking)
     * 
     * @param string $message
     */
    private function add_debug_notice($message) {
        // Store notices in option for later display/logging
        $notices = get_option('mrwp_debug_notices', []);
        $notices[] = [
            'message' => $message,
            'timestamp' => time()
        ];
        
        // Keep only last 10 notices
        if (count($notices) > 10) {
            $notices = array_slice($notices, -10);
        }
        
        update_option('mrwp_debug_notices', $notices);
    }
    
    /**
     * Get debug notices
     * 
     * @return array
     */
    public function get_debug_notices() {
        return get_option('mrwp_debug_notices', []);
    }
    
    /**
     * Clear debug notices
     * 
     * @return bool
     */
    public function clear_debug_notices() {
        return delete_option('mrwp_debug_notices');
    }
    
    /**
     * Check if debug mode is properly configured
     * 
     * @return array Status and recommendations
     */
    public function check_debug_configuration() {
        $status = [
            'properly_configured' => true,
            'issues' => [],
            'recommendations' => []
        ];
        
        // Check if WP_DEBUG is defined in wp-config
        if (defined('WP_DEBUG')) {
            if (WP_DEBUG) {
                $status['recommendations'][] = 'WP_DEBUG is enabled in wp-config.php';
            }
        } else {
            $status['recommendations'][] = 'WP_DEBUG is not defined in wp-config.php, using runtime setting';
        }
        
        // Check log directory
        $log_path = $this->get_debug_log_path();
        if ($log_path) {
            $log_dir = dirname($log_path);
            if (!is_writable($log_dir)) {
                $status['properly_configured'] = false;
                $status['issues'][] = 'Log directory is not writable: ' . $log_dir;
            }
        }
        
        return $status;
    }
}