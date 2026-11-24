--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `gauge_calibrations`
--

CREATE TABLE `gauge_calibrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `certificate_no` varchar(100) NOT NULL,
  `client` varchar(255) NOT NULL,
  `nuprc` varchar(255) DEFAULT NULL,
  `test_item` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `pressure_range` decimal(15,4) NOT NULL,
  `pressure_unit` varchar(20) NOT NULL DEFAULT 'BAR',
  `calibration_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `tested_by` varchar(255) DEFAULT NULL,
  `witnessed_by` varchar(255) DEFAULT NULL,
  `tested_signature` varchar(255) DEFAULT NULL,
  `witnessed_signature` varchar(255) DEFAULT NULL,
  `stamp_image` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `gauge_connection` varchar(255) DEFAULT NULL,
  `instrument_range` decimal(15,4) DEFAULT NULL,
  `range_unit` varchar(20) NOT NULL DEFAULT 'BAR',
  `equipment_name` varchar(255) DEFAULT NULL,
  `equipment_serial` varchar(255) DEFAULT NULL,
  `equipment_range` varchar(255) DEFAULT NULL,
  `equipment_qty` varchar(100) DEFAULT NULL,
  `equipment_calibration_date` date DEFAULT NULL,
  `equipment_due_date` date DEFAULT NULL,
  `equipment_certification_no` varchar(255) DEFAULT NULL,
  `signature_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gauge_calibration_readings`
--

CREATE TABLE `gauge_calibration_readings` (
  `id` int(10) UNSIGNED NOT NULL,
  `calibration_id` int(10) UNSIGNED NOT NULL,
  `reading_type` varchar(20) NOT NULL,
  `percentage_range` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `actual_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `dead_weight_reading` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `gauge_reading` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `error_percent` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gauge_equipment_settings`
--

CREATE TABLE `gauge_equipment_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `equipment_name` varchar(255) NOT NULL DEFAULT '',
  `equipment_range` varchar(255) NOT NULL DEFAULT '',
  `equipment_serial` varchar(255) NOT NULL DEFAULT '',
  `equipment_calibration_date` date DEFAULT NULL,
  `equipment_due_date` date DEFAULT NULL,
  `equipment_qty` varchar(100) NOT NULL DEFAULT '',
  `equipment_certification_no` varchar(255) NOT NULL DEFAULT '',
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nuprc_settings`
--

CREATE TABLE `nuprc_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `nuprc` varchar(255) NOT NULL DEFAULT '',
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recorder_calibrations`
--

CREATE TABLE `recorder_calibrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `certificate_no` varchar(100) NOT NULL,
  `client` varchar(255) NOT NULL,
  `nuprc` varchar(255) DEFAULT NULL,
  `test_item` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `pressure_range` decimal(15,4) NOT NULL,
  `pressure_unit` varchar(20) NOT NULL DEFAULT 'BAR',
  `temperature_range` decimal(15,4) NOT NULL,
  `temperature_unit` varchar(20) NOT NULL DEFAULT 'F',
  `calibration_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `pressure_equipment_name` varchar(255) DEFAULT NULL,
  `pressure_equipment_range` varchar(255) DEFAULT NULL,
  `pressure_equipment_calibration_date` date DEFAULT NULL,
  `pressure_equipment_due_date` date DEFAULT NULL,
  `pressure_equipment_certificate_no` varchar(255) DEFAULT NULL,
  `pressure_equipment_serial` varchar(255) DEFAULT NULL,
  `temperature_equipment_name` varchar(255) DEFAULT NULL,
  `temperature_equipment_range` varchar(255) DEFAULT NULL,
  `temperature_equipment_calibration_date` date DEFAULT NULL,
  `temperature_equipment_due_date` date DEFAULT NULL,
  `temperature_equipment_certificate_no` varchar(255) DEFAULT NULL,
  `temperature_equipment_serial` varchar(255) DEFAULT NULL,
  `tested_by` varchar(255) DEFAULT NULL,
  `witnessed_by` varchar(255) DEFAULT NULL,
  `tested_date` date DEFAULT NULL,
  `witnessed_date` date DEFAULT NULL,
  `tested_signature` varchar(255) DEFAULT NULL,
  `witnessed_signature` varchar(255) DEFAULT NULL,
  `stamp_image` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recorder_calibration_readings`
--

CREATE TABLE `recorder_calibration_readings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `calibration_id` int(10) UNSIGNED NOT NULL,
  `reading_type` enum('rising','falling') NOT NULL,
  `percentage_range` decimal(7,2) NOT NULL,
  `actual_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `dead_weight_reading` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `recorder_reading` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `temperature_cal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `temperature_recorder` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recorder_equipment_settings`
--

CREATE TABLE `recorder_equipment_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `equipment_type` enum('pressure','temperature') NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `equipment_range` varchar(255) NOT NULL DEFAULT '',
  `calibration_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `certificate_no` varchar(255) NOT NULL DEFAULT '',
  `serial_no` varchar(255) NOT NULL DEFAULT '',
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','technician') NOT NULL DEFAULT 'technician',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `valve_certificates`
--

CREATE TABLE `valve_certificates` (
  `id` int(10) UNSIGNED NOT NULL,
  `certificate_no` varchar(100) NOT NULL,
  `client` varchar(255) NOT NULL,
  `nuprc` varchar(255) DEFAULT NULL,
  `test_location` varchar(255) DEFAULT NULL,
  `tested_item` varchar(255) DEFAULT NULL,
  `valve_class` varchar(255) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `matp` varchar(255) DEFAULT NULL,
  `test_date` date DEFAULT NULL,
  `body_test_date` date DEFAULT NULL,
  `seat_test_date` date DEFAULT NULL,
  `body_total_time` varchar(100) DEFAULT NULL,
  `seat_total_time` varchar(100) DEFAULT NULL,
  `body_medium` varchar(100) DEFAULT NULL,
  `seat_medium` varchar(100) DEFAULT NULL,
  `body_final_pressure` varchar(100) DEFAULT NULL,
  `seat_final_pressure` varchar(100) DEFAULT NULL,
  `tested_by` varchar(255) DEFAULT NULL,
  `tested_name` varchar(255) DEFAULT NULL,
  `witnessed_by` varchar(255) DEFAULT NULL,
  `tested_signature` varchar(255) DEFAULT NULL,
  `witnessed_signature` varchar(255) DEFAULT NULL,
  `stamp_image` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `valve_equipment_settings`
--

CREATE TABLE `valve_equipment_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `equipment_name` varchar(255) NOT NULL DEFAULT 'PRESSURE TEST EQUIPMENT',
  `equipment_range` varchar(255) NOT NULL DEFAULT '',
  `equipment_serial` varchar(255) NOT NULL DEFAULT '',
  `calibration_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `certificate_no` varchar(255) NOT NULL DEFAULT '',
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `valve_physical_checks`
--

CREATE TABLE `valve_physical_checks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `valve_id` int(10) UNSIGNED NOT NULL,
  `check_left` varchar(255) DEFAULT NULL,
  `result_left` varchar(100) DEFAULT NULL,
  `check_right` varchar(255) DEFAULT NULL,
  `result_right` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `valve_pressure_log`
--

CREATE TABLE `valve_pressure_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `valve_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `cavity_body_test` varchar(255) DEFAULT NULL,
  `seat_test` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gauge_calibrations`
--
ALTER TABLE `gauge_calibrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_no` (`certificate_no`);

--
-- Indexes for table `gauge_calibration_readings`
--
ALTER TABLE `gauge_calibration_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gauge_readings_calibration_id` (`calibration_id`);

--
-- Indexes for table `gauge_equipment_settings`
--
ALTER TABLE `gauge_equipment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nuprc_settings`
--
ALTER TABLE `nuprc_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recorder_calibrations`
--
ALTER TABLE `recorder_calibrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_no` (`certificate_no`);

--
-- Indexes for table `recorder_calibration_readings`
--
ALTER TABLE `recorder_calibration_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calibration_id` (`calibration_id`);

--
-- Indexes for table `recorder_equipment_settings`
--
ALTER TABLE `recorder_equipment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_type` (`equipment_type`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `valve_certificates`
--
ALTER TABLE `valve_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_no` (`certificate_no`);

--
-- Indexes for table `valve_equipment_settings`
--
ALTER TABLE `valve_equipment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `valve_physical_checks`
--
ALTER TABLE `valve_physical_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `valve_id` (`valve_id`);

--
-- Indexes for table `valve_pressure_log`
--
ALTER TABLE `valve_pressure_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `valve_id` (`valve_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gauge_calibrations`
--
ALTER TABLE `gauge_calibrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gauge_calibration_readings`
--
ALTER TABLE `gauge_calibration_readings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gauge_equipment_settings`
--
ALTER TABLE `gauge_equipment_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recorder_calibrations`
--
ALTER TABLE `recorder_calibrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recorder_calibration_readings`
--
ALTER TABLE `recorder_calibration_readings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recorder_equipment_settings`
--
ALTER TABLE `recorder_equipment_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `valve_certificates`
--
ALTER TABLE `valve_certificates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `valve_physical_checks`
--
ALTER TABLE `valve_physical_checks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `valve_pressure_log`
--
ALTER TABLE `valve_pressure_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `recorder_calibration_readings`
--
ALTER TABLE `recorder_calibration_readings`
  ADD CONSTRAINT `fk_recorder_readings_calibration` FOREIGN KEY (`calibration_id`) REFERENCES `recorder_calibrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `valve_physical_checks`
--
ALTER TABLE `valve_physical_checks`
  ADD CONSTRAINT `fk_valve_physical_valve` FOREIGN KEY (`valve_id`) REFERENCES `valve_certificates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `valve_pressure_log`
--
ALTER TABLE `valve_pressure_log`
  ADD CONSTRAINT `fk_valve_pressure_log_valve` FOREIGN KEY (`valve_id`) REFERENCES `valve_certificates` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
