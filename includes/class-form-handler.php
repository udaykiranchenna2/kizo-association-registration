<?php
/**
 * Form handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Form_Handler {
    
    private $email_handler;
    private $database;
    
    public function __construct($email_handler) {
        $this->email_handler = $email_handler;
        $this->database = new Kizo_Database();
        
        // Initialize hooks
        add_shortcode('association_registration_form', array($this, 'registration_form_shortcode'));
    }
    
    public function registration_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Register Your Association',
            'class' => ''
        ), $atts);
        
        ob_start();
        ?>
        <div class="kizo-association-registration <?php echo esc_attr($atts['class']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <div id="registration-messages"></div>
            
            <form id="association-registration-form" enctype="multipart/form-data">
                <?php wp_nonce_field('kizo_assoc_nonce', 'security'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="association_name">Association Name *</label>
                        <input type="text" id="association_name" name="association_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_person">Contact Person *</label>
                        <input type="text" id="contact_person" name="contact_person" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="4" required placeholder="Describe your association's mission and activities"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" placeholder="https://yourwebsite.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="ein">EIN/Tax ID</label>
                        <input type="text" id="ein" name="ein">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">Select Category</option>
                        <option value="education">Education</option>
                        <option value="health">Health</option>
                        <option value="environment">Environment</option>
                        <option value="social">Social Services</option>
                        <option value="animals">Animal Welfare</option>
                        <option value="arts">Arts & Culture</option>
                        <option value="religion">Religious</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="proof_document">Proof Document *</label>
                    <input type="file" id="proof_document" name="proof_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small>Upload a document proving you represent this association (Certificate, IRS determination letter, etc.)</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="terms_agreement" name="terms_agreement" required>
                        I agree to the terms and conditions and confirm that I am authorized to represent this association *
                    </label>
                </div>
                
                <button type="submit" class="submit-btn">Submit Registration</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_registration_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'kizo_assoc_nonce')) {
            wp_die('Security check failed');
        }
        
        // Handle file upload
        $proof_document = $this->handle_file_upload();
        
        // Prepare data
        $data = $_POST;
        $data['proof_document'] = $proof_document;
        
        // Insert registration data
        $result = $this->database->insert_registration($data);
        
        if ($result) {
            // Send notification email to admin
            $this->email_handler->send_admin_notification($_POST['association_name'], $_POST['email']);
            
            wp_send_json_success(array(
                'message' => 'Registration submitted successfully! We will review your application and get back to you soon.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'There was an error submitting your registration. Please try again.'
            ));
        }
    }
    
    private function handle_file_upload() {
        $proof_document = '';
        
        if (!empty($_FILES['proof_document']['name'])) {
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'] . '/';
            $file_name = time() . '_' . $_FILES['proof_document']['name'];
            
            if (move_uploaded_file($_FILES['proof_document']['tmp_name'], $upload_path . $file_name)) {
                $proof_document = $upload_dir['url'] . '/' . $file_name;
            }
        }
        
        return $proof_document;
    }
}