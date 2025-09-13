<?php

namespace MRWP\Agent;

/**
 * Rest class - Handles REST API endpoints
 */
class Rest {
    
    /**
     * API namespace
     */
    const API_NAMESPACE = 'mrwp/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        // REST API hooks are handled in Agent class
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Public ping endpoint
        register_rest_route(self::API_NAMESPACE, '/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_ping'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);
        
        // Authenticated status endpoint
        register_rest_route(self::API_NAMESPACE, '/status', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_status'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
        
        // Authenticated action endpoint
        register_rest_route(self::API_NAMESPACE, '/action', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_action'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'action' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => [$this, 'validate_action']
                ]
            ]
        ]);
    }
    
    /**
     * Handle ping endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_ping($request) {
        $agent = Agent::instance();
        $status = $agent->get_component('status');
        
        return new \WP_REST_Response($status->get_basic_info(), 200);
    }
    
    /**
     * Handle status endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_status($request) {
        try {
            $agent = Agent::instance();
            $status = $agent->get_component('status');
            
            $site_status = $status->get_site_status();
            
            return new \WP_REST_Response($site_status, 200);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Handle action endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_action($request) {
        try {
            $action = $request->get_param('action');
            $agent = Agent::instance();
            
            switch ($action) {
                case 'toggle_maintenance':
                    $maintenance = $agent->get_component('maintenance');
                    $result = $maintenance->toggle_maintenance();
                    break;
                    
                case 'reset_bypass':
                    $maintenance = $agent->get_component('maintenance');
                    $result = $maintenance->reset_bypass();
                    break;
                    
                case 'toggle_debug':
                    $debug = $agent->get_component('debug');
                    $result = $debug->toggle_debug();
                    break;
                    
                case 'send_bypass_email':
                    $email = $agent->get_component('email');
                    $result = $email->send_bypass_email();
                    break;
                    
                default:
                    return new \WP_REST_Response([
                        'ok' => false,
                        'error' => sprintf(__('Unknown action: %s', 'mrwp-tools'), $action)
                    ], 400);
            }
            
            $status_code = $result['ok'] ? 200 : 400;
            return new \WP_REST_Response($result, $status_code);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check authentication for protected endpoints
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_authentication($request) {
        $agent = Agent::instance();
        $security = $agent->get_component('security');
        
        // Validate HMAC signature
        $validation = $security->validate_hmac_signature();
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        return true;
    }
    
    /**
     * Validate action parameter
     * 
     * @param string $value
     * @param WP_REST_Request $request
     * @param string $param
     * @return bool
     */
    public function validate_action($value, $request, $param) {
        $allowed_actions = [
            'toggle_maintenance',
            'reset_bypass', 
            'toggle_debug',
            'send_bypass_email'
        ];
        
        return in_array($value, $allowed_actions, true);
    }
    
    /**
     * Handle CORS for API requests
     * 
     * @param WP_REST_Response $response
     * @param WP_REST_Server $server
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_cors($response, $server, $request) {
        // Only handle our API endpoints
        if (strpos($request->get_route(), '/mrwp/v1/') !== 0) {
            return $response;
        }
        
        $agent = Agent::instance();
        $hub_url = $agent->get_option('hub_url', '');
        
        // Set CORS headers
        $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, x-mrwp-timestamp, x-mrwp-signature');
        
        // If hub URL is configured, restrict access to it
        if (!empty($hub_url)) {
            $parsed_hub = parse_url($hub_url);
            if ($parsed_hub && isset($parsed_hub['host'])) {
                $allowed_origin = $parsed_hub['scheme'] . '://' . $parsed_hub['host'];
                $response->header('Access-Control-Allow-Origin', $allowed_origin);
            }
        } else {
            // Allow any origin if no hub URL is configured (development mode)
            $response->header('Access-Control-Allow-Origin', '*');
        }
        
        return $response;
    }
    
    /**
     * Get API documentation
     * 
     * @return array
     */
    public function get_api_documentation() {
        return [
            'namespace' => self::API_NAMESPACE,
            'authentication' => 'HMAC-SHA256',
            'endpoints' => [
                'GET /ping' => [
                    'description' => 'Test connectivity (public)',
                    'authentication' => 'none',
                    'response' => [
                        'ok' => 'bool',
                        'site' => 'string (home_url)',
                        'name' => 'string (site_name)',
                        'version' => 'string (plugin_version)'
                    ]
                ],
                'POST /status' => [
                    'description' => 'Get complete site status',
                    'authentication' => 'required',
                    'response' => [
                        'site_name' => 'string',
                        'home_url' => 'string',
                        'wp_version' => 'string',
                        'php_version' => 'string',
                        'core_updates_count' => 'int',
                        'plugin_updates_count' => 'int',
                        'theme_updates_count' => 'int',
                        'maintenance_enabled' => 'bool',
                        'debug_enabled' => 'bool',
                        'bypass_link' => 'string',
                        'last_synced_at' => 'int (timestamp)'
                    ]
                ],
                'POST /action' => [
                    'description' => 'Execute system actions',
                    'authentication' => 'required',
                    'body' => [
                        'action' => 'string (required)'
                    ],
                    'actions' => [
                        'toggle_maintenance' => 'Enable/disable maintenance mode',
                        'reset_bypass' => 'Generate new bypass code',
                        'toggle_debug' => 'Enable/disable debug mode',
                        'send_bypass_email' => 'Send bypass link to client email'
                    ],
                    'response' => [
                        'ok' => 'bool',
                        'action' => 'string',
                        'state' => 'object (varies by action)'
                    ]
                ]
            ],
            'authentication_details' => [
                'method' => 'HMAC-SHA256',
                'headers' => [
                    'x-mrwp-timestamp' => 'Unix timestamp (current time)',
                    'x-mrwp-signature' => 'HMAC signature'
                ],
                'signature_calculation' => 'hmac_sha256(timestamp + "\\n" + body, site_secret)',
                'time_window' => '5 minutes'
            ]
        ];
    }
    
    /**
     * Log API request
     * 
     * @param WP_REST_Request $request
     * @param array $response_data
     */
    private function log_api_request($request, $response_data = []) {
        $log_entry = [
            'timestamp' => time(),
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'params' => $request->get_params(),
            'user_agent' => $request->get_header('user_agent'),
            'ip' => $this->get_client_ip(),
            'response_status' => isset($response_data['ok']) ? ($response_data['ok'] ? 'success' : 'error') : 'unknown'
        ];
        
        // Get existing log
        $api_log = get_option('mrwp_api_log', []);
        
        // Add new entry
        $api_log[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($api_log) > 100) {
            $api_log = array_slice($api_log, -100);
        }
        
        update_option('mrwp_api_log', $api_log);
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Get API activity log
     * 
     * @param int $limit
     * @return array
     */
    public function get_api_log($limit = 50) {
        $api_log = get_option('mrwp_api_log', []);
        
        // Sort by timestamp (newest first)
        usort($api_log, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return array_slice($api_log, 0, $limit);
    }
}