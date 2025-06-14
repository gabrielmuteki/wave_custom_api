<?php
session_start();
require_once __DIR__ . '/admin/auth.php';

// Rediriger vers le dashboard si authentifié, sinon vers login
if (isAuthenticated()) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: admin/login.php');
}
exit();
