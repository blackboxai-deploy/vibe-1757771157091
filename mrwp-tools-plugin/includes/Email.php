<?php

namespace MRWP\Agent;

/**
 * Email class - Handles email functionality
 */
class Email {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Email hooks if needed
    }
    
    /**
     * Send bypass email to client
     * 
     * @return array Response data
     */
    public function send_bypass_email() {
        $agent = Agent::instance();
        $client_email = $agent->get_option('client_email', '');
        
        if (empty($client_email)) {
            return [
                'ok' => false,
                'error' => __('Client email not configured', 'mrwp-tools')
            ];
        }
        
        // Validate email
        if (!is_email($client_email)) {
            return [
                'ok' => false,
                'error' => __('Invalid client email address', 'mrwp-tools')
            ];
        }
        
        // Get site information
        $site_name = get_bloginfo('name');
        $bypass_link = $agent->get_component('status')->get_bypass_link();
        
        if (empty($bypass_link)) {
            return [
                'ok' => false,
                'error' => __('Bypass link not available', 'mrwp-tools')
            ];
        }
        
        // Prepare email content
        $subject = sprintf(
            __('[Mr.WordPress] Maintenance activée – %s', 'mrwp-tools'),
            $site_name
        );
        
        $message = $this->get_bypass_email_template($site_name, $bypass_link);
        
        // Email headers
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->get_from_email(),
            'Reply-To: support@mrwordpress.com'
        ];
        
        // Send email
        $sent = wp_mail($client_email, $subject, $message, $headers);
        
        if ($sent) {
            return [
                'ok' => true,
                'action' => 'send_bypass_email',
                'state' => [
                    'email_sent' => true,
                    'recipient' => $client_email,
                    'sent_at' => time()
                ]
            ];
        } else {
            return [
                'ok' => false,
                'error' => __('Failed to send email', 'mrwp-tools')
            ];
        }
    }
    
    /**
     * Get bypass email template
     * 
     * @param string $site_name
     * @param string $bypass_link
     * @return string
     */
    private function get_bypass_email_template($site_name, $bypass_link) {
        $current_time = current_time('Y-m-d H:i:s');
        
        return sprintf(
            __('Bonjour,

Nous venons d\'activer un mode maintenance pour intervenir en sécurité sur le site %s.

Accès privé (ne pas partager) : %s

Date/heure : %s
Contact : support@mrwordpress.com

— Mr.WordPress Tools', 'mrwp-tools'),
            $site_name,
            $bypass_link,
            $current_time
        );
    }
    
    /**
     * Get FROM email address for outgoing emails
     * 
     * @return string
     */
    private function get_from_email() {
        // Try to get admin email
        $admin_email = get_option('admin_email');
        
        if (!empty($admin_email) && is_email($admin_email)) {
            $site_name = get_bloginfo('name');
            return sprintf('%s <%s>', $site_name, $admin_email);
        }
        
        // Fallback to default
        return 'Mr.WordPress Tools <noreply@mrwordpress.com>';
    }
    
    /**
     * Send notification email (generic)
     * 
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param array $headers
     * @return bool
     */
    public function send_notification($to, $subject, $message, $headers = []) {
        // Validate recipient email
        if (!is_email($to)) {
            return false;
        }
        
        // Default headers
        if (empty($headers)) {
            $headers = [
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $this->get_from_email()
            ];
        }
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send system alert email
     * 
     * @param string $alert_type
     * @param string $message
     * @param array $data
     * @return bool
     */
    public function send_system_alert($alert_type, $message, $data = []) {
        $agent = Agent::instance();
        $client_email = $agent->get_option('client_email', '');
        
        if (empty($client_email)) {
            return false;
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(
            __('[Mr.WordPress] Alert: %s - %s', 'mrwp-tools'),
            $alert_type,
            $site_name
        );
        
        // Build email content
        $email_content = sprintf(
            __('Alert on %s

Type: %s
Message: %s
Time: %s

Site: %s

— Mr.WordPress Tools', 'mrwp-tools'),
            $site_name,
            $alert_type,
            $message,
            current_time('Y-m-d H:i:s'),
            home_url()
        );
        
        // Add additional data if provided
        if (!empty($data)) {
            $email_content .= "\n\n" . __('Additional Information:', 'mrwp-tools') . "\n";
            foreach ($data as $key => $value) {
                $email_content .= sprintf("%s: %s\n", $key, $value);
            }
        }
        
        return $this->send_notification($client_email, $subject, $email_content);
    }
    
    /**
     * Test email configuration
     * 
     * @param string $test_email Optional test email address
     * @return array Test results
     */
    public function test_email_config($test_email = null) {
        $agent = Agent::instance();
        
        // Use provided email or client email
        $recipient = $test_email ?: $agent->get_option('client_email', '');
        
        if (empty($recipient)) {
            return [
                'success' => false,
                'error' => __('No recipient email address', 'mrwp-tools')
            ];
        }
        
        if (!is_email($recipient)) {
            return [
                'success' => false,
                'error' => __('Invalid email address', 'mrwp-tools')
            ];
        }
        
        // Send test email
        $subject = __('[Mr.WordPress] Test Email', 'mrwp-tools');
        $message = sprintf(
            __('This is a test email from Mr.WordPress Tools.

Site: %s
Time: %s

If you received this email, the email configuration is working correctly.

— Mr.WordPress Tools', 'mrwp-tools'),
            get_bloginfo('name'),
            current_time('Y-m-d H:i:s')
        );
        
        $sent = $this->send_notification($recipient, $subject, $message);
        
        return [
            'success' => $sent,
            'recipient' => $recipient,
            'sent_at' => time(),
            'error' => $sent ? null : __('Failed to send test email', 'mrwp-tools')
        ];
    }
    
    /**
     * Get email status information
     * 
     * @return array
     */
    public function get_email_status() {
        $agent = Agent::instance();
        
        return [
            'client_email' => $agent->get_option('client_email', ''),
            'admin_email' => get_option('admin_email'),
            'from_email' => $this->get_from_email(),
            'smtp_configured' => $this->is_smtp_configured()
        ];
    }
    
    /**
     * Check if SMTP is configured
     * 
     * @return bool
     */
    private function is_smtp_configured() {
        // Check common SMTP plugins/configurations
        return (
            defined('WPMS_ON') || // WP Mail SMTP
            class_exists('PHPMailer\\PHPMailer\\SMTP') ||
            function_exists('wp_mail_smtp') ||
            !empty(ini_get('SMTP'))
        );
    }
    
    /**
     * Log email activity
     * 
     * @param string $action
     * @param array $data
     */
    private function log_email_activity($action, $data = []) {
        $log_entry = [
            'timestamp' => time(),
            'action' => $action,
            'data' => $data
        ];
        
        // Get existing log
        $email_log = get_option('mrwp_email_log', []);
        
        // Add new entry
        $email_log[] = $log_entry;
        
        // Keep only last 50 entries
        if (count($email_log) > 50) {
            $email_log = array_slice($email_log, -50);
        }
        
        update_option('mrwp_email_log', $email_log);
    }
    
    /**
     * Get email activity log
     * 
     * @param int $limit
     * @return array
     */
    public function get_email_log($limit = 20) {
        $email_log = get_option('mrwp_email_log', []);
        
        // Sort by timestamp (newest first)
        usort($email_log, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return array_slice($email_log, 0, $limit);
    }
}