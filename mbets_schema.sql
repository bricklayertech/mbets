-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Stores the hashed password
  `role` ENUM('Intern', 'Analyst', 'Admin') NOT NULL DEFAULT 'Intern',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `warrants` (For Transaction Data Entry)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warrants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('Warrant', 'Request', 'IGF_Revenue') NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `description` TEXT NOT NULL,
  `date_entered` DATE NOT NULL,
  `logged_by_user_id` INT(11) DEFAULT NULL, -- Link entry back to the user who logged it
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`logged_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Initial Data: Create a default Admin user
-- (Password is 'adminpassword', which you must change immediately after first login)
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$9Gv1BwJ9fF2vWl7u0gK21eS.m9c8z/WJ6L9F7gY/Mh2M9Oq8T9W5', 'Admin');