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

// Include required files
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-database.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-email-handler.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-form-handler.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-admin-page.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-ajax-handler.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-approval-handler.php';
require_once KIZO_ASSOC_PLUGIN_PATH . 'includes/class-assets.php';

// Main plugin class
class KizoAssociationRegistration {
    
    private $database;
    private $email_handler;
    private $form_handler;
    private $admin_page;
    private $ajax_handler;
    private $approval_handler;
    private $assets;
    
    public function __construct() {
        // Initialize components
        $this->database = new Kizo_Database();
        $this->email_handler = new Kizo_Email_Handler();
        $this->form_handler = new Kizo_Form_Handler($this->email_handler);
        $this->admin_page = new Kizo_Admin_Page();
        $this->ajax_handler = new Kizo_Ajax_Handler($this->form_handler, $this->approval_handler);
        $this->approval_handler = new Kizo_Approval_Handler($this->email_handler);
        $this->assets = new Kizo_Assets();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this->database, 'create_tables'));
        
        // Asset creation
        add_action('admin_init', array($this->assets, 'create_asset_files'));
    }
    
    public function init() {
        // Any initialization code
    }
}

// Initialize the plugin
new KizoAssociationRegistration();