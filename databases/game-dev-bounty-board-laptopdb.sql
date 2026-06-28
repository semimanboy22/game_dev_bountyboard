-- phpMyAdmin SQL Dump
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 04:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@OLD_COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `user_repo_opdracht` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `user_repo_opdracht`;

CREATE TABLE `bb_badges` (
  `id` int(11) NOT NULL,
  `name_badge` varchar(45) DEFAULT NULL,
  `image_badge` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bb_bounty` (
  `id` int(11) NOT NULL,
  `bounty_name` varchar(45) NOT NULL,
  `bounty_xp` int(11) NOT NULL,
  `bounty_description` varchar(255) DEFAULT NULL,
  `cosmetic_id` int(11) DEFAULT NULL,
  `BB_users_id` int(11) DEFAULT NULL,
  `bounty_disabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `bb_bounty` (`id`, `bounty_name`, `bounty_xp`, `bounty_description`, `cosmetic_id`, `BB_users_id`, `bounty_disabled`) VALUES
(21, '1', 1, '1', NULL, 30, 0),
(22, 'stijn is dik', 69420, 'stijn is een bolle dikzak', NULL, 30, 0),
(23, '1', 1, '1', NULL, 30, 1),
(24, '11', 11, '11', NULL, 30, 1),
(25, '111', 111, '111', NULL, 30, 1),
(26, '111', 111, '111', NULL, 30, 1);

CREATE TABLE `bb_bountyproof` (
  `id` int(11) NOT NULL,
  `proof_photo` varchar(255) NOT NULL,
  `proof_description` varchar(255) NOT NULL,
  `BB_bounty_id` int(11) NOT NULL,
  `BB_users_id` int(11) NOT NULL,
  `BB_proof_status` varchar(255) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `bb_bountyproof` (`id`, `proof_photo`, `proof_description`, `BB_bounty_id`, `BB_users_id`, `BB_proof_status`) VALUES
(6, 'w', 'bad bad', 21, 34, 'rejected'),
(10, '1', 'this is good good proof', 22, 36, 'approved'),
(11, 'ye', 'dikzak', 23, 31, 'approved');

CREATE TABLE `bb_cosmetic` (
  `id` int(11) NOT NULL,
  `name_cosmetic` varchar(100) NOT NULL,
  `image_cosmetic` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bb_guild` (
  `id` int(11) NOT NULL,
  `guild_name` varchar(45) NOT NULL,
  `guild_capacity` int(11) NOT NULL DEFAULT 50,
  `guild_description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bb_guildusers` (
  `id` int(11) NOT NULL,
  `BB_guild_id` int(11) NOT NULL,
  `BB_users_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bb_users` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `XP` int(11) NOT NULL DEFAULT 0,
  `isAdmin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `bb_users` (`id`, `name`, `password`, `XP`, `isAdmin`) VALUES
(30, '3', '$2y$10$sdFeS6/KB.YNdcPsGK1axeXkJijmqnDqzHNxwAnkFw.XUSVnqNJLy', 0, 1),
(31, 'sem', '$2y$10$0XlWOqGW1csTl4JGG1zkYOiOxwIyWmFsJ6w.G7UBQicAgk8G39eM6', 1, 0),
(32, '123', '$2y$10$R4dldh/OpYWa1Tr7N0SBS.q2P0d2UzLPnS32bKsYhXxaG8QD1oy9q', 0, 0),
(33, '3', '$2y$10$K.cEBx2f.Da2NBeHEGkGau16T3sy0wd92tB3zSeAZxUHlhFaE2V1S', 0, 0),
(34, 'mes', '$2y$10$8Ko9.A1qgY8QjEv3CJtvsOIszFDZs9qUbreLW9aq8wSmmBdkB.dzm', 0, 0),
(36, '1', '$2y$10$qG490TiGB6OSyM2pyjubfuvtZDuY39biBTkkI1qoytoJKzOAPXZSC', 69420, 0);

CREATE TABLE `bb_users_has_bb_badges` (
  `BB_users_id` int(11) NOT NULL,
  `BB_badges_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bb_users_has_cosmetic` (
  `BB_users_id` int(11) NOT NULL,
  `cosmetic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `bb_badges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_bounty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_BB_bounty_cosmetic1_idx` (`cosmetic_id`),
  ADD KEY `fk_BB_bounty_BB_users1_idx` (`BB_users_id`);

ALTER TABLE `bb_bountyproof`
  ADD PRIMARY KEY (`id`,`BB_bounty_id`),
  ADD KEY `fk_BB_bountyProof_BB_bounty1_idx` (`BB_bounty_id`),
  ADD KEY `fk_BB_bountyProof_BB_users1_idx` (`BB_users_id`);

ALTER TABLE `bb_cosmetic`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_guild`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_guildusers`
  ADD PRIMARY KEY (`id`,`BB_guild_id`,`BB_users_id`),
  ADD KEY `fk_BB_guildUsers_BB_guild_idx` (`BB_guild_id`),
  ADD KEY `fk_BB_guildUsers_BB_users1_idx` (`BB_users_id`);

ALTER TABLE `bb_users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bb_users_has_bb_badges`
  ADD PRIMARY KEY (`BB_users_id`,`BB_badges_id`),
  ADD KEY `fk_BB_users_has_BB_badges_BB_badges1_idx` (`BB_badges_id`),
  ADD KEY `fk_BB_users_has_BB_badges_BB_users1_idx` (`BB_users_id`);

ALTER TABLE `bb_users_has_cosmetic`
  ADD PRIMARY KEY (`BB_users_id`,`cosmetic_id`),
  ADD KEY `fk_BB_users_has_cosmetic_cosmetic1_idx` (`cosmetic_id`),
  ADD KEY `fk_BB_users_has_cosmetic_BB_users1_idx` (`BB_users_id`);

ALTER TABLE `bb_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_bounty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

ALTER TABLE `bb_bountyproof`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `bb_cosmetic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_guild`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_guildusers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bb_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

ALTER TABLE `bb_users_has_bb_badges`
  MODIFY `BB_users_id` int(11) NOT NULL, MODIFY `BB_badges_id` int(11) NOT NULL;

ALTER TABLE `bb_users_has_cosmetic`
  MODIFY `BB_users_id` int(11) NOT NULL, MODIFY `cosmetic_id` int(11) NOT NULL;

ALTER TABLE `bb_bounty`
  ADD CONSTRAINT `fk_BB_bounty_BB_users1` FOREIGN KEY (`BB_users_id`) REFERENCES `bb_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_BB_bounty_cosmetic1` FOREIGN KEY (`cosmetic_id`) REFERENCES `bb_cosmetic` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `bb_bountyproof`
  ADD CONSTRAINT `fk_BB_bountyProof_BB_bounty1` FOREIGN KEY (`BB_bounty_id`) REFERENCES `bb_bounty` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_BB_bountyProof_BB_users1` FOREIGN KEY (`BB_users_id`) REFERENCES `bb_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `bb_guildusers`
  ADD CONSTRAINT `fk_BB_guildUsers_BB_guild` FOREIGN KEY (`BB_guild_id`) REFERENCES `bb_guild` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_BB_guildUsers_BB_users1` FOREIGN KEY (`BB_users_id`) REFERENCES `bb_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `bb_users_has_bb_badges`
  ADD CONSTRAINT `fk_BB_users_has_BB_badges_BB_badges1` FOREIGN KEY (`BB_badges_id`) REFERENCES `bb_badges` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_BB_users_has_BB_badges_BB_users1` FOREIGN KEY (`BB_users_id`) REFERENCES `bb_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `bb_users_has_cosmetic`
  ADD CONSTRAINT `fk_BB_users_has_cosmetic_BB_users1` FOREIGN KEY (`BB_users_id`) REFERENCES `bb_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_BB_users_has_cosmetic_cosmetic1` FOREIGN KEY (`cosmetic_id`) REFERENCES `bb_cosmetic` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
