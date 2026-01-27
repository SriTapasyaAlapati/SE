-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 27, 2026 at 06:14 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user`
--

-- --------------------------------------------------------

--
-- Table structure for table `bill`
--

DROP TABLE IF EXISTS `bill`;
CREATE TABLE IF NOT EXISTS `bill` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scno` varchar(9) NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT '0.00',
  `present_reading` decimal(10,2) NOT NULL,
  `noofunits` decimal(10,2) NOT NULL,
  `grp` varchar(1) NOT NULL,
  `current_month_bill` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `duepayment` decimal(10,2) NOT NULL,
  `late_fine` decimal(10,2) DEFAULT '0.00',
  `fine_applied_date` datetime DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `bill_datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bill` (`scno`,`month`,`year`),
  KEY `idx_scno` (`scno`),
  KEY `idx_status` (`status`),
  KEY `idx_month_year` (`month`,`year`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bill`
--

INSERT INTO `bill` (`id`, `scno`, `month`, `year`, `previous_reading`, `present_reading`, `noofunits`, `grp`, `current_month_bill`, `total_amount`, `duepayment`, `late_fine`, `fine_applied_date`, `payment_date`, `status`, `bill_datetime`) VALUES
(32, '374835269', 1, 2026, 0.00, 222.00, 222.00, 'I', 1143.00, 1143.00, 1143.00, 150.00, '2026-01-27 23:43:26', '2026-01-27 23:43:31', 'Paid', '2026-01-27 23:43:09'),
(4, '098470138', 12, 2025, 0.00, 100.00, 200.00, 'D', 0.00, 200.00, 200.00, 200.00, NULL, NULL, 'Unpaid', '2025-12-10 00:00:00'),
(31, '560561454', 1, 2026, 0.00, 200.00, 200.00, 'C', 800.00, 800.00, 800.00, 150.00, '2026-01-27 23:36:41', '2026-01-27 23:36:46', 'Paid', '2026-01-27 23:35:37'),
(3, '098470138', 11, 2025, 0.00, 0.00, 100.00, 'D', 0.00, 200.00, 200.00, 200.00, NULL, NULL, 'Paid', '2025-11-01 00:00:00'),
(30, '098470138', 1, 2026, 100.00, 101.00, 1.00, 'D', 1.50, 351.50, 351.50, 150.00, '2026-01-27 22:12:03', '2026-01-27 22:12:12', 'Paid', '2026-01-27 22:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `scno` varchar(9) NOT NULL,
  `uscno` varchar(9) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` text,
  `area` varchar(255) DEFAULT NULL,
  `grp` varchar(1) DEFAULT NULL,
  `phno` varchar(10) NOT NULL,
  PRIMARY KEY (`scno`),
  UNIQUE KEY `uscno` (`uscno`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`scno`, `uscno`, `name`, `address`, `area`, `grp`, `phno`) VALUES
('374835269', '374886585', 'Tap', 'hyd', 'hyd', 'I', '7890654321'),
('560561454', '560583885', 'Varnam', 'hyd', 'hyd', 'C', '8987654321'),
('732028821', '732021979', 'Radhika', 'hyd', 'hyd', 'C', '6789012345'),
('098470138', '098459873', 'Tapasya', 'hyd', 'hyd', 'D', '9876543210');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
