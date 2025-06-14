<?php
require_once 'auth.php';
requireAuth();

$user = getCurrentUser();

// Définir une constante pour indiquer que les pages sont incluses dans le dashboard
define('INCLUDED_IN_DASHBOARD', true);

// Déterminer quelle page charger
$page = $_GET['page'] ?? 'dashboard';

?>
<!DOCTYPE html>
<html lang="fr">

<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Wave Simulation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Scripts de l'application -->
    <script src="js/dashboard.js" defer></script>
    <script src="js/transactions.js" defer></script>
    <script src="js/webhooks.js" defer></script>
    <script src="js/api-keys.js" defer></script>
    <style>
        body {
            padding-top: 48px;
            /* Hauteur de la navbar */
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        main {
            padding-top: 1.5rem !important;
            margin-left: 16.66666667% !important;
            /* équivalent à col-md-2 */
        }

        @media (min-width: 992px) {
            main {
                margin-left: 16.66666667% !important;
                /* équivalent à col-lg-2 */
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">Wave Simulation</a>
        <div class="d-flex align-items-center px-3">
            <a href="../docs.php" target="_blank" class="btn btn-outline-light btn-sm me-4">
                <i class="fas fa-book me-1"></i>Documentation
            </a>
            <div class="text-white">
                Bienvenue, <?= htmlspecialchars($user['name']) ?> |
                <a href="logout.php" class="text-white text-decoration-none">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" onclick="showDashboard(this)">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showApiKeys(this)">
                                <i class="fas fa-key"></i> Clés API
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showTransactions(this)">
                                <i class="fas fa-exchange-alt"></i> Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showWebhooks(this)">
                                <i class="fas fa-bell"></i> Webhooks
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 pt-4">
                <div id="content">
                    <!-- Le contenu sera chargé dynamiquement ici -->
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card border-0 shadow-lg welcome-card">
                                <div class="card-header bg-dark text-white py-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h2 class="mb-0"><i class="fas fa-hand-wave me-2"></i>Bienvenue sur EPSIE</h2>
                                        <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($user['name']) ?></span>
                                    </div>
                                </div>

                                <div class="card-body p-5">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h3 class="fw-bold text-gradient text-dark">Commencez votre voyage</h3>
                                            <p class="lead">Nous sommes ravis de vous accueillir dans notre écosystème.</p>
                                            <p>Explorez nos services et découvrez comment nous pouvons vous aider à atteindre vos objectifs.</p>

                                            <div class="d-grid gap-3 mt-4">
                                                <a href="#" class="btn btn-dark btn-lg rounded-pill" onclick="showDashboard(this)">
                                                    <i class="fas fa-rocket me-2"></i>Démarrer
                                                </a>
                                                <a href="../../test/" class="btn btn-success btn-lg rounded-pill" target="_blank">
                                                    <i class="fas fa-flask me-2"></i>Tester
                                                </a>
                                                <a class="btn btn-outline-secondary rounded-pill" href="../docs.php">
                                                    <i class="fas fa-book me-2"></i>Documentation
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-md-6 text-center d-none d-md-block">
                                            <img src="../../public/images/logo-epsie.png"
                                                alt="Logo EPSIE"
                                                class="img-fluid rounded-3 shadow-sm">
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-light py-3">
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><i class="fas fa-info-circle me-1"></i> Dernière connexion : <?= htmlspecialchars($user['last_login'] ?? 'Vous effectuez votre toute première connexion.') ?>
                                        </span>
                                        <span>Prêt à commencer ?</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>