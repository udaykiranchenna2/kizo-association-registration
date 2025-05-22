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
    // Approve button click
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
    
    // Reject button click
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
    
    // View details button click
    $('.view-details').on('click', function() {
        var regId = $(this).data('id');
        
        // Show loading state
        $('#modal-body').html('<div class=\"loading\">Loading registration details...</div>');
        $('#registration-modal').show();
        
        $.post(kizo_assoc_admin_ajax.ajax_url, {
            action: 'view_registration_details',
            registration_id: regId,
            nonce: kizo_assoc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#modal-body').html(response.data.html);
            } else {
                $('#modal-body').html('<div class=\"error-message\">Error: ' + response.data.message + '</div>');
            }
        }).fail(function() {
            $('#modal-body').html('<div class=\"error-message\">Failed to load registration details.</div>');
        });
    });
    
    // Close modal
    $('.close, #registration-modal').on('click', function(e) {
        if (e.target === this) {
            $('#registration-modal').hide();
        }
    });
    
    // Approve from modal
    $(document).on('click', '.approve-btn-modal', function() {
        var regId = $(this).data('id');
        if (confirm('Are you sure you want to approve this registration?')) {
            $.post(kizo_assoc_admin_ajax.ajax_url, {
                action: 'approve_association',
                registration_id: regId,
                nonce: kizo_assoc_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('#registration-modal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        }
    });
    
    // Reject from modal
    $(document).on('click', '.reject-btn-modal', function() {
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
                    $('#registration-modal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        }
    });
    
    // Close modal with Escape key
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('#registration-modal').hide();
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
/* Status badges */
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

/* Action buttons */
.approve-btn, .approve-btn-modal {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.reject-btn, .reject-btn-modal {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

/* Modal styles */
#registration-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 15px;
    right: 20px;
    z-index: 1;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

/* Registration details styles */
.registration-details {
    padding: 30px;
}

.registration-details h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.detail-section {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #0073aa;
}

.detail-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #23282d;
    font-size: 16px;
}

.detail-row {
    display: flex;
    margin-bottom: 10px;
    align-items: flex-start;
}

.detail-row strong {
    min-width: 140px;
    margin-right: 15px;
    color: #555;
    flex-shrink: 0;
}

.detail-row span {
    flex: 1;
    word-wrap: break-word;
}

.detail-row span a {
    color: #0073aa;
    text-decoration: none;
}

.detail-row span a:hover {
    text-decoration: underline;
}

/* Full width sections */
.detail-section:nth-child(n+3) {
    grid-column: 1 / -1;
}

.detail-actions {
    text-align: center;
    padding: 20px;
    border-top: 1px solid #ddd;
    background-color: #f9f9f9;
    margin: 20px -30px -30px -30px;
    border-radius: 0 0 8px 8px;
}

.detail-actions .button {
    margin: 0 10px;
    padding: 8px 20px;
}

/* Responsive design */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 20px auto;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .registration-details {
        padding: 20px;
    }
    
    .detail-row {
        flex-direction: column;
    }
    
    .detail-row strong {
        min-width: auto;
        margin-right: 0;
        margin-bottom: 5px;
    }
    
    .detail-actions {
        margin: 20px -20px -20px -20px;
    }
}

/* Loading state */
.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.loading:before {
    content: '';
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}";
            file_put_contents($admin_css, $admin_css_content);
        }
    }
}