<?php

namespace MRWP\Agent;

/**
 * Main Agent class - Orchestrates all plugin functionality
 */
class Agent {
    
    /**
     * Agent instance
     * @var Agent
     */
    private static $instance = null;
    
    /**
     * Plugin components
     * @var array
     */
    private $components = [];
    
    /**
     * Get Agent instance (Singleton)
     * 
     * @return Agent
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize Security component
        $this->components['security'] = new Security();
        
        // Initialize Status component
        $this->components['status'] = new Status();
        
        // Initialize Maintenance component
        $this->components['maintenance'] = new Maintenance();
        
        // Initialize Debug component
        $this->components['debug'] = new Debug();
        
        // Initialize Email component
        $this->components['email'] = new Email();
        
        // Initialize REST API component
        $this->components['rest'] = new Rest();
        
        // Initialize Settings page (admin only)
        if (is_admin()) {
            require_once MRWP_TOOLS_PLUGIN_PATH . 'admin/Settings.php';
            $this->components['settings'] = new \MRWP\Admin\Settings();
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Early hook for maintenance mode check
        add_action('template_redirect', [$this, 'check_maintenance_mode'], 0);
        
        // Initialize debug mode early
        add_action('init', [$this, 'init_debug_mode'], 1);
        
        // REST API initialization
        add_action('rest_api_init', [$this, 'init_rest_api']);
    }
    
    /**
     * Check and display maintenance mode if enabled
     */
    public function check_maintenance_mode() {
        if ($this->components['maintenance']) {
            $this->components['maintenance']->check_maintenance_mode();
        }
    }
    
    /**
     * Initialize debug mode
     */
    public function init_debug_mode() {
        if ($this->components['debug']) {
            $this->components['debug']->init_debug_mode();
        }
    }
    
    /**
     * Initialize REST API routes
     */
    public function init_rest_api() {
        if ($this->components['rest']) {
            $this->components['rest']->register_routes();
        }
    }
    
    /**
     * Get component instance
     * 
     * @param string $component
     * @return mixed|null
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
    
    /**
     * Get plugin options
     * 
     * @return array
     */
    public function get_options() {
        return get_option('mrwp_agent', []);
    }
    
    /**
     * Update plugin options
     * 
     * @param array $options
     * @return bool
     */
    public function update_options($options) {
        return update_option('mrwp_agent', $options);
    }
    
    /**
     * Get specific option value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_option($key, $default = null) {
        $options = $this->get_options();
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Set specific option value
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set_option($key, $value) {
        $options = $this->get_options();
        $options[$key] = $value;
        return $this->update_options($options);
    }
}