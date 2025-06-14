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
$temp_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $access_code = htmlspecialchars(trim($_POST['access_code']), ENT_QUOTES, 'UTF-8');

    if (!empty($name) && !empty($email) && !empty($access_code)) {
        // Vérifier le code d'accès
        if ($access_code !== Database::getAdminAccessCode()) {
            $error = 'Code d\'accès invalide';
        } else {
            $db = (new Database())->connect();
            
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé';
            } else {                // Créer l'utilisateur avec un mot de passe temporaire initial
                $temp_password = bin2hex(random_bytes(8));
                $temp_password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                // Créer l'utilisateur
                $stmt = $db->prepare("
                    INSERT INTO admin_users (
                        email, 
                        password_hash, 
                        name, 
                        temp_password_hash, 
                        temp_password_expiry,
                        first_login
                    ) VALUES (?, ?, ?, ?, ?, 1)
                ");                if ($stmt->execute([$email, $temp_password_hash, $name, $temp_password_hash, $expiry])) {
                    // Envoyer le mot de passe temporaire
                    $result = Database::sendTemporaryPassword($email, $name, true, $temp_password, $temp_password_hash, $expiry);
                    if ($result['success']) {
                        $success = 'Compte créé avec succès. ' . $result['message'];
                    } else {
                        // $error = 'Le compte a été créé mais ' . $result['message'] . ' Contactez l\'administrateur. '.$temp_password;
                        $error = 'Le compte a été créé mais ' . $result['message'] . ' Contactez l\'administrateur. ';
                    }
                } else {
                    $error = 'Erreur lors de la création du compte';
                }
            }
        }
    } else {
        $error = 'Tous les champs sont requis';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Administration Wave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        .form-signup {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signup">
        <form method="POST">
            <img src="../../public/images/logo-epsie.png" alt="EPSIE Logo" width="200" class="mb-4">
            <h1 class="h3 mb-3 fw-normal">Inscription Administration Wave</h1>            <?php 
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
                    <a href="login.php" class="btn btn-primary btn-sm mt-2">Aller à la page de connexion</a>
                </div>
            <?php else: ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Votre nom" required>
                    <label for="name">Nom complet</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                    <label for="email">Adresse email</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="access_code" name="access_code" placeholder="Code d'accès" required>
                    <label for="access_code">Code d'accès</label>
                </div>

                <button class="w-100 btn btn-lg btn-primary" type="submit">S'inscrire</button>
            <?php endif; ?>

            <p class="mt-3">
                <a href="login.php">Retour à la connexion</a>
            </p>
        </form>
    </main>
</body>
</html>
