// Fonction pour afficher le payload dans le modal
function showPayload(payload) {
    document.getElementById('payloadContent').textContent = 
        JSON.stringify(payload, null, 2);
    new bootstrap.Modal(document.getElementById('payloadModal')).show();
}

// Fonction pour renvoyer un webhook spécifique
function resendWebhook(id) {
    if (confirm('Voulez-vous vraiment renvoyer ce webhook ?')) {
        fetch('webhook-resend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        }).then(() => window.location.reload());
    }
}

// Fonction pour renvoyer tous les webhooks échoués
function resendFailedWebhooks() {
    if (confirm('Voulez-vous vraiment renvoyer tous les webhooks échoués ?')) {
        fetch('webhook-resend-all.php', {
            method: 'POST'
        }).then(() => window.location.reload());
    }
}
