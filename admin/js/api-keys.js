// Fonction pour copier dans le presse-papiers
function copyToClipboard(button) {
    const input = button.previousElementSibling;
    input.select();
    document.execCommand('copy');
    
    // Feedback visuel
    const originalText = button.textContent;
    button.textContent = 'Copié !';
    setTimeout(() => button.textContent = originalText, 2000);
}

// Fonction pour activer/désactiver une clé API
async function toggleApiKey(id, activate) {
    try {
        const response = await fetch('toggle-api-key.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },            body: JSON.stringify({
                id: parseInt(id),
                activate: activate ? 1 : 0
            })
        });

        const result = await response.json();
        if (result.success) {
            showApiKeys(document.querySelector('a[onclick="showApiKeys(this)"]'));
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show';
        errorDiv.innerHTML = `
            Erreur: ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.querySelector('#content').insertAdjacentElement('afterbegin', errorDiv);
    }
}

// Gestionnaire de création de clé API
async function createApiKey(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
      try {
        const response = await fetch('process-api-key.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Fermer correctement le modal avec Bootstrap 5
            const modalElement = document.getElementById('newApiKeyModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            modalInstance.hide(); // Cacher le modal proprement
            
            // Attendre que l'animation de fermeture soit terminée
            modalElement.addEventListener('hidden.bs.modal', () => {
                // Supprimer manuellement le backdrop et nettoyer
                document.querySelector('.modal-backdrop')?.remove();
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                
                // Recharger la page
                window.location.reload();
            }, { once: true }); // L'événement ne sera déclenché qu'une seule fois
        } else {
            alert('Erreur lors de la création de la clé API');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la création de la clé API');
    }
}

// (Removed duplicate and incomplete function definitions. The correct versions are already defined above.)
