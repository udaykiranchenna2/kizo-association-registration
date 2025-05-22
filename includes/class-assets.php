<?php
/**
 * Assets handling for Association Registration plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kizo_Assets {
    
    public function create_asset_files() {
        $assets_dir = KIZO_ASSOC_PLUGIN_PATH . 'assets/';
        
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        $this->create_frontend_js($assets_dir);
        $this->create_admin_js($assets_dir);
        $this->create_frontend_css($assets_dir);
        $this->create_admin_css($assets_dir);
    }
    
    private function create_frontend_js($assets_dir) {
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
    }
    
    private function create_admin_js($assets_dir) {
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
    }
    
    private function create_frontend_css($assets_dir) {
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
    }
    
    private function create_admin_css($assets_dir) {
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
}