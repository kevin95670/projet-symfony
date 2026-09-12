$(document).on('click', '.btn-edit-comment', function() {
    const commentId = $(this).data('commentId');
    const commentDiv = $(`[data-comment-id="${commentId}"]`);
    const content = commentDiv.find('.comment-content');
    const editForm = commentDiv.find('.comment-edit-form');
    
    content.addClass('hidden');
    editForm.removeClass('hidden');
});

$(document).on('click', '.btn-cancel-edit', function() {
    const editForm = $(this).closest('.comment-edit-form');
    const commentDiv = editForm.closest('[data-comment-id]');
    const content = commentDiv.find('.comment-content');
    
    editForm.addClass('hidden');
    content.removeClass('hidden');
});