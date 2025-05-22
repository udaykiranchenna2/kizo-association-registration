<?php
/**
 * Approval handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Approval_Handler {
    
    private $email_handler;
    private $database;
    
    public function __construct($email_handler) {
        $this->email_handler = $email_handler;
        $this->database = new Kizo_Database();
    }
    
    public function approve_registration($registration_id) {
        // Get registration details
        $registration = $this->database->get_registration($registration_id);
        
        if (!$registration) {
            return false;
        }
        
        // Create WordPress user
        $user_data = $this->create_wordpress_user($registration);
        
        if (is_wp_error($user_data)) {
            return false;
        }
        
        // Create association post
        $association_id = $this->create_association_post($registration, $user_data['user_id']);
        
        // Update registration status
        $this->database->update_registration_status($registration_id, 'approved', array(
            'approved_date' => current_time('mysql'),
            'created_user_id' => $user_data['user_id'],
            'created_association_id' => $association_id
        ));
        
        // Send approval email with login credentials
        $this->email_handler->send_approval_email($registration, $user_data['username'], $user_data['password'], $association_id);
        
        return true;
    }
    
    public function reject_registration($registration_id, $reason) {
        // Get registration details for email
        $registration = $this->database->get_registration($registration_id);
        
        if (!$registration) {
            return false;
        }
        
        // Update status to rejected
        $result = $this->database->update_registration_status($registration_id, 'rejected', array(
            'admin_notes' => $reason
        ));
        
        // Send rejection email
        $this->email_handler->send_rejection_email($registration, $reason);
        
        return $result;
    }
    
    private function create_wordpress_user($registration) {
        $username = sanitize_user($registration->association_name);
        $username = str_replace(' ', '_', strtolower($username));
        
        // Make username unique if it exists
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        $password = wp_generate_password(12, false);
        
        $user_id = wp_create_user($username, $password, $registration->email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $registration->contact_person);
        update_user_meta($user_id, 'association_name', $registration->association_name);
        update_user_meta($user_id, 'user_role', 'association');
        
        return array(
            'user_id' => $user_id,
            'username' => $username,
            'password' => $password
        );
    }
    
    private function create_association_post($registration, $user_id) {
        // Create association post (assuming associations_dir_ltg post type exists)
        $association_post = array(
            'post_title' => $registration->association_name,
            'post_content' => $registration->description,
            'post_status' => 'draft', // They can publish later
            'post_type' => 'associations_dir_ltg',
            'post_author' => $user_id
        );
        
        $association_id = wp_insert_post($association_post);
        
        if ($association_id) {
            // Add custom fields to association post
            update_post_meta($association_id, 'email', $registration->email);
            update_post_meta($association_id, 'website', $registration->website);
            update_post_meta($association_id, 'ein', $registration->ein);
            update_post_meta($association_id, 'contact_person', $registration->contact_person);
            update_post_meta($association_id, 'phone', $registration->phone);
            update_post_meta($association_id, 'address', $registration->address);
            update_post_meta($association_id, 'category', $registration->category);
            update_post_meta($association_id, 'associated_user_id', $user_id);
        }
        
        return $association_id;
    }
}