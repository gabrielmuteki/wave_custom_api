-- Base de données : `epsiestartup`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `temp_password_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temp_password_expiry` datetime DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int NOT NULL,
  `api_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `merchant_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `webhook_url` text COLLATE utf8mb4_general_ci,
  `merchant_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `checkout_sessions` (
  `id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` int NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'XOF',
  `status` enum('pending','completed','failed','expired','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `client_reference` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aggregated_merchant_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `success_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `cancel_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `payment_url` text COLLATE utf8mb4_general_ci,
  `merchant_api_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `metadata` json DEFAULT NULL,
  `customer_info` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','read','replied') COLLATE utf8mb4_general_ci DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `downloads_counter` (
  `id` int NOT NULL,
  `software_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `download_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
