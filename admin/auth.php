<?php
session_start();

// Vérifier si l'utilisateur est connecté
function isAuthenticated() {
    return isset($_SESSION['admin_user']);
}

// Rediriger vers la page de connexion si non authentifié
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

// Récupérer les informations de l'utilisateur connecté
function getCurrentUser() {
    return $_SESSION['admin_user'] ?? null;
}

// Déconnexion
function logout() {
    unset($_SESSION['admin_user']);
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
