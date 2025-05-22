<?php
/**
 * Email handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Email_Handler {
    
    public function send_admin_notification($association_name, $email) {
        $subject = 'New Association Registration - ' . $association_name;
        $message = "A new association has registered on your site.\n\n";
        $message .= "Association: " . $association_name . "\n";
        $message .= "Email: " . $email . "\n\n";
        $message .= "Please review and approve/reject this registration in your admin panel.\n";
        $message .= admin_url('admin.php?page=kizo-associations');
        
        return wp_mail(get_option('admin_email'), $subject, $message);
    }
    
    public function send_approval_email($registration, $username, $password, $association_id) {
        $subject = 'Your Association Registration Has Been Approved!';
        $message = "Dear " . $registration->contact_person . ",\n\n";
        $message .= "Great news! Your association registration for '" . $registration->association_name . "' has been approved.\n\n";
        $message .= "Your login credentials:\n";
        $message .= "Username: " . $username . "\n";
        $message .= "Password: " . $password . "\n";
        $message .= "Login URL: " . wp_login_url() . "\n\n";
        $message .= "You can now log in and manage your association page.\n\n";
        $message .= "Thank you for joining our platform!\n";
        $message .= "The Kizo Team";
        
        return wp_mail($registration->email, $subject, $message);
    }
    
    public function send_rejection_email($registration, $reason) {
        $subject = 'Association Registration Update';
        $message = "Dear " . $registration->contact_person . ",\n\n";
        $message .= "Thank you for your interest in joining our platform. Unfortunately, we cannot approve your registration for '" . $registration->association_name . "' at this time.\n\n";
        if ($reason) {
            $message .= "Reason: " . $reason . "\n\n";
        }
        $message .= "If you have any questions or would like to resubmit your application, please contact us.\n\n";
        $message .= "Best regards,\n";
        $message .= "The Kizo Team";
        
        return wp_mail($registration->email, $subject, $message);
    }
}