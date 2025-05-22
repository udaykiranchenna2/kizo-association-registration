<?php
/**
 * Admin page handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Admin_Page {
    
    private $database;
    private $approval_handler;
    
    public function __construct() {
        $this->database = new Kizo_Database();
        
        // Initialize hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
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
    
    public function add_admin_menu() {
        add_menu_page(
            'Association Registrations',
            'Associations',
            'manage_options',
            'kizo-associations',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
    }
    
    public function admin_page() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] == 'bulk_approve' && isset($_POST['registration_ids'])) {
            if (!$this->approval_handler) {
                $this->approval_handler = new Kizo_Approval_Handler(new Kizo_Email_Handler());
            }
            
            foreach ($_POST['registration_ids'] as $reg_id) {
                $this->approval_handler->approve_registration($reg_id);
            }
            echo '<div class="notice notice-success"><p>Selected registrations have been approved.</p></div>';
        }
        
        $registrations = $this->database->get_all_registrations();
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
}