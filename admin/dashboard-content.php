<?php
require_once __DIR__ . '/../api/config/database.php';
$db = (new Database())->connect();

// Récupérer les statistiques
$stats = [
    'transactions' => $db->query("SELECT COUNT(*) FROM checkout_sessions")->fetchColumn(),
    'successful' => $db->query("SELECT COUNT(*) FROM checkout_sessions WHERE status = 'completed'")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM checkout_sessions WHERE status = 'pending'")->fetchColumn(),
    'amount' => $db->query("SELECT COALESCE(SUM(amount), 0) FROM checkout_sessions WHERE status = 'completed'")->fetchColumn(),
];

// Récupérer les dernières transactions
$recentTransactions = $db->query("
    SELECT id, amount, currency, status, created_at 
    FROM checkout_sessions 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Transactions Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['transactions']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Transactions Réussies</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['successful']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En Attente</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Volume Total (XOF)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['amount'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières Transactions</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Montant</th>
                                    <th>Devise</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['id']) ?></td>
                                    <td><?= number_format($transaction['amount']) ?></td>
                                    <td><?= htmlspecialchars($transaction['currency']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $transaction['status'] === 'completed' ? 'success' : 
                                            ($transaction['status'] === 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= htmlspecialchars($transaction['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
