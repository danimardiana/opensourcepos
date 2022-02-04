-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: database
-- Generation Time: Feb 03, 2022 at 11:02 AM
-- Server version: 5.7.29
-- PHP Version: 7.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lamp`
--

-- --------------------------------------------------------

--
-- Table structure for table `ospos_item_prices`
--

CREATE TABLE `ospos_item_prices` (
  `item_price_id` int(10) NOT NULL,
  `multi_price_id` int(10) NOT NULL,
  `item_id` int(10) NOT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ospos_item_prices`
--
ALTER TABLE `ospos_item_prices`
  ADD PRIMARY KEY (`item_price_id`),
  ADD UNIQUE KEY `multi_price_id_2` (`multi_price_id`,`item_id`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `multi_price_id` (`multi_price_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ospos_item_prices`
--
ALTER TABLE `ospos_item_prices`
  MODIFY `item_price_id` int(10) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
