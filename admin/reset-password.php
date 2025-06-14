<?php
require_once __DIR__ . '/../api/config/database.php';
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$db = (new Database())->connect();

// Vérifier si le token est valide
$stmt = $db->prepare("
    SELECT id, email 
    FROM admin_users 
    WHERE reset_token = ? 
    AND reset_token_expires > NOW() 
    AND is_active = 1
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Ce lien de réinitialisation est invalide ou a expiré.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        // Mettre à jour le mot de passe
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            UPDATE admin_users 
            SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = ?
        ");

        if ($stmt->execute([$password_hash, $user['id']])) {
            $success = 'Votre mot de passe a été mis à jour avec succès.';
        } else {
            $error = 'Une erreur est survenue lors de la mise à jour du mot de passe';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - Administration Wave</title>
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
        .form-reset {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-reset">
        <form method="POST">
            <img src="../../public/images/logo-epsie.png" alt="EPSIE Logo" width="200" class="mb-4">
            <h1 class="h3 mb-3 fw-normal">Réinitialisation du mot de passe</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                    <?php if (strpos($error, 'invalide ou a expiré') !== false): ?>
                        <br>
                        <a href="forgot-password.php" class="btn btn-primary btn-sm mt-2">Demander un nouveau lien</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br>
                    <a href="login.php" class="btn btn-primary btn-sm mt-2">Se connecter</a>
                </div>
            <?php elseif (!$error || strpos($error, 'invalide ou a expiré') === false): ?>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nouveau mot de passe" required>
                    <label for="password">Nouveau mot de passe</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmez le mot de passe" required>
                    <label for="confirm_password">Confirmez le mot de passe</label>
                </div>

                <button class="w-100 btn btn-lg btn-primary" type="submit">
                    Mettre à jour le mot de passe
                </button>
            <?php endif; ?>

            <p class="mt-3">
                <a href="login.php">Retour à la connexion</a>
            </p>
        </form>
    </main>
</body>
</html>
