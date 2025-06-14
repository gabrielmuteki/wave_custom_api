<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/models/ApiKey.php';

$db = (new Database())->connect();
$apiKeyModel = new ApiKey($db);

// Récupérer toutes les clés API
$stmt = $db->query("
    SELECT * FROM api_keys 
    ORDER BY created_at DESC
");
$apiKeys = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gestion des Clés API</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newApiKeyModal">
            Nouvelle Clé API
        </button>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Marchand</th>
                            <th>Clé API</th>
                            <th>Webhook URL</th>
                            <th>Statut</th>
                            <th>Dernière Utilisation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                            <tr>
                                <td><?= htmlspecialchars($key['merchant_name']) ?></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($key['api_key']) ?>" readonly>
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this)">Copier</button>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($key['webhook_url']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $key['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $key['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= $key['last_used_at'] ? date('d/m/Y H:i', strtotime($key['last_used_at'])) : 'Jamais' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-<?= $key['is_active'] ? 'danger' : 'success' ?>"
                                        onclick="toggleApiKey(<?= (int)$key['id'] ?>, <?= $key['is_active'] ? 0 : 1 ?>)">
                                        <?= $key['is_active'] ? 'Désactiver' : 'Activer' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour nouvelle clé API -->
<div class="modal fade" id="newApiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Clé API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form onsubmit="createApiKey(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="merchant_name" class="form-label">Nom du Marchand</label>
                        <input type="text" class="form-control" id="merchant_name" name="merchant_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="webhook_url" class="form-label">URL Webhook (optionnel)</label>
                        <input type="url" class="form-control" id="webhook_url" name="webhook_url" value="http://localhost/epsiestartup/wave/webhook/test">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/api-keys.js"></script>