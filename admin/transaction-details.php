<?php
require_once __DIR__ . '/../api/config/database.php';
$db = (new Database())->connect();

$sessionId = $_GET['id'] ?? '';
if (!$sessionId) {
    echo '<div class="alert alert-danger">ID de session manquant</div>';
    return;
}

// Récupérer les détails de la session avec le nom du marchand
$stmt = $db->prepare("
    SELECT cs.*, ak.merchant_name 
    FROM checkout_sessions cs
    LEFT JOIN api_keys ak ON cs.aggregated_merchant_id = ak.merchant_id 
    WHERE cs.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    echo '<div class="alert alert-danger">Session non trouvée</div>';
    return;
}

// Récupérer l'historique des actions
$stmt = $db->prepare("
    SELECT * FROM payment_logs 
    WHERE session_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$sessionId]);
$logs = $stmt->fetchAll();

// Récupérer l'historique des webhooks
$stmt = $db->prepare("
    SELECT * FROM webhook_notifications 
    WHERE session_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$sessionId]);
$webhooks = $stmt->fetchAll();
?>

<div class="container">
    <!-- Section 1 : Informations Générales -->
    <h6 class="font-weight-bold">Informations Générales</h6>
    <table class="table table-sm w-100">
        <tr>
            <th>ID Session:</th>
            <td><?= htmlspecialchars($session['id']) ?></td>
        </tr>
        <tr>
            <th>ID Marchand Agrégé:</th>
            <td><?= htmlspecialchars($session['aggregated_merchant_id'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <th>Nom du Marchand:</th>
            <td><?= htmlspecialchars($session['merchant_name'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <th>Montant:</th>
            <td><?= number_format($session['amount']) ?> <?= htmlspecialchars($session['currency']) ?></td>
        </tr>
        <tr>
            <th>Statut:</th>
            <td>
                <span class="badge bg-<?= 
                    $session['status'] === 'completed' ? 'success' : 
                    ($session['status'] === 'pending' ? 'warning' : 'danger') 
                ?>">
                    <?= htmlspecialchars($session['status']) ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Référence Client:</th>
            <td><?= htmlspecialchars($session['client_reference'] ?? 'N/A') ?></td>
        </tr>
        <?php if ($session['description']): ?>
        <tr>
            <th>Description:</th>
            <td><?= htmlspecialchars($session['description']  ?? 'N/A') ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($session['metadata']): ?>
        <tr>
            <th>Metadata:</th>
            <td><pre><?= htmlspecialchars(json_encode(json_decode($session['metadata']), JSON_PRETTY_PRINT)) ?></pre></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>URL de Succès:</th>
            <td><?= htmlspecialchars($session['success_url']) ?></td>
        </tr>
        <tr>
            <th>URL d'Erreur:</th>
            <td><?= htmlspecialchars($session['cancel_url']) ?></td>
        </tr>
        <tr>
            <th>Créée le:</th>
            <td><?= date('d/m/Y H:i:s', strtotime($session['created_at'])) ?></td>
        </tr>
        <?php if ($session['completed_at']): ?>
        <tr>
            <th>Complétée le:</th>
            <td><?= date('d/m/Y H:i:s', strtotime($session['completed_at'])) ?></td>
        </tr>        <?php endif; ?>
    </table>

    <!-- Section 1.5 : Informations Client -->
    <?php if ($session['customer_info']): ?>
    <h6 class="font-weight-bold mt-4">Informations Client</h6>
    <table class="table table-sm w-100">
        <?php 
        $customerInfo = json_decode($session['customer_info'], true);
        foreach ($customerInfo as $key => $value): 
        ?>
        <tr>
            <th><?= ucfirst(htmlspecialchars($key)) ?>:</th>
            <td><?= htmlspecialchars($value) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- Section 2 : Historique des Actions & Webhooks -->
    <h6 class="font-weight-bold mt-4">Historique des Actions</h6>
    <table class="table table-sm w-100">
        <thead>
            <tr>
                <th>Action</th>
                <th>Date</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['action']) ?></td>
                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                <td><?= htmlspecialchars($log['ip_address']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h6 class="font-weight-bold mt-4">Historique des Webhooks</h6>
    <table class="table table-sm w-100">
        <thead>
            <tr>
                <th>Statut</th>
                <th>Tentatives</th>
                <th>Dernière Tentative</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($webhooks as $webhook): ?>
            <tr>
                <td>
                    <span class="badge bg-<?= 
                        $webhook['status'] === 'sent' ? 'success' : 
                        ($webhook['status'] === 'pending' ? 'warning' : 'danger') 
                    ?>">
                        <?= htmlspecialchars($webhook['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($webhook['attempts']) ?></td>
                <td>
                    <?= $webhook['last_attempt_at'] ? 
                        date('d/m/Y H:i:s', strtotime($webhook['last_attempt_at'])) : 
                        'N/A' 
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
