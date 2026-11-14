SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

--
-- Database: `finance_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('bank','cash','credit_card','investment','loan') NOT NULL DEFAULT 'bank',
  `initial_balance` decimal(10,2) DEFAULT 0.00,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `credit_limit` decimal(10,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `due_day` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `monthly_limit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `user_category_budget` (`user_id`, `category`),
  INDEX `idx_user_budget` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recurring_bills`
--

CREATE TABLE `recurring_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `next_due_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  INDEX `idx_user_bills` (`user_id`),
  INDEX `idx_due_date` (`next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_goals`
--

CREATE TABLE `savings_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  INDEX `idx_user_goals` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--

-- --------------------------------------------------------

--
-- Table structure for table `transfers`
--

CREATE TABLE `transfers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `from_account_id` int(11) DEFAULT NULL,
  `to_account_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'Admin', 'admin@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-11-15 17:22:35');

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `from_account_id` (`from_account_id`),
  ADD KEY `to_account_id` (`to_account_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `transfers`
--
ALTER TABLE `transfers`
  ADD CONSTRAINT `transfers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transfers_ibfk_2` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `transfers_ibfk_3` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`);
COMMIT;