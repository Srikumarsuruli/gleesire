// Comments functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle comment form submission
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const enquiryId = document.querySelector('input[name="enquiry_id"]').value;
            const comment = document.getElementById('comment').value;
            const redirectUrl = document.querySelector('input[name="redirect"]').value;
            
            if (!comment.trim()) {
                alert('Please enter a comment');
                return;
            }
            
            // Use traditional form submission instead of AJAX
            commentForm.submit();
        });
    }
});