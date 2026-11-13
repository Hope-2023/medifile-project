-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2026 at 11:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medifile_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `app_date_time` datetime NOT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `type` varchar(20) DEFAULT 'Upcoming',
  `reason` varchar(150) NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_by` varchar(100) DEFAULT 'nurse_Mlambo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `app_date_time`, `appointment_date`, `type`, `reason`, `status`, `created_by`) VALUES
(3, 2, '2026-12-14 10:00:00', '2026-12-14 10:00:00', 'Upcoming', 'Diabetes Consultation', '', 'nurse_Mlambo'),
(4, 3, '2026-11-01 08:30:00', '2026-11-01 08:30:00', 'Previous', 'Vaccination', 'Completed', 'nurse_Mlambo'),
(5, 5, '2026-11-05 13:20:57', '2026-11-05 13:20:57', 'Previous', 'Acute - Flu and Fever - Fever 38.5c,cough,more sore throat-Given Paracetamol 500mg- Adviced to rest+fluids - Return if fever continues', 'Completed', 'nurse_Mlambo'),
(6, 5, '2027-05-12 10:00:00', '2027-05-12 10:00:00', 'Upcoming', 'Diabetes collection', 'Scheduled', 'nurse_Mlambo'),
(7, 4, '2026-11-05 14:04:17', '2026-11-05 14:04:17', 'Previous', 'Antenental-Acute - Follow-up: 32 weeks pregnant- Bp checked- Urine checked\r\nBaby\'s heartbeat normal.\r\nGiven folic acid and iron ', 'Completed', 'nurse_Mlambo'),
(8, 4, '2026-11-23 07:00:00', '2026-11-23 07:00:00', 'Upcoming', 'next visit in three weeks for 36 weeks follow up', 'Scheduled', 'nurse_Mlambo'),
(9, 8, '2026-11-05 14:15:23', '2026-11-05 14:15:23', 'Previous', 'Pulse problem  - Pulse 118 bpm - fast bp 130/85.\r\npatient feels dizzy and heart racing. \r\nECG / pulse check done\r\nRested for 15 min, pulsedown to 98 b', 'Completed', 'nurse_Mlambo'),
(10, 14, '2026-11-05 13:22:39', '2026-11-05 13:22:39', 'Current', 'Antenental-Acute - Current Visit', 'Completed', 'nurse_Mlambo'),
(15, 14, '2026-11-13 02:28:01', '2026-11-13 02:28:01', 'Previous', 'Diarrhea - drink lots of motswako and comes back after three days if not stopping', 'Completed', 'nurse_Mlambo'),
(16, 1, '2026-11-13 02:32:03', '2026-11-13 02:32:03', 'Previous', 'Acute - Flu and Fever - Flu mix tablets,alcohfilix syrup', 'Completed', 'nurse_Mlambo'),
(17, 1, '2027-03-05 07:00:00', '2027-03-05 07:00:00', 'Upcoming', 'ARV collection', 'Scheduled', 'nurse_Mlambo');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `created_by_user` int(11) NOT NULL,
  `visit_date` datetime DEFAULT current_timestamp(),
  `vitals` varchar(100) DEFAULT NULL,
  `diagnosis` text NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `created_by_user`, `visit_date`, `vitals`, `diagnosis`, `notes`) VALUES
(1, 1, 1, '2026-02-20 08:00:00', 'BP 140/90 Pulse 88', 'Hypertension', 'Headache, prescribed Amlodine'),
(2, 2, 1, '2026-05-26 09:00:00', 'Glucose 8.7 BP 120/80', 'Diabetes Type 2', 'Follow up in 2 weeks'),
(3, 3, 2, '2026-10-20 08:15:00', 'Temp 38.2C Pulse 92', 'Flu', 'Panado Rest 3 days'),
(4, 4, 1, '2026-10-15 11:30:00', 'BP 18/78 Pulse 72', 'Routine checkup', 'All normal');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `sa_id_number` varchar(13) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `preferred_lang` varchar(20) DEFAULT 'English',
  `diagnosis` varchar(255) DEFAULT 'General - Checkup',
  `medication_prescribed` varchar(255) DEFAULT '',
  `arrival_time` datetime DEFAULT current_timestamp(),
  `file_wait_start` datetime DEFAULT current_timestamp(),
  `seen_time` datetime DEFAULT NULL,
  `file_release_time` datetime DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `seen_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `sa_id_number`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone_number`, `preferred_lang`, `diagnosis`, `medication_prescribed`, `arrival_time`, `file_wait_start`, `seen_time`, `file_release_time`, `allergies`, `seen_by`) VALUES
(1, '9001015800081', 'Thabiso', 'Molokomme', '1990-01-01', 'Male', '0799179745', 'Sepedi', 'Chronic - High Blood Pressure Collection', 'Amlodipine 5mg - 30 days', '2026-11-05 11:41:02', '2026-11-03 12:34:47', '2026-11-13 02:32:03', '2026-11-05 12:02:02', NULL, NULL),
(2, '8505120456083', 'Dineo', 'Ndlovu', '1985-05-12', 'Female', '0828218694', 'Zulu', 'Chronic - HIV Treatment Collection', 'ARVs - Tenofovir 30 days', '2026-11-05 11:04:02', '2026-11-03 12:34:47', NULL, '2026-11-05 11:38:02', NULL, NULL),
(3, '9609150061082', 'Sipho', 'Ntimbane', '1996-09-15', 'Male', '0661681506', 'Tsonga', 'Prevention - PrEP Injection', 'CAB-LA Injection - Every 2 months', '2026-11-05 10:27:02', '2026-11-03 12:34:47', NULL, '2026-11-05 10:47:02', NULL, NULL),
(4, '0209090313088', 'Tshepang', 'Nkoko', '2002-09-09', 'Female', '0607261279', 'English', 'Antenental-Acute', 'folic acid', '2026-11-05 09:50:02', '2026-11-03 12:34:47', '2026-11-05 13:54:01', '2026-11-05 10:23:02', 'No drug allergie -No Food allergy', 'nurse_Mlambo'),
(5, '9203153009084', 'Nomsa', 'Khumalo', '1992-03-15', 'Female', '0829876543', 'English', 'Chronic - Diabetes Collection', 'Metformin 500mg - 60 tablets', '2026-11-05 11:43:02', '2026-11-03 12:34:47', '2026-11-05 13:20:57', '2026-11-05 12:02:02', NULL, NULL),
(7, '9505204800083', 'Lerato', 'Dube', '1995-05-20', 'Female', '0842223344', 'Sotho', 'Prevention - Family Planning', 'Contraceptive Injection - 3 months', '2026-11-05 10:29:02', '2026-11-03 12:34:47', NULL, '2026-11-05 10:47:02', NULL, NULL),
(8, '9007105500082', 'Thabo', 'Molefe', '1990-07-10', 'Male', '0813334455', 'English', 'Chronic - Asthma Collection', 'Ventolin Inhaler - 30 days', '2026-11-05 09:52:02', '2026-11-03 12:34:47', '2026-11-05 14:09:12', '2026-11-05 10:23:02', 'NONE', 'nurse_Mlambo'),
(14, '9903221234089', 'Pontsho', 'Makwela', '1999-03-22', 'Male', '0660745987', 'sepedi', 'Antenental-Acute', 'folic acid and iron', '2026-11-05 13:22:39', '2026-11-05 13:22:39', '2026-11-13 02:28:01', NULL, 'No drug allergie -No Food allergy', 'nurse_Mlambo');

-- --------------------------------------------------------

--
-- Table structure for table `patient_visits`
--

CREATE TABLE `patient_visits` (
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visit_date` datetime DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `doctor_nurse` varchar(100) DEFAULT NULL,
  `allergies` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_visits`
--

INSERT INTO `patient_visits` (`visit_id`, `patient_id`, `visit_date`, `diagnosis`, `notes`, `doctor_nurse`, `allergies`) VALUES
(2, 5, '2026-11-05 13:20:57', 'Acute - Flu and Fever', 'Fever 38.5c,cough,more sore throat-Given Paracetamol 500mg- Adviced to rest+fluids - Return if fever continues', 'nurse_Mlambo', NULL),
(3, 4, '2026-11-05 14:04:17', 'Antenental-Acute', 'Follow-up: 32 weeks pregnant- Bp checked- Urine checked\r\nBaby\'s heartbeat normal.\r\nGiven folic acid and iron ', 'nurse_Mlambo', 'No drug allergie -No Food allergy'),
(4, 8, '2026-11-05 14:15:22', 'Pulse problem ', 'Pulse 118 bpm - fast bp 130/85.\r\npatient feels dizzy and heart racing. \r\nECG / pulse check done\r\nRested for 15 min, pulsedown to 98 bpm.\r\nReffered to doctor for follow up', 'nurse_Mlambo', 'NONE'),
(5, 14, '2026-11-13 02:28:01', 'Diarrhea', 'drink lots of motswako and comes back after three days if not stopping', 'nurse_Mlambo', NULL),
(6, 1, '2026-11-13 02:32:03', 'Acute - Flu and Fever', 'Flu mix tablets,alcohfilix syrup', 'nurse_Mlambo', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `username`, `full_name`, `role`, `password`) VALUES
(1, 'nurse_manyica', 'Dimpho manyica', 'nurse', 'nurse_manyica@123'),
(2, 'Matron_Mkhabela', 'Makhosazane Mkhabela', 'Matron/ Senor Nurse', '$2y$10$KHxbee6YULNkatkBFWy7SecjRDXRJJnA6iHrklMPa3z79Kl.Z.CP2'),
(3, 'silas', 'silas tops', 'king', '$2y$10$fD0jX9OcLje/jb/ZSca5m.4GVapUvIpVuTjhDDlM8GPxUis5tlmAW'),
(5, 'Lesedi', 'mampho', 'matron', '$2y$10$7VQbQNmYViPsexBTMxArTuh.POWC9BaHcti7NIOvPrbZa59bTbj4q');

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

CREATE TABLE `sync_log` (
  `sync_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_type` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `sync_status` enum('Pending','Synced','Conflict') DEFAULT 'Pending',
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sync_log`
--

INSERT INTO `sync_log` (`sync_id`, `user_id`, `record_type`, `action`, `sync_status`, `timestamp`) VALUES
(1, 1, 'patient', 'CREATE', 'Pending', '2026-10-29 19:51:15'),
(2, 1, 'appointment', 'CREATE', 'Synced', '2026-10-29 19:51:15'),
(3, 1, 'medical_record', 'UPDATE', 'Pending', '2026-10-29 18:30:00'),
(4, 2, 'patient', 'DELETE', '', '2026-10-29 17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Nurse','Clerk') NOT NULL,
  `full_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `full_name`) VALUES
(1, 'admin_hope', 'admin@230', 'Admin', 'Hope Mojapelo'),
(2, 'nurse_Mlambo', 'nurse@980', 'Nurse', 'Sarah Mlambo'),
(3, 'clerk_john', '$clerk@123', 'Clerk', 'John Sithole');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `created_by_user` (`created_by_user`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `sa_id_number` (`sa_id_number`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`visit_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD PRIMARY KEY (`sync_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sync_log`
--
ALTER TABLE `sync_log`
  MODIFY `sync_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`created_by_user`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD CONSTRAINT `sync_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
