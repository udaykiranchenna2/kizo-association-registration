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
        
        $.post(kizo_assoc_admin_ajax.ajax_url, {
            action: 'view_registration_details',
            registration_id: regId,
            nonce: kizo_assoc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#modal-body').html(response.data.html);
                $('#registration-modal').show();
            } else {
                alert('Error: ' + response.data.message);
            }
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
});