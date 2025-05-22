<?php
/**
 * Database operations for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Database {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'association_registrations';
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
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
    
    public function insert_registration($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'association_name' => sanitize_text_field($data['association_name']),
                'email' => sanitize_email($data['email']),
                'description' => sanitize_textarea_field($data['description']),
                'website' => esc_url_raw($data['website']),
                'ein' => sanitize_text_field($data['ein']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'category' => sanitize_text_field($data['category']),
                'proof_document' => $data['proof_document'],
                'status' => 'pending'
            )
        );
    }
    
    public function get_all_registrations() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY submitted_date DESC");
    }
    
    public function get_registration($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }
    
    public function update_registration_status($id, $status, $additional_data = array()) {
        global $wpdb;
        
        $update_data = array_merge(array('status' => $status), $additional_data);
        
        return $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id)
        );
    }
    
    public function get_table_name() {
        return $this->table_name;
    }
}