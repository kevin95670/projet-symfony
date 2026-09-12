// assets/js/commentaire.js

document.querySelectorAll('.btn-edit-comment').forEach(btn => {
    btn.addEventListener('click', function() {
        const commentId = this.dataset.commentId;
        const commentDiv = document.querySelector(`[data-comment-id="${commentId}"]`);
        const content = commentDiv.querySelector('.comment-content');
        const editForm = commentDiv.querySelector('.comment-edit-form');
        
        content.classList.add('hidden');
        editForm.classList.remove('hidden');
    });
});

document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const editForm = this.closest('.comment-edit-form');
        const commentDiv = editForm.closest('[data-comment-id]');
        const content = commentDiv.querySelector('.comment-content');
        
        editForm.classList.add('hidden');
        content.classList.remove('hidden');
    });
});