// Variable globale pour le modal et les templates HTML
var transactionModal;
const spinnerTemplate = `
    <div class="text-center" id="loadingSpinner">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
    </div>`;
const errorTemplate = `
    <div class="alert alert-danger d-none" id="errorAlert">
        Une erreur est survenue lors du chargement des détails.
    </div>`;

// Fonction pour obtenir l'URL de base
function getBaseUrl() {
    // Extraire directement le chemin wave/admin depuis l'URL actuelle
    const path = window.location.pathname;
    const baseUrl = '/wave/admin';
    
    // Si nous sommes dans une page qui contient déjà /wave/admin, extraire le chemin correct
    const adminIndex = path.indexOf('/wave/admin');
    if (adminIndex !== -1) {
        // Extrait seulement la partie jusqu'à /wave/admin
        return path.substring(0, adminIndex + '/wave/admin'.length);
    }
    
    return baseUrl;
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function () {
    transactionModal = new bootstrap.Modal(document.getElementById('transactionDetails'));
});

// Fonction de détails accessible globalement
function showDetails(sessionId) {
    console.log('Opening modal for session:', sessionId);

    if (!transactionModal) {
        console.log('Initializing modal...');
        transactionModal = new bootstrap.Modal(document.getElementById('transactionDetails'));
    }

    const contentDiv = document.getElementById('transactionDetailsContent');

    // Réinitialiser le contenu avec le spinner visible
    contentDiv.innerHTML = spinnerTemplate + errorTemplate;

    // Ouvrir le modal
    transactionModal.show();    // Construire l'URL complète
    const baseUrl = getBaseUrl();
    const url = `${baseUrl}/transaction-details.php?id=${encodeURIComponent(sessionId)}`;
    
    // Nettoyer l'URL en supprimant les doubles slashes potentiels sauf après http(s):
    const cleanUrl = url.replace(/([^:])\/+/g, '$1/');
    console.log('Base URL:', baseUrl);
    console.log('Fetching details from:', cleanUrl);

    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            console.log('Received HTML content length:', html.length);
            contentDiv.innerHTML = html.trim() || '<div class="alert alert-warning">Aucun détail trouvé</div>';
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = spinnerTemplate + errorTemplate;
            document.getElementById('loadingSpinner').classList.add('d-none');
            const errorAlert = document.getElementById('errorAlert');
            errorAlert.classList.remove('d-none');
            errorAlert.textContent = `Erreur: ${error.message}`;
        });
}
