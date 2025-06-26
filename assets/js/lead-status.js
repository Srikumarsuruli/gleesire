$(document).ready(function() {
    $(document).on('click', '.save-status-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const enquiryId = $(this).data('id');
        const status = $('.lead-status-select[data-id="' + enquiryId + '"]').val();
        
        if (!status) {
            alert('Please select a status');
            return;
        }
        
        const $icon = $(this).find('i');
        $icon.removeClass('fa-check').addClass('fa-spinner fa-spin');
        
        $.ajax({
            url: 'update_lead_status.php',
            method: 'POST',
            data: {
                enquiry_id: enquiryId,
                status: status
            },
            success: function() {
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-check');
            },
            error: function() {
                alert('Error saving status');
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-check');
            }
        });
        
        return false;
    });
});