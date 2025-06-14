<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../api/config/database.php';
$db = (new Database())->connect();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Construction de la requête
$where = [];
$params = [];

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($dateStart) {
    $where[] = "created_at >= ?";
    $params[] = $dateStart . ' 00:00:00';
}

if ($dateEnd) {
    $where[] = "created_at <= ?";
    $params[] = $dateEnd . ' 23:59:59';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$stmt = $db->prepare("SELECT COUNT(*) FROM checkout_sessions $whereClause");
if ($where) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
}
$stmt->execute();
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les transactions
$stmt = $db->prepare("    SELECT cs.*, 
           pl.action as last_action,
           wn.status as webhook_status,
           ak.merchant_name
    FROM checkout_sessions cs
    LEFT JOIN api_keys ak ON cs.aggregated_merchant_id = ak.merchant_id
    LEFT JOIN payment_logs pl ON cs.id = pl.session_id 
        AND pl.created_at = (
            SELECT MAX(created_at) 
            FROM payment_logs 
            WHERE session_id = cs.id
        )
    LEFT JOIN webhook_notifications wn ON cs.id = wn.session_id
        AND wn.created_at = (
            SELECT MAX(created_at) 
            FROM webhook_notifications 
            WHERE session_id = cs.id
        )
    $whereClause    ORDER BY cs.created_at DESC 
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
$transactions = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transactions</h1>
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
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Complété</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Échoué</option>
                        <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expiré</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($dateStart) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($dateEnd) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Session</th>
                            <!-- <th>ID Marchand</th> -->
                            <th>Nom Marchand</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Référence Client</th>
                            <th>Webhook</th>
                            <th>Créée le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody> <?php foreach ($transactions as $t): ?> <tr>
                                <td><?= htmlspecialchars($t['id']) ?></td>
                                <!-- <td><?= htmlspecialchars($t['aggregated_merchant_id'] ?? 'N/A') ?></td> -->
                                <td><?= htmlspecialchars($t['merchant_name'] ?? 'N/A') ?></td>
                                <td><?= number_format($t['amount']) ?> <?= htmlspecialchars($t['currency']) ?></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $t['status'] === 'completed' ? 'success' : ($t['status'] === 'pending' ? 'warning' : 'danger')
                                                            ?>">
                                        <?= htmlspecialchars($t['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($t['client_reference'] ?? 'N/A');
                                    ?></td>
                                <td> <span class="badge bg-<?=
                                                            $t['webhook_status'] === 'sent' ? 'success' : ($t['webhook_status'] === 'pending' ? 'warning' : 'danger')
                                                            ?>">
                                        <?= htmlspecialchars($t['webhook_status'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="showDetails('<?= $t['id'] ?>')">
                                        Détails
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
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $status ? "&status=$status" : '' ?><?= $dateStart ? "&date_start=$dateStart" : '' ?><?= $dateEnd ? "&date_end=$dateEnd" : '' ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $status ? "&status=$status" : '' ?><?= $dateStart ? "&date_start=$dateStart" : '' ?><?= $dateEnd ? "&date_end=$dateEnd" : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $status ? "&status=$status" : '' ?><?= $dateStart ? "&date_start=$dateStart" : '' ?><?= $dateEnd ? "&date_end=$dateEnd" : '' ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<div class="modal fade" id="transactionDetails" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetailsContent">
                <!-- Le contenu sera chargé dynamiquement -->
                <div class="text-center d-none" id="loadingSpinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
                <div class="alert alert-danger d-none" id="errorAlert">
                    Une erreur est survenue lors du chargement des détails.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Le JavaScript est maintenant chargé depuis js/transactions.js -->