<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wave Payment Simulation</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 20px; 
            background-color: #f8f9fa;
        }
        .payment-card { 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 30px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .amount { 
            font-size: 32px; 
            font-weight: bold; 
            color: #333; 
            text-align: center; 
            margin: 20px 0; 
        }
        .currency { 
            color: #666; 
            font-size: 20px; 
        }
        button { 
            width: 100%; 
            padding: 15px; 
            margin: 10px 0; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .pay-btn { 
            background: #28a745; 
            color: white; 
        }
        .pay-btn:hover {
            background: #218838;
        }
        .cancel-btn { 
            background: #dc3545; 
            color: white; 
        }
        .cancel-btn:hover {
            background: #c82333;
        }
        .info { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
            font-size: 14px;
            color: #666;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .customer-info {
            margin: 20px 0;
            text-align: left;
        }
        .customer-info strong {
            display: inline-block;
            min-width: 100px;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <h2>Simulation de Paiement Wave</h2>
          <div class="info">
            <strong>Référence:</strong> <?= htmlspecialchars($session['client_reference'] ?? 'N/A') ?><br>
            <strong>ID Session:</strong> <?= htmlspecialchars($session['id']) ?>
            <?php if (isset($session['description'])): ?>
            <br><strong>Description:</strong> <?= htmlspecialchars($session['description']) ?>
            <?php endif; ?>
        </div>
        
        <div class="amount">
            <?= number_format($session['amount'], 0, ',', ' ') ?>
            <span class="currency"><?= htmlspecialchars($session['currency']) ?></span>
        </div>

        <?php if (isset($session['customer']) && isset($session['customer']['phone'])): ?>
        <div class="customer-info">
            <strong>Téléphone:</strong> <?= htmlspecialchars($session['customer']['phone']) ?><br>
            <?php if (isset($session['customer']['name'])): ?>
            <strong>Nom:</strong> <?= htmlspecialchars($session['customer']['name']) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="../../wave/pay/process">
            <input type="hidden" name="session_id" value="<?= htmlspecialchars($session['id']) ?>">
            
            <button type="submit" name="action" value="pay" class="pay-btn">
                Confirmer le Paiement
            </button>
            
            <button type="submit" name="action" value="cancel" class="cancel-btn">
                Annuler
            </button>
        </form>
        
        <div class="info" style="text-align: center; margin-top: 20px;">
            ⚠️ Ceci est une simulation. Aucun vrai paiement ne sera effectué.
        </div>
    </div>
</body>
</html>
