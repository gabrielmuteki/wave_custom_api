<?php
class Database
{    //Développement
    private $host = 'localhost';
    private $db_name = 'epsiestartup';
    private $username = 'root';  // À modifier selon votre configuration
    private $password = 'root';      // À modifier selon votre configuration

    //Production
    

    private $connection;

    // Code d'accès pour l'inscription des administrateurs
    private static $ADMIN_ACCESS_CODE = 'ACCESS_WAVE_ADMIN_2025';

    // Configuration email
    private static $EMAIL_FROM = 'support@epsie-startup.com';
    private static $EMAIL_FROM_NAME = 'EPSIE Wave';

    // Buffer des erreurs
    private static $errors = [];

    public static function getAdminAccessCode()
    {
        return self::$ADMIN_ACCESS_CODE;
    }
    public static function getEmailConfig()
    {
        return [
            'from' => self::$EMAIL_FROM,
            'from_name' => self::$EMAIL_FROM_NAME
        ];
    }

    public static function sendTemporaryPassword(
        $email,
        $name,
        $onRegistration = false,
        $temp_password = null,
        $temp_password_hash = null,
        $expiry = null
    ) {
        $db = (new self())->connect();

        // Vérifier si un mot de passe temporaire valide existe déjà
        $stmt = $db->prepare("SELECT temp_password_expiry FROM admin_users WHERE email = ? AND temp_password_expiry > NOW()");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && !$onRegistration) {
            $timeLeft = strtotime($existing['temp_password_expiry']) - time();
            $minutesLeft = ceil($timeLeft / 60);
            return [
                'success' => false,
                'message' => "Un mot de passe temporaire est déjà actif. Veuillez patienter encore {$minutesLeft} minutes ou utiliser le mot de passe déjà envoyé."
            ];
        }

        // Générer nouveau mot de passe temporaire
        if (!$onRegistration) {
            $temp_password = bin2hex(random_bytes(8));
            $temp_password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
            $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }

        // Mettre à jour l'utilisateur
        $stmt = $db->prepare("
            UPDATE admin_users 
            SET temp_password_hash = ?, 
                temp_password_expiry = ?
            WHERE email = ?
        ");

        if (!$stmt->execute([$temp_password_hash, $expiry, $email])) {
            return [
                'success' => false,
                'message' => "Erreur lors de la mise à jour du mot de passe temporaire."
            ];
        }

        // Envoyer l'email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= 'From: ' . self::$EMAIL_FROM_NAME . ' <' . self::$EMAIL_FROM . '>' . "\r\n";

        $subject = "Votre mot de passe temporaire Wave";
        $message = self::getTemporaryPasswordEmailTemplate($name, $email, $temp_password);

        if (!mail($email, $subject, $message, $headers)) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'envoi de l'email."
            ];
        }

        return [
            'success' => true,
            'message' => "Un nouveau mot de passe temporaire a été envoyé à votre adresse email."
        ];
    }

    private static function getTemporaryPasswordEmailTemplate($name, $email, $temp_password)
    {
        return <<<EOF
<!DOCTYPE html>
<html lang='fr'>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <img src='https://epsie-startup.com/public/images/logo-epsie.png' alt='EPSIE Logo' style='max-width: 200px;'>
            </div>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px;'>
                <h2 style='color: #0D2B4F; margin-bottom: 20px;'>Bonjour {$name},</h2>
                <p>Voici votre nouveau mot de passe temporaire pour l'administration Wave.</p>
                <div style='background: #fff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Mot de passe temporaire:</strong> {$temp_password}</p>
                </div>
                <p style='color: #dc3545;'><strong>Important:</strong> Ce mot de passe est valable pendant 30 minutes.</p>
                <p><a href='https://epsie-startup.com/wave/admin/login.php' style='background: #0D2B4F; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Se connecter</a></p>
            </div>
            <div style='text-align: center; margin-top: 20px; font-size: 12px; color: #6c757d;'>
                <p>EPSIE - Solutions Informatiques</p>
                <p>Email: support@epsie-startup.com</p>
            </div>
        </div>
    </body>
</html>
EOF;
    }    public function connect()
    {
        $this->connection = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->connection = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } catch (PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->connection;
    }

    // Gestionnaire d'erreurs personnalisé
    public static function handleError($errno, $errstr) {
        self::addError($errstr);
        return true;
    }

    public static function addError($error) {
        self::$errors[] = $error;
    }

    public static function getErrors() {
        return self::$errors;
    }

    public static function clearErrors() {
        self::$errors = [];
    }
}
