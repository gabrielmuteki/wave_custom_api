<?php
session_start();
require_once 'auth.php';

// Rediriger vers le dashboard si authentifié, sinon vers login
if (isAuthenticated()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
