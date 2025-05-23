<?php
/**
 * Plugin Name: Association Registration & Approval
 * Plugin URI: https://kizo.co.il
 * Description: Custom plugin for association registration, approval workflow, and automatic user/association creation
 * Version: 1.0.0
 * Author: Kizo Team
 * Text Domain: kizo-associations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KIZO_ASSOC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KIZO_ASSOC_PLUGIN_PATH', plugin_dir_path(__FILE__));

class KizoAssociationRegistration {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_nopriv_submit_association_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_approve_association', array($this, 'handle_approval'));
        add_action('wp_ajax_reject_association', array($this, 'handle_rejection'));
        add_action('wp_ajax_view_registration_details', array($this, 'handle_view_details'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Shortcode
        add_shortcode('association_registration_form', array($this, 'registration_form_shortcode'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        // Any initialization code
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('kizo-assoc-frontend', KIZO_ASSOC_PLUGIN_URL . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('kizo-assoc-frontend', KIZO_ASSOC_PLUGIN_URL . 'assets/frontend.css', array(), '1.0.0');
        
        wp_localize_script('kizo-assoc-frontend', 'kizo_assoc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kizo_assoc_nonce')
        ));
    }
    
    public function admin_enqueue_scripts() {
        wp_enqueue_script('kizo-assoc-admin', KIZO_ASSOC_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('kizo-assoc-admin', KIZO_ASSOC_PLUGIN_URL . 'assets/admin.css', array(), '1.0.0');
        
        wp_localize_script('kizo-assoc-admin', 'kizo_assoc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kizo_assoc_admin_nonce')
        ));
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'association_registrations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            association_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            description text NOT NULL,
            website varchar(255),
            ein varchar(100),
            contact_person varchar(255) NOT NULL,
            phone varchar(50),
            address text,
            category varchar(100),
            proof_document varchar(255),
            status varchar(20) DEFAULT 'pending',
            submitted_date datetime DEFAULT CURRENT_TIMESTAMP,
            approved_date datetime NULL,
            created_user_id int(11) NULL,
            created_association_id int(11) NULL,
            admin_notes text,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Association Registrations',
            'Associations Approve',
            'manage_options',
            'kizo-associations',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
    }
    
    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'association_registrations';
        
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] == 'bulk_approve' && isset($_POST['registration_ids'])) {
            foreach ($_POST['registration_ids'] as $reg_id) {
                $this->approve_registration($reg_id);
            }
            echo '<div class="notice notice-success"><p>Selected registrations have been approved.</p></div>';
        }
        
        $registrations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_date DESC");
        ?>
        <div class="wrap">
            <h1>Association Registrations</h1>
            
            <form method="post">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th>Association Name</th>
                            <th>Email</th>
                            <th>Contact Person</th>
                            <th>Status</th>
                            <th>Submitted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="registration_ids[]" value="<?php echo $registration->id; ?>">
                            </th>
                            <td><strong><?php echo esc_html($registration->association_name); ?></strong></td>
                            <td><?php echo esc_html($registration->email); ?></td>
                            <td><?php echo esc_html($registration->contact_person); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $registration->status; ?>">
                                    <?php echo ucfirst($registration->status); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($registration->submitted_date)); ?></td>
                            <td>
                                <button type="button" class="button view-details" data-id="<?php echo $registration->id; ?>">View Details</button>
                                <?php if ($registration->status == 'pending'): ?>
                                    <button type="button" class="button button-primary approve-btn" data-id="<?php echo $registration->id; ?>">Approve</button>
                                    <button type="button" class="button reject-btn" data-id="<?php echo $registration->id; ?>">Reject</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="tablenav">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="">Bulk Actions</option>
                            <option value="bulk_approve">Approve</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Modal for viewing details -->
        <div id="registration-modal" style="display:none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modal-body"></div>
            </div>
        </div>
        <?php
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
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'association_registrations';
        
        // Handle file upload
        $proof_document = '';
        if (!empty($_FILES['proof_document']['name'])) {
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'] . '/';
            $file_name = time() . '_' . $_FILES['proof_document']['name'];
            
            if (move_uploaded_file($_FILES['proof_document']['tmp_name'], $upload_path . $file_name)) {
                $proof_document = $upload_dir['url'] . '/' . $file_name;
            }
        }
        
        // Insert registration data
        $result = $wpdb->insert(
            $table_name,
            array(
                'association_name' => sanitize_text_field($_POST['association_name']),
                'email' => sanitize_email($_POST['email']),
                'description' => sanitize_textarea_field($_POST['description']),
                'website' => esc_url_raw($_POST['website']),
                'ein' => sanitize_text_field($_POST['ein']),
                'contact_person' => sanitize_text_field($_POST['contact_person']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'category' => sanitize_text_field($_POST['category']),
                'proof_document' => $proof_document,
                'status' => 'pending'
            )
        );
        
        if ($result) {
            // Send notification email to admin
            $this->send_admin_notification($_POST['association_name'], $_POST['email']);
            
            wp_send_json_success(array(
                'message' => 'Registration submitted successfully! We will review your application and get back to you soon.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'There was an error submitting your registration. Please try again.'
            ));
        }
    }
    
    public function handle_approval() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kizo_assoc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $registration_id = intval($_POST['registration_id']);
        $result = $this->approve_registration($registration_id);
        
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
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'association_registrations';
        $registration_id = intval($_POST['registration_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Update status to rejected
        $wpdb->update(
            $table_name,
            array(
                'status' => 'rejected',
                'admin_notes' => $reason
            ),
            array('id' => $registration_id)
        );
        
        // Get registration details for email
        $registration = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $registration_id));
        
        // Send rejection email
        $this->send_rejection_email($registration, $reason);
        
        wp_send_json_success(array('message' => 'Association registration rejected.'));
    }
    
    private function approve_registration($registration_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'association_registrations';
        
        // Get registration details
        $registration = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $registration_id));
        
        if (!$registration) {
            return false;
        }
        
        // Create WordPress user
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
            return false;
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $registration->contact_person);
        update_user_meta($user_id, 'association_name', $registration->association_name);
        update_user_meta($user_id, 'user_role', 'association');
        
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
        
        // Update registration status
        $wpdb->update(
            $table_name,
            array(
                'status' => 'approved',
                'approved_date' => current_time('mysql'),
                'created_user_id' => $user_id,
                'created_association_id' => $association_id
            ),
            array('id' => $registration_id)
        );
        
        // Send approval email with login credentials
        $this->send_approval_email($registration, $username, $password, $association_id);
        
        return true;
    }
    
    private function send_admin_notification($association_name, $email) {
        $subject = 'New Association Registration - ' . $association_name;
        $message = "A new association has registered on your site.\n\n";
        $message .= "Association: " . $association_name . "\n";
        $message .= "Email: " . $email . "\n\n";
        $message .= "Please review and approve/reject this registration in your admin panel.\n";
        $message .= admin_url('admin.php?page=kizo-associations');
        
        wp_mail(get_option('admin_email'), $subject, $message);
    }
    
    private function send_approval_email($registration, $username, $password, $association_id) {
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
        
        wp_mail($registration->email, $subject, $message);
    }
    
    private function send_rejection_email($registration, $reason) {
        $subject = 'Association Registration Update';
        $message = "Dear " . $registration->contact_person . ",\n\n";
        $message .= "Thank you for your interest in joining our platform. Unfortunately, we cannot approve your registration for '" . $registration->association_name . "' at this time.\n\n";
        if ($reason) {
            $message .= "Reason: " . $reason . "\n\n";
        }
        $message .= "If you have any questions or would like to resubmit your application, please contact us.\n\n";
        $message .= "Best regards,\n";
        $message .= "The Kizo Team";
        
        wp_mail($registration->email, $subject, $message);
    }
}

// Initialize the plugin
new KizoAssociationRegistration();

// Create assets directory and files if they don't exist
add_action('admin_init', 'kizo_create_asset_files');
 function handle_view_details() {
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
function kizo_create_asset_files() {
    $assets_dir = KIZO_ASSOC_PLUGIN_PATH . 'assets/';
    
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    
    // Create frontend.js
    $frontend_js = $assets_dir . 'frontend.js';
    if (!file_exists($frontend_js)) {
        $js_content = "
jQuery(document).ready(function($) {
    $('#association-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'submit_association_registration');
        
        $.ajax({
            url: kizo_assoc_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#registration-messages').html('<div class=\"success-message\">' + response.data.message + '</div>');
                    $('#association-registration-form')[0].reset();
                } else {
                    $('#registration-messages').html('<div class=\"error-message\">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#registration-messages').html('<div class=\"error-message\">An error occurred. Please try again.</div>');
            }
        });
    });
});";
        file_put_contents($frontend_js, $js_content);
    }
    
    // Create admin.js
    $admin_js = $assets_dir . 'admin.js';
    if (!file_exists($admin_js)) {
        $admin_js_content = "
jQuery(document).ready(function($) {
    $('.approve-btn').on('click', function() {
        var regId = $(this).data('id');
        if (confirm('Are you sure you want to approve this registration?')) {
            $.post(kizo_assoc_admin_ajax.ajax_url, {
                action: 'approve_association',
                registration_id: regId,
                nonce: kizo_assoc_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        }
    });
    
    $('.reject-btn').on('click', function() {
        var regId = $(this).data('id');
        var reason = prompt('Please provide a reason for rejection:');
        if (reason) {
            $.post(kizo_assoc_admin_ajax.ajax_url, {
                action: 'reject_association',
                registration_id: regId,
                reason: reason,
                nonce: kizo_assoc_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        }
    });
});";
        file_put_contents($admin_js, $admin_js_content);
    }
    
    // Create frontend.css
    $frontend_css = $assets_dir . 'frontend.css';
    if (!file_exists($frontend_css)) {
        $css_content = "
.kizo-association-registration {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.kizo-association-registration h3 {
    margin-bottom: 20px;
    color: #333;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    flex: 1;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.submit-btn {
    background-color: #007cba;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.submit-btn:hover {
    background-color: #005a87;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    margin-bottom: 20px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}";
        file_put_contents($frontend_css, $css_content);
    }
    
    // Create admin.css
    $admin_css = $assets_dir . 'admin.css';
    if (!file_exists($admin_css)) {
        $admin_css_content = "
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-approved {
    background-color: #d4edda;
    color: #155724;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
}

.approve-btn {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.reject-btn {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}";
        file_put_contents($admin_css, $admin_css_content);
    }
}

?>