// Fonction pour charger le contenu
function loadContent(url) {
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('content').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('content').innerHTML =
                `<div class="alert alert-danger">Erreur lors du chargement du contenu: ${error.message}</div>`;
        });
}

// Ajout de la classe active au lien cliqué
function setActiveLink(linkElement) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    linkElement.classList.add('active');
}

// Gestionnaires d'événements pour la navigation
function showDashboard(el) {
    setActiveLink(el);
    loadContent('dashboard-content.php');
}

function showApiKeys(el) {
    setActiveLink(el);
    loadContent('api-keys.php');
}

function showTransactions(el) {
    setActiveLink(el);
    loadContent('transactions.php');
}

function showWebhooks(el) {
    setActiveLink(el);
    loadContent('webhooks.php');
}

// Charger le dashboard par défaut
document.addEventListener('DOMContentLoaded', function() {
    showDashboard();
});
