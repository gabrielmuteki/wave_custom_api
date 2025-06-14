<?php
require_once __DIR__ . '/../api/config/database.php';
session_start();

// Configuration de la gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactive l'affichage direct des erreurs
set_error_handler([Database::class, 'handleError']);

// Rediriger si déjà connecté
if (isset($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

Database::clearErrors(); // Réinitialiser les erreurs au début
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!empty($email)) {
        $db = (new Database())->connect();
        
        // Vérifier si l'utilisateur existe
        $stmt = $db->prepare("SELECT id, name FROM admin_users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {            // Envoyer un mot de passe temporaire
            $result = Database::sendTemporaryPassword($email, $user['name']);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } else {
            // Pour des raisons de sécurité, on affiche le même message que si l'email existait
            $success = 'Les instructions de réinitialisation ont été envoyées à votre adresse email.';
        }
    } else {
        $error = 'Veuillez saisir votre adresse email';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Administration Wave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
            height: 100vh;
        }
        .form-forgot {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-forgot">
        <form method="POST">
            <img src="../../public/images/logo-epsie.png" alt="EPSIE Logo" width="200" class="mb-4">
            <h1 class="h3 mb-3 fw-normal">Mot de passe oublié</h1>            <?php 
            $allErrors = Database::getErrors();
            if (!empty($allErrors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($allErrors as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br>
                    <a href="login.php" class="btn btn-primary btn-sm mt-2">Retour à la connexion</a>
                </div>
            <?php else: ?>
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                    <label for="email">Adresse email</label>
                </div>

                <button class="w-100 btn btn-lg btn-primary mt-3" type="submit">
                    Réinitialiser le mot de passe
                </button>
            <?php endif; ?>

            <p class="mt-3">
                <a href="login.php">Retour à la connexion</a>
            </p>
        </form>
    </main>
</body>
</html>
