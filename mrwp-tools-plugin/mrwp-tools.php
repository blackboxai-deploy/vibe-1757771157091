<?php
/**
 * Plugin Name: Mr.WordPress Tools
 * Plugin URI: https://mrwordpress.com
 * Description: Plugin agent pour exposer une API REST sécurisée permettant la gestion à distance des sites WordPress (maintenance, debug, informations système).
 * Version: 1.0.0
 * Author: Mr.WordPress
 * Author URI: https://mrwordpress.com
 * Text Domain: mrwp-tools
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MRWP_TOOLS_VERSION', '1.0.0');
define('MRWP_TOOLS_PLUGIN_FILE', __FILE__);
define('MRWP_TOOLS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MRWP_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MRWP_TOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class MRWP_Tools {
    
    /**
     * Plugin instance
     * @var MRWP_Tools
     */
    private static $instance = null;
    
    /**
     * Get plugin instance (Singleton)
     * 
     * @return MRWP_Tools
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Define additional constants if needed
     */
    private function define_constants() {
        // Additional constants can be defined here if needed
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Autoloader for plugin classes
        spl_autoload_register([$this, 'autoload']);
        
        // Include main agent class
        require_once MRWP_TOOLS_PLUGIN_PATH . 'includes/Agent.php';
    }
    
    /**
     * PSR-4 autoloader for plugin classes
     * 
     * @param string $class_name
     */
    public function autoload($class_name) {
        // Only handle our namespace
        if (strpos($class_name, 'MRWP\\Agent\\') !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class_name = str_replace('MRWP\\Agent\\', '', $class_name);
        
        // Convert to file path
        $file = MRWP_TOOLS_PLUGIN_PATH . 'includes/' . $class_name . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Initialize the plugin after WordPress loads
        add_action('plugins_loaded', [$this, 'init']);
        
        // Load text domain for translations
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }
    
    /**
     * Initialize the plugin components
     */
    public function init() {
        // Initialize the main Agent class
        if (class_exists('MRWP\\Agent\\Agent')) {
            MRWP\Agent\Agent::instance();
        }
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mrwp-tools',
            false,
            dirname(MRWP_TOOLS_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation callback
     */
    public function activate() {
        // Generate initial configuration on activation
        $options = get_option('mrwp_agent', []);
        
        // Generate site secret if not exists
        if (empty($options['site_secret'])) {
            $options['site_secret'] = $this->generate_secret(64);
        }
        
        // Generate bypass code if not exists
        if (empty($options['bypass_code'])) {
            $options['bypass_code'] = $this->generate_secret(24);
        }
        
        // Set default values
        $defaults = [
            'hub_url' => '',
            'client_email' => '',
            'maintenance_enabled' => false,
            'debug_enabled' => false,
        ];
        
        $options = array_merge($defaults, $options);
        update_option('mrwp_agent', $options);
        
        // Clear rewrite rules to ensure our REST endpoints work
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation callback
     */
    public function deactivate() {
        // Clean up - disable maintenance mode if active
        $options = get_option('mrwp_agent', []);
        if (!empty($options['maintenance_enabled'])) {
            $options['maintenance_enabled'] = false;
            update_option('mrwp_agent', $options);
        }
        
        // Clean up - disable debug mode if active
        if (!empty($options['debug_enabled'])) {
            $options['debug_enabled'] = false;
            update_option('mrwp_agent', $options);
        }
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Generate a random secret string
     * 
     * @param int $length
     * @return string
     */
    private function generate_secret($length = 64) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $secret = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $characters[random_int(0, $max)];
        }
        
        return $secret;
    }
}

// Initialize the plugin
function mrwp_tools() {
    return MRWP_Tools::instance();
}

// Start the plugin
mrwp_tools();