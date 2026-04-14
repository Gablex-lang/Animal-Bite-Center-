-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 07:43 AM
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
-- Database: `animal_bite_center_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bite_incident`
--

CREATE TABLE `bite_incident` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `recorded_by_id` int(11) NOT NULL,
  `date_registered` date NOT NULL,
  `bite_date` date NOT NULL,
  `place_occurred` varchar(100) NOT NULL,
  `animal_type` varchar(100) NOT NULL,
  `bite_type` enum('B','NB') NOT NULL,
  `body_site` varchar(100) NOT NULL,
  `category` enum('1','2','3') NOT NULL,
  `rig_date` date DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_date` date DEFAULT NULL,
  `archived_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bite_incident`
--

INSERT INTO `bite_incident` (`id`, `patient_id`, `recorded_by_id`, `date_registered`, `bite_date`, `place_occurred`, `animal_type`, `bite_type`, `body_site`, `category`, `rig_date`, `is_archived`, `archived_date`, `archived_by_id`) VALUES
(1, 1, 1, '2026-01-05', '2026-01-05', 'BRGY. CARMEN', 'DOG', 'B', 'LEFT HAND', '2', NULL, 0, NULL, NULL),
(2, 2, 1, '2026-01-15', '2026-01-14', 'PUROK 2 PLAYGROUND', 'DOG', 'B', 'RIGHT ARM', '1', NULL, 0, NULL, NULL),
(3, 3, 1, '2026-01-22', '2026-01-22', 'SCHOOL PREMISES', 'DOG', 'B', 'RIGHT HAND', '3', '2026-01-22', 0, NULL, NULL),
(4, 4, 1, '2026-02-10', '2026-02-10', 'PUROK 1 BACKYARD', 'DOG', 'B', 'LEFT FOOT', '1', NULL, 0, NULL, NULL),
(5, 5, 1, '2026-02-20', '2026-02-19', 'BRGY. CARMEN ROAD', 'DOG', 'B', 'RIGHT LEG', '2', NULL, 0, NULL, NULL),
(6, 6, 1, '2026-03-01', '2026-03-01', 'HOME', 'CAT', 'B', 'LEFT ARM', '1', NULL, 0, NULL, NULL),
(7, 7, 1, '2026-03-14', '2026-03-14', 'PUROK 7 STREET', 'DOG', 'B', 'LEFT HAND', '2', NULL, 0, NULL, NULL),
(8, 8, 1, '2026-03-15', '2026-03-15', 'BRGY. CARMEN MARKET', 'DOG', 'B', 'NECK', '3', '2026-03-15', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `defaulter_log`
--

CREATE TABLE `defaulter_log` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `date_of_entry` date NOT NULL,
  `missed_dose` varchar(10) NOT NULL,
  `reason_for_default` varchar(255) DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `staff_responsible_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `age` tinyint(3) UNSIGNED NOT NULL,
  `sex` enum('M','F') NOT NULL,
  `address` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `contact_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `middle_name`, `last_name`, `age`, `sex`, `address`, `created_at`, `contact_no`) VALUES
(1, 'MARIA', 'SANTOS', 'DELA CRUZ', 34, 'F', 'PUROK 4, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09171234501'),
(2, 'JOSE', 'REYES', 'GARCIA', 27, 'M', 'PUROK 2, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09281234502'),
(3, 'ANA', 'LOZANO', 'FERNANDEZ', 19, 'F', 'PUROK 6, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09391234503'),
(4, 'PEDRO', 'CRUZ', 'MARTINEZ', 45, 'M', 'PUROK 1, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09501234504'),
(5, 'LUCIA', 'BAUTISTA', 'TORRES', 12, 'F', 'PUROK 3, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09611234505'),
(6, 'CARLOS', 'RAMOS', 'FLORES', 38, 'M', 'PUROK 5, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09721234506'),
(7, 'ELENA', 'MENDOZA', 'RIVERA', 22, 'F', 'PUROK 7, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09831234507'),
(8, 'ANTONIO', 'CASTILLO', 'LOPEZ', 55, 'M', 'PUROK 8, BRGY. CARMEN, CAGAYAN DE ORO CITY', '2026-03-17 13:44:05', '09941234508');

-- --------------------------------------------------------

--
-- Table structure for table `pep_treatment`
--

CREATE TABLE `pep_treatment` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `injection_incharge_id` int(11) DEFAULT NULL,
  `washed` enum('Y','N') NOT NULL,
  `injection_route` varchar(10) NOT NULL DEFAULT '',
  `vaccination_schedule_id` int(11) NOT NULL,
  `outcome` enum('C','INC','N','D') NOT NULL DEFAULT 'INC',
  `animal_status` enum('ALIVE','DEAD','LOST') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `price_at_registration` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pep_treatment`
--

INSERT INTO `pep_treatment` (`id`, `patient_id`, `incident_id`, `vaccine_id`, `injection_incharge_id`, `washed`, `injection_route`, `vaccination_schedule_id`, `outcome`, `animal_status`, `remarks`, `price_at_registration`) VALUES
(1, 1, 1, 1, 1, 'Y', 'ID', 1, 'C', 'ALIVE', NULL, 1200.00),
(2, 2, 2, 1, 1, 'Y', 'ID', 2, 'C', 'ALIVE', NULL, 1200.00),
(3, 3, 3, 1, 1, 'Y', 'ID', 3, 'C', 'DEAD', 'RIG ADMINISTERED', 1200.00),
(4, 4, 4, 1, 1, 'Y', 'ID', 4, 'INC', 'ALIVE', NULL, 1200.00),
(5, 5, 5, 1, 1, 'Y', 'ID', 5, 'INC', 'ALIVE', NULL, 1200.00),
(6, 6, 6, 1, 1, 'Y', 'IM', 6, 'INC', 'ALIVE', NULL, 1200.00),
(7, 7, 7, 1, NULL, 'Y', 'ID', 7, 'INC', 'ALIVE', NULL, 1200.00),
(8, 8, 8, 1, NULL, 'Y', 'IM', 8, 'INC', 'ALIVE', 'RIG GIVEN', 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('STAFF','ADMIN','SUPERADMIN') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `first_name`, `middle_name`, `last_name`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SUPERADMIN', 'SYSTEM', NULL, 'ADMINISTRATOR', '2026-02-28 14:52:50'),
(6, 'RYAN1', '$2y$10$hyE0xojsBHc7TPWSS6VCIea4Uu4MUHI6jjT4K.vzTYNXaFn/4Lay2', 'STAFF', 'RONALD', 'RYAN', 'INTUD', '2026-03-07 18:13:33'),
(8, 'SARANG1', '$2y$10$syxfIywv2DRmh.CcE52EreWYHxX/HJL6q3lcMTJwPlM4N0MCFHljS', 'ADMIN', 'KENT', 'ZACHIE', 'SARANG', '2026-03-07 18:14:39');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_schedules`
--

CREATE TABLE `vaccination_schedules` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `day_0` date DEFAULT NULL,
  `day_3` date DEFAULT NULL,
  `day_7` date DEFAULT NULL,
  `day_14` date DEFAULT NULL,
  `day_28` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_schedules`
--

INSERT INTO `vaccination_schedules` (`id`, `incident_id`, `day_0`, `day_3`, `day_7`, `day_14`, `day_28`) VALUES
(1, 1, '2026-01-05', '2026-01-08', '2026-01-12', '2026-01-19', '2026-02-02'),
(2, 2, '2026-01-15', '2026-01-18', '2026-01-22', '2026-01-29', '2026-02-12'),
(3, 3, '2026-01-22', '2026-01-25', '2026-01-29', '2026-02-05', '2026-02-19'),
(4, 4, '2026-02-10', '2026-02-13', '2026-02-17', NULL, NULL),
(5, 5, '2026-02-20', '2026-02-23', NULL, NULL, NULL),
(6, 6, '2026-03-01', NULL, NULL, NULL, NULL),
(7, 7, NULL, NULL, NULL, NULL, NULL),
(8, 8, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vaccine`
--

CREATE TABLE `vaccine` (
  `id` int(11) NOT NULL,
  `vaccine_brand` varchar(100) NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `stocked_date` date NOT NULL,
  `added_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccine`
--

INSERT INTO `vaccine` (`id`, `vaccine_brand`, `stock_quantity`, `price`, `expiry_date`, `stocked_date`, `added_by_id`) VALUES
(1, 'SPEEDA', 97, 1200.00, '2027-03-01', '2026-03-01', 1),
(2, 'ABRAYHAYB', 100, 1200.00, '2026-03-06', '2026-03-06', 1),
(3, 'SPEEDA', 100, 1200.00, '2026-03-27', '2026-03-07', 1),
(4, 'ABRAYHAYB', 99, 1200.00, '2026-03-20', '2026-03-07', 1),
(5, 'SPEEDA', 100, 1200.00, '2026-03-26', '2026-03-07', 1),
(6, 'ABRAYHAYB', 100, 1200.00, '2026-03-27', '2026-03-07', 1),
(7, 'SPEEDA', 100, 1200.00, '2026-03-26', '2026-03-07', 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bite_incident`
--
ALTER TABLE `bite_incident`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient` (`patient_id`),
  ADD KEY `recorded_by` (`recorded_by_id`),
  ADD KEY `archived_by` (`archived_by_id`),
  ADD KEY `idx_is_archived` (`is_archived`);

--
-- Indexes for table `defaulter_log`
--
ALTER TABLE `defaulter_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dl_patient` (`patient_id`),
  ADD KEY `dl_incident` (`incident_id`),
  ADD KEY `dl_staff` (`staff_responsible_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pep_treatment`
--
ALTER TABLE `pep_treatment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pep_incident` (`incident_id`),
  ADD KEY `pep_vaccine` (`vaccine_id`),
  ADD KEY `pep_patient` (`patient_id`),
  ADD KEY `pep_schedule` (`vaccination_schedule_id`),
  ADD KEY `pep_incharge` (`injection_incharge_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_unique` (`username`);

--
-- Indexes for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vs_incident` (`incident_id`);

--
-- Indexes for table `vaccine`
--
ALTER TABLE `vaccine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bite_incident`
--
ALTER TABLE `bite_incident`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `defaulter_log`
--
ALTER TABLE `defaulter_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pep_treatment`
--
ALTER TABLE `pep_treatment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vaccine`
--
ALTER TABLE `vaccine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bite_incident`
--
ALTER TABLE `bite_incident`
  ADD CONSTRAINT `bi_archived_by` FOREIGN KEY (`archived_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bi_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `bi_recorded_by` FOREIGN KEY (`recorded_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `defaulter_log`
--
ALTER TABLE `defaulter_log`
  ADD CONSTRAINT `dl_incident` FOREIGN KEY (`incident_id`) REFERENCES `bite_incident` (`id`),
  ADD CONSTRAINT `dl_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `dl_staff` FOREIGN KEY (`staff_responsible_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pep_treatment`
--
ALTER TABLE `pep_treatment`
  ADD CONSTRAINT `pep_incharge` FOREIGN KEY (`injection_incharge_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pep_incident` FOREIGN KEY (`incident_id`) REFERENCES `bite_incident` (`id`),
  ADD CONSTRAINT `pep_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `pep_schedule` FOREIGN KEY (`vaccination_schedule_id`) REFERENCES `vaccination_schedules` (`id`),
  ADD CONSTRAINT `pep_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccine` (`id`);

--
-- Constraints for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  ADD CONSTRAINT `vs_incident` FOREIGN KEY (`incident_id`) REFERENCES `bite_incident` (`id`);

--
-- Constraints for table `vaccine`
--
ALTER TABLE `vaccine`
  ADD CONSTRAINT `vaccine_added_by` FOREIGN KEY (`added_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
