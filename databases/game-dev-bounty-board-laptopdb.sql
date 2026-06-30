-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 09:32 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `game_dev_bounty_board` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `game_dev_bounty_board`;

CREATE TABLE IF NOT EXISTS `gdbb_achievements` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `badge_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_achievements` (`id`, `name`, `description`, `badge_image`) VALUES
(1, 'First Bounty Completed', 'Complete your first bounty', NULL),
(2, '5 Bounties Completed', 'Complete 5 bounties', NULL),
(3, 'Level 5 Reached', 'Reach level 5', NULL),
(4, 'Hard Bounty Completed', 'Complete a hard difficulty bounty', NULL);

CREATE TABLE IF NOT EXISTS `gdbb_bounties` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL,
  `xp_reward` int UNSIGNED NOT NULL,
  `deadline` varchar(50) DEFAULT NULL,
  `status` enum('open','claimed','in_review','completed','rejected') NOT NULL DEFAULT 'open',
  `created_by_user_id` int UNSIGNED NOT NULL,
  `claimed_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expire_date` varchar(50) DEFAULT NULL,
  `reward_color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gdbb_idx_bounties_status` (`status`),
  KEY `gdbb_idx_bounties_deadline` (`deadline`),
  KEY `gdbb_idx_bounties_created_by` (`created_by_user_id`),
  KEY `gdbb_idx_bounties_claimed_by` (`claimed_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_bounties` (`id`, `title`, `description`, `difficulty`, `xp_reward`, `deadline`, `status`, `created_by_user_id`, `claimed_by_user_id`, `created_at`, `updated_at`, `expire_date`, `reward_color`) VALUES
(1, 'Spoon', 'get a Spoon', 'easy', 25, NULL, 'open', 1, NULL, '2026-06-29 20:50:41', '2026-06-29 20:50:41', NULL, '#fd8181');

CREATE TABLE IF NOT EXISTS `gdbb_bounty_submissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `bounty_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `proof_text` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `review_comment` text,
  `approved` tinyint(1) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `proof_photo` varchar(255) DEFAULT NULL,
  `proof_description` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reviewed_by` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gdbb_idx_submissions_bounty` (`bounty_id`),
  KEY `gdbb_idx_submissions_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `gdbb_unlocked_outline_colors` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `color_code` varchar(20) NOT NULL,
  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_color` (`user_id`,`color_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_unlocked_outline_colors` (`id`, `user_id`, `color_code`, `unlocked_at`) VALUES
(1, 1, '#fd8181', '2026-06-29 20:50:49');

CREATE TABLE IF NOT EXISTS `gdbb_users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('player','gm','admin') NOT NULL DEFAULT 'player',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `xp` int UNSIGNED NOT NULL DEFAULT '0',
  `level` int UNSIGNED NOT NULL DEFAULT '1',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) DEFAULT NULL,
  `profile_outline_color` varchar(20) NOT NULL DEFAULT '#000000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `gdbb_idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_users` (`id`, `username`, `password_hash`, `role`, `is_admin`, `xp`, `level`, `is_blocked`, `created_at`, `profile_picture`, `profile_outline_color`) VALUES
(1, 'Sem', '$2y$10$yNjurWyBoEweW4.c3zIqjuphuNLnk0UCt64C.JtrWgdpu9F/a0IGW', 'admin', 1, 0, 1, 0, '2026-06-28 19:47:26', 'uploads/profile_pictures/user-1-1782759707.jpg', '#000000');

CREATE TABLE IF NOT EXISTS `gdbb_guilds` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `leader_user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `gdbb_idx_guilds_leader` (`leader_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_guilds` (`id`, `name`, `description`, `leader_user_id`, `created_at`) VALUES
(1, 'The Questers', 'A friendly guild for players who love completing bounties.', 1, '2026-06-30 09:32:00');

CREATE TABLE IF NOT EXISTS `gdbb_guild_members` (
  `guild_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('leader','member') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guild_id`,`user_id`),
  UNIQUE KEY `unique_user_guild_membership` (`user_id`),
  KEY `gdbb_idx_guild_members_guild` (`guild_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `gdbb_guild_members` (`guild_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 'leader', '2026-06-30 09:32:00');

CREATE TABLE IF NOT EXISTS `gdbb_user_achievements` (
  `user_id` int UNSIGNED NOT NULL,
  `achievement_id` int UNSIGNED NOT NULL,
  `earned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`achievement_id`),
  KEY `fk_user_achievements_achievement` (`achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;
