<?php

namespace MRWP\Agent;

/**
 * Maintenance class - Handles maintenance mode functionality
 */
class Maintenance {
    
    /**
     * Bypass cookie name
     */
    const BYPASS_COOKIE = 'mrwp_bypass';
    
    /**
     * Cookie expiration time (24 hours)
     */
    const COOKIE_EXPIRY = 86400;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Handle bypass code from URL parameter
        add_action('init', [$this, 'handle_bypass_code']);
    }
    
    /**
     * Check and display maintenance mode if enabled
     */
    public function check_maintenance_mode() {
        $agent = Agent::instance();
        $maintenance_enabled = $agent->get_option('maintenance_enabled', false);
        
        if (!$maintenance_enabled) {
            return;
        }
        
        // Allow admin users to bypass maintenance
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }
        
        // Allow admin area access
        if (is_admin() || strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
            return;
        }
        
        // Allow REST API access for our endpoints
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/mrwp/v1/') !== false) {
            return;
        }
        
        // Check for valid bypass
        if ($this->is_bypass_valid()) {
            return;
        }
        
        // Display maintenance page
        $this->display_maintenance_page();
    }
    
    /**
     * Handle bypass code from URL parameter
     */
    public function handle_bypass_code() {
        if (isset($_GET['bypass_code']) && !empty($_GET['bypass_code'])) {
            $bypass_code = sanitize_text_field($_GET['bypass_code']);
            $agent = Agent::instance();
            $stored_code = $agent->get_option('bypass_code', '');
            
            if (!empty($stored_code) && hash_equals($stored_code, $bypass_code)) {
                // Set bypass cookie
                setcookie(
                    self::BYPASS_COOKIE,
                    $bypass_code,
                    time() + self::COOKIE_EXPIRY,
                    '/',
                    '',
                    is_ssl(),
                    true // HTTPOnly
                );
                
                // Redirect to remove bypass_code from URL
                $redirect_url = remove_query_arg('bypass_code');
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }
    
    /**
     * Check if bypass is valid
     * 
     * @return bool
     */
    private function is_bypass_valid() {
        if (!isset($_COOKIE[self::BYPASS_COOKIE])) {
            return false;
        }
        
        $agent = Agent::instance();
        $stored_code = $agent->get_option('bypass_code', '');
        $cookie_code = $_COOKIE[self::BYPASS_COOKIE];
        
        return !empty($stored_code) && hash_equals($stored_code, $cookie_code);
    }
    
    /**
     * Display maintenance mode page
     */
    private function display_maintenance_page() {
        // Set proper HTTP status
        status_header(503);
        
        // Get site information
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        // Custom maintenance page HTML
        $html = $this->get_maintenance_html($site_name, $site_url);
        
        // Send headers
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600'); // Suggest retry after 1 hour
        
        // Output the page
        echo $html;
        exit;
    }
    
    /**
     * Get maintenance page HTML
     * 
     * @param string $site_name
     * @param string $site_url
     * @return string
     */
    private function get_maintenance_html($site_name, $site_url) {
        $title = !empty($site_name) ? $site_name : parse_url($site_url, PHP_URL_HOST);
        
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . esc_html($title) . ' - Maintenance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 2rem;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }
        p {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            ' . substr($title, 0, 1) . '
        </div>
        <h1>' . esc_html($title) . '</h1>
        <p>Maintenance en cours, merci de revenir plus tard.</p>
        <div class="spinner"></div>
        <div class="footer">
            Propulsé par <strong>Mr.WordPress Tools</strong>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Toggle maintenance mode
     * 
     * @return array Response data
     */
    public function toggle_maintenance() {
        $agent = Agent::instance();
        $current_state = $agent->get_option('maintenance_enabled', false);
        $new_state = !$current_state;
        
        // Update the option
        $agent->set_option('maintenance_enabled', $new_state);
        
        return [
            'ok' => true,
            'action' => 'toggle_maintenance',
            'state' => [
                'maintenance_enabled' => $new_state,
                'bypass_link' => $agent->get_component('status')->get_bypass_link()
            ]
        ];
    }
    
    /**
     * Reset bypass code
     * 
     * @return array Response data
     */
    public function reset_bypass() {
        $agent = Agent::instance();
        $security = $agent->get_component('security');
        
        // Generate new bypass code
        $new_bypass_code = $security->generate_random_string(24);
        
        // Update the option
        $agent->set_option('bypass_code', $new_bypass_code);
        
        // Get new bypass link
        $bypass_link = $agent->get_component('status')->get_bypass_link();
        
        return [
            'ok' => true,
            'action' => 'reset_bypass',
            'state' => [
                'bypass_link' => $bypass_link,
                'bypass_code' => $new_bypass_code
            ]
        ];
    }
    
    /**
     * Get maintenance status
     * 
     * @return array
     */
    public function get_maintenance_status() {
        $agent = Agent::instance();
        
        return [
            'maintenance_enabled' => $agent->get_option('maintenance_enabled', false),
            'bypass_link' => $agent->get_component('status')->get_bypass_link()
        ];
    }
    
    /**
     * Clear bypass cookie
     */
    public function clear_bypass_cookie() {
        if (isset($_COOKIE[self::BYPASS_COOKIE])) {
            setcookie(
                self::BYPASS_COOKIE,
                '',
                time() - 3600,
                '/',
                '',
                is_ssl(),
                true
            );
        }
    }
}