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
    private $database;
    
    public function __construct($form_handler, $approval_handler) {
        $this->form_handler = $form_handler;
        $this->approval_handler = $approval_handler;
        $this->database = new Kizo_Database();
        
        // Initialize AJAX hooks
        add_action('wp_ajax_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_nopriv_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_approve_association', array($this, 'handle_approval'));
        add_action('wp_ajax_reject_association', array($this, 'handle_rejection'));
        add_action('wp_ajax_view_registration_details', array($this, 'handle_view_details'));
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
    
    public function handle_view_details() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kizo_assoc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $registration_id = intval($_POST['registration_id']);
        $registration = $this->database->get_registration($registration_id);
        
        if (!$registration) {
            wp_send_json_error(array('message' => 'Registration not found.'));
        }
        
        // Generate HTML for the modal
        ob_start();
        ?>
        <div class="registration-details">
            <h2><?php echo esc_html($registration->association_name); ?></h2>
            
            <div class="details-grid">
                <div class="detail-section">
                    <h3>Basic Information</h3>
                    <div class="detail-row">
                        <strong>Association Name:</strong>
                        <span><?php echo esc_html($registration->association_name); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Email:</strong>
                        <span><?php echo esc_html($registration->email); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Contact Person:</strong>
                        <span><?php echo esc_html($registration->contact_person); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Phone:</strong>
                        <span><?php echo esc_html($registration->phone ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Website:</strong>
                        <span>
                            <?php if ($registration->website): ?>
                                <a href="<?php echo esc_url($registration->website); ?>" target="_blank"><?php echo esc_html($registration->website); ?></a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3>Organization Details</h3>
                    <div class="detail-row">
                        <strong>Category:</strong>
                        <span><?php echo esc_html($registration->category ?: 'Not specified'); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>EIN/Tax ID:</strong>
                        <span><?php echo esc_html($registration->ein ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Status:</strong>
                        <span class="status-badge status-<?php echo $registration->status; ?>">
                            <?php echo ucfirst($registration->status); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <strong>Submitted Date:</strong>
                        <span><?php echo date('F j, Y \a\t g:i A', strtotime($registration->submitted_date)); ?></span>
                    </div>
                    <?php if ($registration->approved_date): ?>
                    <div class="detail-row">
                        <strong>Approved Date:</strong>
                        <span><?php echo date('F j, Y \a\t g:i A', strtotime($registration->approved_date)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Address</h3>
                <div class="detail-row">
                    <span><?php echo $registration->address ? nl2br(esc_html($registration->address)) : 'Not provided'; ?></span>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Description</h3>
                <div class="detail-row">
                    <span><?php echo nl2br(esc_html($registration->description)); ?></span>
                </div>
            </div>
            
            <?php if ($registration->proof_document): ?>
            <div class="detail-section">
                <h3>Proof Document</h3>
                <div class="detail-row">
                    <a href="<?php echo esc_url($registration->proof_document); ?>" target="_blank" class="button button-secondary">
                        View Document
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($registration->admin_notes): ?>
            <div class="detail-section">
                <h3>Admin Notes</h3>
                <div class="detail-row">
                    <span><?php echo nl2br(esc_html($registration->admin_notes)); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($registration->status == 'pending'): ?>
            <div class="detail-actions">
                <button type="button" class="button button-primary approve-btn-modal" data-id="<?php echo $registration->id; ?>">
                    Approve Registration
                </button>
                <button type="button" class="button reject-btn-modal" data-id="<?php echo $registration->id; ?>">
                    Reject Registration
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}