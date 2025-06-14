<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de Paiement - Wave</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .error-card {
            background-color: white;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .error-icon {
            color: #dc3545;
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .return-btn {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .return-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">×</div>
        <h1>Erreur de Paiement</h1>
        <?php if (isset($_GET['message'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>
        <p>Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer ou contacter le support si le problème persiste.</p>
        <a href="../../test/index.php" class="return-btn">Retour à l'accueil</a>
    </div>
</body>
</html>
