<?php

namespace MRWP\Admin;

/**
 * Settings class - Handles the admin settings page
 */
class Settings {
    
    /**
     * Page slug
     */
    const PAGE_SLUG = 'mrwp-tools-settings';
    
    /**
     * Option group
     */
    const OPTION_GROUP = 'mrwp_tools_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __('Mr.WordPress Tools', 'mrwp-tools'),
            __('Mr.WordPress Tools', 'mrwp-tools'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'settings_page']
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            self::OPTION_GROUP,
            'mrwp_agent',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => []
            ]
        );
        
        // Main settings section
        add_settings_section(
            'mrwp_main_settings',
            __('Configuration', 'mrwp-tools'),
            [$this, 'settings_section_callback'],
            self::PAGE_SLUG
        );
        
        // Hub URL field
        add_settings_field(
            'hub_url',
            __('Hub URL', 'mrwp-tools'),
            [$this, 'hub_url_callback'],
            self::PAGE_SLUG,
            'mrwp_main_settings'
        );
        
        // Client email field
        add_settings_field(
            'client_email',
            __('Client Email', 'mrwp-tools'),
            [$this, 'client_email_callback'],
            self::PAGE_SLUG,
            'mrwp_main_settings'
        );
        
        // Bypass link section (read-only)
        add_settings_section(
            'mrwp_bypass_settings',
            __('Bypass Link', 'mrwp-tools'),
            [$this, 'bypass_section_callback'],
            self::PAGE_SLUG
        );
        
        // Bypass link field (read-only)
        add_settings_field(
            'bypass_link',
            __('Current Bypass Link', 'mrwp-tools'),
            [$this, 'bypass_link_callback'],
            self::PAGE_SLUG,
            'mrwp_bypass_settings'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }
        
        // Add inline CSS for better styling
        $css = '
        .mrwp-settings-wrapper {
            max-width: 800px;
        }
        .mrwp-readonly-field {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }
        .mrwp-bypass-link {
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .mrwp-info-box {
            background: #e7f3ff;
            border-left: 4px solid #0073aa;
            padding: 12px;
            margin: 15px 0;
        }
        .mrwp-warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
        }
        .mrwp-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .mrwp-status-active {
            background-color: #28a745;
        }
        .mrwp-status-inactive {
            background-color: #dc3545;
        }
        ';
        
        wp_add_inline_style('wp-admin', $css);
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], self::OPTION_GROUP . '-options')) {
            $this->handle_form_submission();
        }
        
        // Get current options
        $agent = \MRWP\Agent\Agent::instance();
        $options = $agent->get_options();
        
        ?>
        <div class="wrap mrwp-settings-wrapper">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->display_status_overview(); ?>
            
            <form method="post" action="">
                <?php
                wp_nonce_field(self::OPTION_GROUP . '-options');
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button();
                ?>
            </form>
            
            <?php $this->display_api_information(); ?>
        </div>
        <?php
    }
    
    /**
     * Display status overview
     */
    private function display_status_overview() {
        $agent = \MRWP\Agent\Agent::instance();
        $options = $agent->get_options();
        $maintenance_enabled = !empty($options['maintenance_enabled']);
        $debug_enabled = !empty($options['debug_enabled']);
        
        ?>
        <div class="mrwp-info-box">
            <h3><?php _e('Current Status', 'mrwp-tools'); ?></h3>
            <p>
                <span class="mrwp-status-indicator <?php echo $maintenance_enabled ? 'mrwp-status-active' : 'mrwp-status-inactive'; ?>"></span>
                <strong><?php _e('Maintenance Mode:', 'mrwp-tools'); ?></strong>
                <?php echo $maintenance_enabled ? __('Active', 'mrwp-tools') : __('Inactive', 'mrwp-tools'); ?>
            </p>
            <p>
                <span class="mrwp-status-indicator <?php echo $debug_enabled ? 'mrwp-status-active' : 'mrwp-status-inactive'; ?>"></span>
                <strong><?php _e('Debug Mode:', 'mrwp-tools'); ?></strong>
                <?php echo $debug_enabled ? __('Active', 'mrwp-tools') : __('Inactive', 'mrwp-tools'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Display API information
     */
    private function display_api_information() {
        $agent = \MRWP\Agent\Agent::instance();
        $options = $agent->get_options();
        $site_secret = $options['site_secret'] ?? '';
        
        ?>
        <div class="mrwp-info-box">
            <h3><?php _e('API Information', 'mrwp-tools'); ?></h3>
            <p><strong><?php _e('API Base URL:', 'mrwp-tools'); ?></strong> <?php echo esc_html(home_url('/wp-json/mrwp/v1/')); ?></p>
            <p><strong><?php _e('Site Secret:', 'mrwp-tools'); ?></strong> <code><?php echo esc_html(substr($site_secret, 0, 8) . '...'); ?></code></p>
            <p><em><?php _e('The complete site secret is required for HMAC authentication. Contact your administrator for the full secret.', 'mrwp-tools'); ?></em></p>
        </div>
        <?php
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure the basic settings for Mr.WordPress Tools.', 'mrwp-tools') . '</p>';
    }
    
    /**
     * Hub URL field callback
     */
    public function hub_url_callback() {
        $agent = \MRWP\Agent\Agent::instance();
        $options = $agent->get_options();
        $value = $options['hub_url'] ?? '';
        
        echo '<input type="url" id="hub_url" name="mrwp_agent[hub_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL of the external dashboard that will manage this site.', 'mrwp-tools') . '</p>';
    }
    
    /**
     * Client email field callback
     */
    public function client_email_callback() {
        $agent = \MRWP\Agent\Agent::instance();
        $options = $agent->get_options();
        $value = $options['client_email'] ?? '';
        
        echo '<input type="email" id="client_email" name="mrwp_agent[client_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Email address of the site owner who will receive maintenance notifications.', 'mrwp-tools') . '</p>';
    }
    
    /**
     * Bypass section callback
     */
    public function bypass_section_callback() {
        echo '<p>' . __('The bypass link allows access to the site even when maintenance mode is active.', 'mrwp-tools') . '</p>';
        echo '<div class="mrwp-warning-box">';
        echo '<p><strong>' . __('Security Note:', 'mrwp-tools') . '</strong> ' . __('Keep this link private. It can be regenerated via the API if needed.', 'mrwp-tools') . '</p>';
        echo '</div>';
    }
    
    /**
     * Bypass link field callback
     */
    public function bypass_link_callback() {
        $agent = \MRWP\Agent\Agent::instance();
        $status = $agent->get_component('status');
        $bypass_link = $status->get_bypass_link();
        
        echo '<div class="mrwp-bypass-link">';
        if (!empty($bypass_link)) {
            echo esc_html($bypass_link);
        } else {
            echo '<em>' . __('No bypass link available', 'mrwp-tools') . '</em>';
        }
        echo '</div>';
        echo '<p class="description">' . __('This link can only be regenerated via the API using the "reset_bypass" action.', 'mrwp-tools') . '</p>';
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }
        
        $agent = \MRWP\Agent\Agent::instance();
        $current_options = $agent->get_options();
        
        // Get submitted data
        $submitted_data = $_POST['mrwp_agent'] ?? [];
        
        // Merge with current options (preserve fields not in form)
        $new_options = array_merge($current_options, $submitted_data);
        
        // Update options
        $updated = $agent->update_options($new_options);
        
        if ($updated) {
            add_settings_error(
                'mrwp_tools_messages',
                'mrwp_tools_message',
                __('Settings saved successfully.', 'mrwp-tools'),
                'updated'
            );
        } else {
            add_settings_error(
                'mrwp_tools_messages',
                'mrwp_tools_message',
                __('Error saving settings.', 'mrwp-tools'),
                'error'
            );
        }
        
        settings_errors('mrwp_tools_messages');
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $agent = \MRWP\Agent\Agent::instance();
        $current_options = $agent->get_options();
        $sanitized = $current_options; // Start with current options
        
        // Sanitize hub_url
        if (isset($input['hub_url'])) {
            $sanitized['hub_url'] = esc_url_raw($input['hub_url']);
        }
        
        // Sanitize client_email
        if (isset($input['client_email'])) {
            $email = sanitize_email($input['client_email']);
            if (!empty($email) && is_email($email)) {
                $sanitized['client_email'] = $email;
            } else {
                add_settings_error(
                    'mrwp_tools_messages',
                    'invalid_email',
                    __('Invalid email address.', 'mrwp-tools'),
                    'error'
                );
            }
        }
        
        return $sanitized;
    }
}