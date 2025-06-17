-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 08:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `splicing_detection`
--

-- --------------------------------------------------------

--
-- Table structure for table `analysis_results`
--

CREATE TABLE `analysis_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `result_folder` varchar(255) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `is_spliced` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analysis_results`
--

INSERT INTO `analysis_results` (`id`, `user_id`, `result_folder`, `timestamp`, `is_spliced`) VALUES
(15, 12, '2025-06-09_21-49-02', '2025-06-10 03:50:04', 1),
(16, 12, '2025-06-15_14-23-44', '2025-06-15 20:24:56', 1),
(17, 13, '2025-06-16_17-51-53', '2025-06-16 23:53:26', 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `timestamp`, `success`) VALUES
(57, 'ai220232@student.uthm.edu.my', '2025-06-16 23:51:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `salt` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`, `reset_token`, `reset_token_expires`, `verification_token`, `email_verified`, `salt`, `is_admin`) VALUES
(11, 'admin@splicenoise.com', '$argon2id$v=19$m=65536,t=4,p=2$ak1Rd2l5WlBpeUw4OXNWNg$JqSOzvJi/U8Dn//HijN80UUF7T6sq8NOUB7SwQFSF5c', '2025-06-10 03:07:26', NULL, NULL, NULL, 1, 'YP/VD48BDmdX5i/4dE8d34XXnhE3cDRVZJngItqr6Pk=', 1),
(12, 'farisha1497@gmail.com', '$argon2id$v=19$m=65536,t=4,p=2$emJpTXl6RFBkc0FIa1ZYcg$zxbaW3p31lUhn9Q+QHLTIFa/JKKx8iayySIOmj8sNNw', '2025-06-10 03:46:56', NULL, NULL, NULL, 1, 's7g8pHMqZw/68gYgrSyZDXj/vo7qja5WBnQBKXbvBko=', 0),
(13, 'ai220232@student.uthm.edu.my', '$argon2id$v=19$m=65536,t=4,p=2$RXEzUDFwdGp1VFU2YUVqNQ$O8adKPbboqKo9UEhsSRLjvynT5lZoSDNRhe527nOI/g', '2025-06-16 23:49:59', NULL, NULL, NULL, 1, 'nq8SsnljG3wKp9MEpNgaAueSRNPo/nid2H14JrX7L1Q=', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`timestamp`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD CONSTRAINT `analysis_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
