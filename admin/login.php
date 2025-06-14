<?php
require_once __DIR__ . '/../api/config/database.php';
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['admin_user'])) {
    header('Location: ?page=wave-admin&action=dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $db = (new Database())->connect();        $stmt = $db->prepare("SELECT id, email, name, password_hash, last_login, temp_password_hash, temp_password_expiry, first_login FROM admin_users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $isValidTempPassword = false;
        if ($user && $user['temp_password_hash'] && strtotime($user['temp_password_expiry']) > time()) {
            $isValidTempPassword = password_verify($password, $user['temp_password_hash']);
        }

        if ($user && ($isValidTempPassword || password_verify($password, $user['password_hash']))) {
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'last_login' => $user['last_login'],
                'first_login' => $user['first_login'],
                'using_temp_password' => $isValidTempPassword
            ];

            // Mettre à jour last_login
            $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect';
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
    <title>Connexion - Administration Wave</title>
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
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="email"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form method="POST">
            <img src="../../public/images/logo-epsie.png" alt="EPSIE Logo" width="200" class="mb-4">
            <h1 class="h3 mb-3 fw-normal">Administration Wave</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                <label for="email">Adresse email</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">Se connecter</button>
            <button type="button" class="w-100 btn btn-secondary mb-3" onclick="window.location.href='request-temp-password.php'">
                Demander un mot de passe temporaire
            </button>
            <p class="mt-3">
                <a href="register.php">S'inscrire</a> | 
                <a href="forgot-password.php">Mot de passe oublié ?</a>
            </p>
        </form>
    </main>
</body>
</html>
