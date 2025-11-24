document.addEventListener('DOMContentLoaded', function() {
    // Handle edit comment button clicks
    document.addEventListener('click', function(e) {
        // Vérifier si le clic provient d'un bouton de modification
        const editButton = e.target.closest('.edit-comment');
        if (!editButton) return;
        
        e.preventDefault();
        
        const commentItem = editButton.closest('.forum-comment-item');
        const commentId = editButton.getAttribute('data-comment-id');
        const commentContent = editButton.getAttribute('data-original-content');
        const commentTextElement = commentItem.querySelector('.forum-comment-text');
        
        // Créer le formulaire d'édition
        const form = document.createElement('form');
        form.className = 'edit-comment-form';
        form.style.marginTop = '10px';
        form.innerHTML = `
            <div class="mb-3">
                <textarea name="contenu" class="form-control" rows="3" required>${commentContent}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit">Annuler</button>
            </div>
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
        `;

        // Sauvegarder le contenu original
        const originalContent = commentTextElement.innerHTML;
        
        // Cacher le texte du commentaire et afficher le formulaire
        commentTextElement.style.display = 'none';
        commentTextElement.insertAdjacentElement('afterend', form);
        
        // Masquer le bouton d'édition pendant l'édition
        editButton.style.display = 'none';
        
        // Gérer la soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Ajouter le token CSRF au FormData
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            formData.append('_token', csrfToken);

            // Créer les en-têtes pour la requête
            const headers = new Headers();
            headers.append('X-Requested-With', 'XMLHttpRequest');
            
            // Si vous utilisez le composant Security de Symfony, vous pourriez avoir besoin d'ajouter ceci :
            // headers.append('X-CSRF-TOKEN', csrfToken);

            fetch(`/forul/comment/${commentId}/edit`, {
                method: 'POST',
                body: formData,
                headers: headers,
                credentials: 'same-origin' // Important pour les cookies de session
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Erreur lors de la mise à jour du commentaire');
                    }).catch(() => {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour le contenu du commentaire
                    commentTextElement.innerHTML = data.content;
                    // Mettre à jour l'attribut data-original-content avec le nouveau contenu
                    editButton.setAttribute('data-original-content', formData.get('contenu'));
                    // Afficher un message de succès
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show mt-2';
                    alert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    form.parentNode.insertBefore(alert, form);
                    // Supprimer l'alerte après 3 secondes
                    setTimeout(() => alert.remove(), 3000);
                } else {
                    throw new Error(data.message || 'Erreur inconnue lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Afficher un message d'erreur plus détaillé
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show mt-2';
                alert.innerHTML = `
                    Erreur: ${error.message || 'Une erreur est survenue lors de la mise à jour du commentaire.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                form.parentNode.insertBefore(alert, form);
                // Supprimer l'alerte après 5 secondes
                setTimeout(() => alert.remove(), 5000);
            })
            .finally(() => {
                // Nettoyer le formulaire et réafficher le texte du commentaire
                form.remove();
                commentTextElement.style.display = 'block';
                editButton.style.display = 'inline-block';
            });
        });
        
        // Gérer le bouton d'annulation
        const cancelButton = form.querySelector('.cancel-edit');
        cancelButton.addEventListener('click', function() {
            form.remove();
            commentTextElement.style.display = 'block';
            editButton.style.display = 'inline-block';
        });
    });
});
