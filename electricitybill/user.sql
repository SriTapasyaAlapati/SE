-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 22, 2026 at 04:20 PM
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
  `status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `bill_datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bill` (`scno`,`month`,`year`),
  KEY `idx_scno` (`scno`),
  KEY `idx_status` (`status`),
  KEY `idx_month_year` (`month`,`year`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bill`
--

INSERT INTO `bill` (`id`, `scno`, `month`, `year`, `previous_reading`, `present_reading`, `noofunits`, `grp`, `current_month_bill`, `total_amount`, `duepayment`, `status`, `bill_datetime`) VALUES
(2, '112633171', 1, 2026, 0.00, 450.00, 450.00, 'D', 1150.00, 1150.00, 1150.00, 'Paid', '2026-01-21 22:10:01'),
(11, '018264671', 1, 2026, 0.00, 50.00, 50.00, 'C', 200.00, 200.00, 200.00, 'Paid', '2026-01-22 21:48:38'),
(10, '803588643', 1, 2026, 0.00, 455.00, 455.00, 'C', 1770.00, 1770.00, 1770.00, 'Paid', '2026-01-21 23:42:26'),
(7, '463114896', 11, 2025, 240.00, 380.00, 140.00, 'C', 350.00, 702.00, 702.00, 'Paid', '2025-11-15 10:00:00'),
(8, '463114896', 1, 2026, 380.00, 500.00, 120.00, 'I', 550.00, 1322.20, 1322.20, 'Paid', '2026-01-21 22:22:04');

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
  PRIMARY KEY (`scno`),
  UNIQUE KEY `uscno` (`uscno`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`scno`, `uscno`, `name`, `address`, `area`, `grp`) VALUES
('112633171', '112661989', 'Tapasya', 'Kalyan nagar phase 1', 'Vengal Roa Nagar', 'D'),
('803588643', '780351307', 'Vasavi', 'sr nagar,hyd', 'Vengal Roa Nagar', 'C'),
('463114896', '463121558', 'Varnam', 'Hyd', 'Vengal Roa Nagar', 'I'),
('526032716', '526071476', 'Mary', 'hyd', 'hyd', 'D'),
('972269275', '972249922', 'Ravi', 'hyd', 'hyd', 'D'),
('443554228', '443517857', 'John', 'hyd', 'hyd', 'D'),
('018264671', '018286232', 'Ram', 'hyd', 'hyd', 'C');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
