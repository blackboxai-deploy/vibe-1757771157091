<?php

namespace MRWP\Agent;

/**
 * Security class - Handles HMAC authentication and security measures
 */
class Security {
    
    /**
     * Time window for request validation (5 minutes)
     */
    const TIME_WINDOW = 300;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Security hooks
        add_filter('rest_authentication_errors', [$this, 'authenticate_request']);
    }
    
    /**
     * Authenticate REST API requests using HMAC
     * 
     * @param WP_Error|null|bool $result
     * @return WP_Error|null|bool
     */
    public function authenticate_request($result) {
        // Only authenticate our API routes
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/mrwp/v1/') === false) {
            return $result;
        }
        
        // Allow public ping endpoint
        if (strpos($_SERVER['REQUEST_URI'], '/wp-json/mrwp/v1/ping') !== false && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return $result;
        }
        
        // Validate HMAC signature for all other routes
        $validation = $this->validate_hmac_signature();
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        return $result;
    }
    
    /**
     * Validate HMAC signature from request headers
     * 
     * @return bool|WP_Error
     */
    public function validate_hmac_signature() {
        // Get required headers
        $timestamp = $this->get_header('x-mrwp-timestamp');
        $signature = $this->get_header('x-mrwp-signature');
        
        if (!$timestamp || !$signature) {
            return new \WP_Error(
                'missing_auth_headers',
                __('Missing authentication headers', 'mrwp-tools'),
                ['status' => 401]
            );
        }
        
        // Validate timestamp (within 5 minutes window)
        $current_time = time();
        $request_time = intval($timestamp);
        
        if (abs($current_time - $request_time) > self::TIME_WINDOW) {
            return new \WP_Error(
                'invalid_timestamp',
                __('Request timestamp is outside acceptable window', 'mrwp-tools'),
                ['status' => 401]
            );
        }
        
        // Get request body
        $body = file_get_contents('php://input');
        
        // Calculate expected signature
        $expected_signature = $this->calculate_hmac_signature($timestamp, $body);
        
        // Compare signatures using hash_equals to prevent timing attacks
        if (!hash_equals($expected_signature, $signature)) {
            return new \WP_Error(
                'invalid_signature',
                __('Invalid HMAC signature', 'mrwp-tools'),
                ['status' => 401]
            );
        }
        
        return true;
    }
    
    /**
     * Calculate HMAC signature
     * 
     * @param string $timestamp
     * @param string $body
     * @return string
     */
    public function calculate_hmac_signature($timestamp, $body) {
        // Get site secret
        $agent = Agent::instance();
        $site_secret = $agent->get_option('site_secret', '');
        
        if (empty($site_secret)) {
            return '';
        }
        
        // Create message: timestamp + "\n" + body
        $message = $timestamp . "\n" . $body;
        
        // Calculate HMAC-SHA256
        return hash_hmac('sha256', $message, $site_secret);
    }
    
    /**
     * Generate HMAC signature for outgoing requests
     * 
     * @param string $body JSON body
     * @return array Headers array with timestamp and signature
     */
    public function generate_hmac_headers($body = '') {
        $timestamp = time();
        $signature = $this->calculate_hmac_signature($timestamp, $body);
        
        return [
            'x-mrwp-timestamp' => $timestamp,
            'x-mrwp-signature' => $signature
        ];
    }
    
    /**
     * Get HTTP header value
     * 
     * @param string $header_name
     * @return string|null
     */
    private function get_header($header_name) {
        // Convert header name to uppercase with underscores
        $server_key = 'HTTP_' . strtoupper(str_replace('-', '_', $header_name));
        
        // Check $_SERVER for the header
        if (isset($_SERVER[$server_key])) {
            return $_SERVER[$server_key];
        }
        
        // Fallback: check for direct header name
        $headers = $this->get_all_headers();
        $header_name_lower = strtolower($header_name);
        
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $header_name_lower) {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Get all HTTP headers
     * 
     * @return array
     */
    private function get_all_headers() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        // Fallback implementation
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Generate random string for secrets
     * 
     * @param int $length
     * @return string
     */
    public function generate_random_string($length = 64) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, $max)];
        }
        
        return $string;
    }
    
    /**
     * Check if current user has required capability
     * 
     * @param string $capability
     * @return bool
     */
    public function current_user_can($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Sanitize and validate email
     * 
     * @param string $email
     * @return string|false
     */
    public function validate_email($email) {
        return sanitize_email($email) ?: false;
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url
     * @return string|false
     */
    public function validate_url($url) {
        return esc_url_raw($url) ?: false;
    }
}