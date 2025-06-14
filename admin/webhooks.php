<?php
require_once __DIR__ . '/../api/config/database.php';
$db = (new Database())->connect();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Construction de la requête
$where = [];
$params = [];

if ($status) {
    $where[] = "wn.status = ?";
    $params[] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM webhook_notifications wn 
    $whereClause
");
if ($where) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
}
$stmt->execute();
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les notifications
$stmt = $db->prepare("
    SELECT wn.*,
           cs.amount, cs.currency, cs.status as session_status,
           ak.merchant_name
    FROM webhook_notifications wn
    JOIN checkout_sessions cs ON wn.session_id = cs.id
    JOIN api_keys ak ON cs.merchant_api_key = ak.api_key
    $whereClause    ORDER BY wn.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if ($where) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
}
$stmt->execute();
$webhooks = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Webhooks</h1>
        <button class="btn btn-primary" onclick="resendFailedWebhooks()">
            Renvoyer les Webhooks Échoués
        </button>
    </div>

    <!-- Filtres -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
                        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Envoyé</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Échoué</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des webhooks -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Session</th>
                            <th>Marchand</th>
                            <th>URL</th>
                            <th>Statut</th>
                            <th>Tentatives</th>
                            <th>Dernière Tentative</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhooks as $wh): ?>
                        <tr>
                            <td><?= htmlspecialchars($wh['session_id']) ?></td>
                            <td><?= htmlspecialchars($wh['merchant_name']) ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($wh['webhook_url']) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $wh['status'] === 'sent' ? 'success' : 
                                    ($wh['status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= htmlspecialchars($wh['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($wh['attempts']) ?></td>
                            <td>
                                <?= $wh['last_attempt_at'] ? 
                                    date('d/m/Y H:i:s', strtotime($wh['last_attempt_at'])) : 
                                    'N/A' 
                                ?>
                            </td>
                            <td>
                                <?php if ($wh['status'] !== 'sent'): ?>
                                <button class="btn btn-sm btn-primary" onclick="resendWebhook(<?= $wh['id'] ?>)">
                                    Renvoyer
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-info" onclick="showPayload(<?= htmlspecialchars(json_encode($wh['payload'])) ?>)">
                                    Payload
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?><?= $status ? "&status=$status" : '' ?>">Précédent</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $status ? "&status=$status" : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?><?= $status ? "&status=$status" : '' ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Payload -->
<div class="modal fade" id="payloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payload Webhook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="payloadContent" style="max-height: 400px; overflow: auto;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function showPayload(payload) {
    document.getElementById('payloadContent').textContent = 
        JSON.stringify(payload, null, 2);
    new bootstrap.Modal(document.getElementById('payloadModal')).show();
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container-fluid').insertAdjacentElement('afterbegin', alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function resendWebhook(id) {
    if (confirm('Voulez-vous vraiment renvoyer ce webhook ?')) {
        fetch('webhook-resend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Webhook renvoyé avec succès');
            } else {
                showAlert(data.error || 'Échec du renvoi du webhook', 'danger');
            }
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(error => {
            showAlert('Erreur lors du renvoi du webhook: ' + error, 'danger');
        });
    }
}

function resendFailedWebhooks() {
    if (confirm('Voulez-vous vraiment renvoyer tous les webhooks échoués ?')) {
        fetch('webhook-resend-all.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Webhooks renvoyés avec succès');
            } else {
                showAlert(data.error || 'Échec du renvoi des webhooks', 'danger');
            }
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(error => {
            showAlert('Erreur lors du renvoi des webhooks: ' + error, 'danger');
        });
    }
}
</script>
