<?php
/**
 * AJAX handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Ajax_Handler {
    
    private $form_handler;
    private $approval_handler;
    
    public function __construct($form_handler, $approval_handler) {
        $this->form_handler = $form_handler;
        $this->approval_handler = $approval_handler;
        
        // Initialize AJAX hooks
        add_action('wp_ajax_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_nopriv_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_approve_association', array($this, 'handle_approval'));
        add_action('wp_ajax_reject_association', array($this, 'handle_rejection'));
    }
    
    public function handle_registration_submission() {
        $this->form_handler->handle_registration_submission();
    }
    
    public function handle_approval() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kizo_assoc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $registration_id = intval($_POST['registration_id']);
        $result = $this->approval_handler->approve_registration($registration_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Association approved successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Error approving association.'));
        }
    }
    
    public function handle_rejection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kizo_assoc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $registration_id = intval($_POST['registration_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        $result = $this->approval_handler->reject_registration($registration_id, $reason);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Association registration rejected.'));
        } else {
            wp_send_json_error(array('message' => 'Error rejecting association.'));
        }
    }
}