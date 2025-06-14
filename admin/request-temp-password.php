<?php
require_once __DIR__ . '/../api/config/database.php';
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!empty($email)) {
        $db = (new Database())->connect();
        
        // Vérifier si l'utilisateur existe
        $stmt = $db->prepare("SELECT id, name FROM admin_users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Envoyer le mot de passe temporaire
            $result = Database::sendTemporaryPassword($email, $user['name']);
            $messageType = $result['success'] ? 'success' : 'danger';
            $message = $result['message'];
        } else {
            $messageType = 'danger';
            $message = 'Aucun compte actif n\'a été trouvé avec cet email.';
        }
    } else {
        $messageType = 'danger';
        $message = 'Veuillez fournir une adresse email.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de mot de passe temporaire - Administration Wave</title>
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
        .form-temp-password {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-temp-password">
        <form method="POST">
            <img src="../../public/images/logo-epsie.png" alt="EPSIE Logo" width="200" class="mb-4">
            <h1 class="h3 mb-3 fw-normal">Mot de passe temporaire</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" style="max-height: 120px; overflow-y: auto; word-break: break-word; white-space: pre-line;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                <label for="email">Adresse email</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary mt-3" type="submit">
                Demander un mot de passe temporaire
            </button>

            <p class="mt-3">
                <a href="login.php">Retour à la connexion</a>
            </p>
        </form>
    </main>
</body>
</html>
