-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 22, 2025 at 04:43 PM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `disposals`
--

CREATE TABLE `disposals` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `recorded_by` varchar(100) NOT NULL,
  `recorded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `disposals`
--

INSERT INTO `disposals` (`id`, `medicine_name`, `lot_number`, `quantity`, `reason`, `recorded_by`, `recorded_at`) VALUES
(1, 'Paracetamol', 'LOT001-A', 3, 'Expired', 'Sint Wai Toe', '2025-08-10 00:39:39'),
(2, 'ငြိမ်ဆေး', '1343546', 56, 'Abnormal', 'Sint Wai Toe', '2025-08-10 00:58:00'),
(3, 'ငြိမ်ဆေး', '1343546', 5, 'Expired', 'Sint Wai Toe', '2025-08-10 00:59:21'),
(4, '96 ပါးရောဂါပျောက်ဆေး', 'LOT001-A1', 5, 'Expired', 'Sint Wai Toe', '2025-08-13 19:51:27'),
(5, 'Cough Syrup', 'LOT-COUGH-005', 2, 'Other', 'Sint Wai Toe', '2025-08-15 21:20:36'),
(6, 'Amoxicillin', 'LOT-AMOX-002', 5, 'Expired', 'Sint Wai Toe', '2025-08-21 16:49:41');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt`
--

CREATE TABLE `goods_receipt` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `receipt_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `goods_receipt`
--

INSERT INTO `goods_receipt` (`id`, `medicine_id`, `lot_number`, `expiry_date`, `quantity`, `purchase_price`, `receipt_date`) VALUES
(1, 1, '243657', '2025-08-08', 5, '6000.00', '2025-08-09 20:57:52'),
(2, 5, '1343546', '2025-08-08', 66, '5000.00', '2025-08-09 21:09:10'),
(3, 5, '1343546', '2025-08-08', 1, '5000.00', '2025-08-09 21:18:55'),
(4, 6, 'Lot 24056', '2026-01-10', 50, '10000.00', '2025-08-10 14:45:28'),
(5, 3, 'LOT002-C', '2025-09-10', 12, '4000.00', '2025-08-13 19:54:15'),
(6, 5, 'LOT002-C', '2025-09-20', 12, '4000.00', '2025-08-13 20:05:30'),
(7, 5, 'LOT002-C', '2025-09-20', 15, '4000.00', '2025-08-13 22:29:30'),
(8, 3, 'LOT003-C', '2025-09-26', 3, '5000.00', '2025-08-19 22:29:19'),
(9, 3, 'LOT003-C', '2025-08-20', 5, '5000.00', '2025-08-20 02:52:27'),
(10, 3, 'LOT003-C', '2025-08-20', 5, '5000.00', '2025-08-20 03:26:00'),
(11, 1, 'LOT002-C', '2025-09-18', 50, '5000.00', '2025-08-21 16:45:04'),
(12, 1, 'LOT002-C', '2025-10-21', 50, '5000.00', '2025-08-21 16:55:45');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `expiry_date` date NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `received_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `sell_price` decimal(10,2) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `barcode`, `dosage_form`, `category`, `quantity`, `unit`, `lot_number`, `mfg_date`, `exp_date`, `cost_price`, `sell_price`, `reorder_level`) VALUES
(1, 'Paracetamol', '1234567890123', 'Tablet', 'Fever Medicine', 205, 'box', 'LOT001-A', '2023-01-01', '2025-12-31', '150.00', '300.00', 20),
(2, 'Amoxicillin', '9876543210123', 'Capsule', 'Antibiotic', 23, 'card', 'LOT002-B', '2025-08-08', '2025-08-22', '200.00', '450.00', 10),
(3, 'Vitamin C Syrup', 'MED1755619195205', 'Syrup', 'Vitamins', 30, 'bottle', 'LOT003-C', '2025-08-20', '2023-10-01', '5000.00', '6000.00', 5),
(5, '96 ပါးရောဂါပျောက်ဆေး', '124354767856', 'Tablet', 'sleep', 6, 'လုံး', 'LOT001-A1', '2025-08-11', '2025-11-22', '10000.00', '15000.00', 1),
(6, 'Vitamin C', '45678910', 'Tablet', 'Energy', 99, 'box', 'Lot 24056', '2025-08-10', '2026-01-30', '10000.00', '12000.00', 10),
(7, 'Paracetamol', '1234567890123', 'Tablet', 'Fever Medicine', 10, 'box', 'LOT-PARA-001', '2024-06-01', '2025-08-04', '150.00', '300.00', 20),
(8, 'Vitamin C', '4567891011123', 'Tablet', 'Vitamins', 80, 'box', 'LOT-VITC-001', '2024-01-15', '2025-07-12', '500.00', '900.00', 15),
(9, 'Amoxicillin', '9876543210123', 'Capsule', 'Antibiotic', 55, 'card', 'LOT-AMOX-002', '2025-01-10', '2025-08-18', '200.00', '450.00', 10),
(10, 'Cough Syrup', '5556667778889', 'Syrup', 'Cold & Flu', 20, 'bottle', 'LOT-COUGH-005', '2024-09-15', '2025-09-10', '600.00', '1200.00', 5),
(11, 'ORS Sachet', '1122334455667', 'Powder', 'Rehydration', 200, 'sachet', 'LOT-ORS-003', '2025-05-10', '2027-05-09', '50.00', '100.00', 30),
(12, 'Ibuprofen', '9988776655443', 'Tablet', 'Pain Relief', 150, 'box', 'LOT-IBU-004', '2025-03-01', '2026-03-01', '300.00', '600.00', 25),
(13, 'Anti-Malarial', '3344556677889', 'Tablet', 'Malaria Treatment', 90, 'strip', 'LOT-MAL-009', '2024-12-01', '2026-12-01', '800.00', '1500.00', 10),
(14, 'Metformin', '7766554433221', 'Tablet', 'Diabetes', 100, 'box', 'LOT-MET-007', '2025-06-01', '2027-06-01', '250.00', '500.00', 20),
(16, 'မော်ဖင်း', '1235558', 'Injection', 'sleep', 4, 'ချောင်း', 'LOT002-C', '2025-08-19', '2025-09-06', '11500.00', '12000.00', 3),
(17, 'Mixagrip', 'MED1755619195205', 'Capsule', 'fever', 47, 'box', 'LOT003-C', '2025-08-21', '2025-08-21', '5000.00', '7000.00', 5);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `lot_number` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `sold_by` varchar(255) DEFAULT NULL,
  `sold_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `medicine_id`, `lot_number`, `quantity`, `selling_price`, `discount_percent`, `total_amount`, `sold_by`, `sold_at`) VALUES
(1, 5, '1343546', 1, '3000.00', '0.00', '3000.00', 'Sint Wai Toe', '2025-08-09 22:56:49'),
(2, 5, '1343546', 5, '6000.00', '0.00', '30000.00', 'Sint Wai Toe', '2025-08-10 21:24:24'),
(3, 10, 'LOT-COUGH-005', 2, '1200.00', '0.00', '2400.00', 'Sint Wai Toe', '2025-08-13 19:13:19'),
(4, 2, 'LOT002-B', 2, '3500.00', '0.00', '7000.00', 'Sint Wai Toe', '2025-08-13 19:30:39'),
(5, 2, 'LOT002-B', 1, '450.00', '0.00', '450.00', 'Sint Wai Toe', '2025-08-13 19:32:29'),
(6, 6, 'Lot 24056', 1, '12000.00', '0.00', '12000.00', 'Sint Wai Toe', '2025-08-13 19:32:29'),
(7, 10, 'LOT-COUGH-005', 1, '1200.00', '0.00', '1200.00', 'Sint Wai Toe', '2025-08-13 19:32:29'),
(8, 5, 'LOT001-A1', 12, '15000.00', '0.00', '180000.00', 'Sint Wai Toe', '2025-08-13 22:02:32'),
(9, 5, 'LOT001-A1', 1, '15000.00', '0.00', '15000.00', 'Sint Wai Toe', '2025-08-13 23:09:58'),
(10, 5, 'LOT001-A1', 1, '15000.00', '0.00', '15000.00', 'Sint Wai Toe', '2025-08-13 23:19:45'),
(11, 5, 'LOT001-A1', 1, '15000.00', '0.00', '15000.00', 'Sint Wai Toe', '2025-08-13 23:20:11'),
(12, 5, 'LOT001-A1', 1, '15000.00', '0.00', '15000.00', 'Sint Wai Toe', '2025-08-13 23:22:07'),
(13, 5, 'LOT001-A1', 1, '15000.00', '0.00', '15000.00', 'Sint Wai Toe', '2025-08-13 23:37:23'),
(14, 2, 'LOT002-B', 1, '450.00', '0.00', '450.00', 'Sint Wai Toe', '2025-08-15 21:11:17'),
(15, 2, 'LOT002-B', 2, '5000.00', '10.00', '9000.00', 'Sint Wai Toe', '2025-08-15 21:11:17'),
(16, 2, 'LOT002-B', 1, '450.00', '0.00', '450.00', 'Sint Wai Toe', '2025-08-15 21:16:43'),
(17, 5, 'LOT001-A1', 4, '15000.00', '0.00', '60000.00', 'Sint Wai Toe', '2025-08-21 12:48:24'),
(18, 7, 'LOT-PARA-001', 110, '2000.00', '0.00', '220000.00', 'Sint Wai Toe', '2025-08-21 16:47:47'),
(19, 17, 'LOT003-C', 3, '7000.00', '0.00', '21000.00', 'Sint Wai Toe', '2025-08-21 16:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `contact_person`, `company_name`, `phone`, `email`, `address`) VALUES
(1, 'U Kyaw Zin Latt', 'Kyaw', '09987654321', 'k@gmail.com', 'Monywa'),
(2, 'U Thura Htun Naing', 'Shwe Man DaLar Hein', '09912987965', 'thn@gmail.com', 'Mandalay');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$8xjTL2kuNQZ1J2oZPS56OO0btJNGNZXUhV1Ba0GqeBhsTEYm6d/7u\r\n'),
(2, 'Sint Wai Toe', '$2y$10$POCugSg.1m30E.WA/.5ehOf4DOblPsQcUiIP7CG.Uyg8z7okyliWm');

-- --------------------------------------------------------

--
-- Table structure for table `wastage`
--

CREATE TABLE `wastage` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `recorded_by` varchar(100) NOT NULL,
  `recorded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `wastage`
--

INSERT INTO `wastage` (`id`, `medicine_id`, `lot_number`, `quantity`, `reason`, `recorded_by`, `recorded_at`) VALUES
(1, 5, '1343546', 2, 'Damaged', 'Sint Wai Toe', '2025-08-10 00:43:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `disposals`
--
ALTER TABLE `disposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wastage`
--
ALTER TABLE `wastage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `disposals`
--
ALTER TABLE `disposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wastage`
--
ALTER TABLE `wastage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  ADD CONSTRAINT `goods_receipt_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `goods_receipts_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `wastage`
--
ALTER TABLE `wastage`
  ADD CONSTRAINT `wastage_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
