-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2019 at 07:21 AM
-- Server version: 5.7.17
-- PHP Version: 7.1.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cron_job`
--

-- --------------------------------------------------------

--
-- Table structure for table `ftpfiles`
--

CREATE TABLE `ftpfiles` (
  `id` int(11) NOT NULL,
  `path` varchar(555) NOT NULL,
  `type` varchar(11) NOT NULL DEFAULT 'F' COMMENT 'D=Directory, F=File',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0 = Logged, 1 = Ready, 2 = Transferred',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ftpfiles`
--

INSERT INTO `ftpfiles` (`id`, `path`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, '/index.php', 'F', 2, '2019-07-21 21:24:37', '2019-07-21 21:24:37'),
(2, '/Bollywood', 'D', 2, '2019-07-21 21:24:37', '2019-07-21 21:24:37'),
(3, '/Hollywood', 'D', 2, '2019-07-21 21:24:37', '2019-07-21 21:24:37'),
(4, '/Lollywood', 'D', 2, '2019-07-21 21:24:37', '2019-07-21 21:24:37'),
(5, '/Bollywood/index.php', 'F', 2, '2019-07-21 21:24:41', '2019-07-21 21:24:41'),
(6, '/Hollywood/index.php', 'F', 2, '2019-07-21 21:24:41', '2019-07-21 21:24:41'),
(7, '/Lollywood/index.php', 'F', 2, '2019-07-21 21:24:41', '2019-07-21 21:24:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ftpfiles`
--
ALTER TABLE `ftpfiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ftpfiles`
--
ALTER TABLE `ftpfiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
