-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2021 at 11:21 AM
-- Server version: 10.3.29-MariaDB-log-cll-lve
-- PHP Version: 7.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `techdeve_zakatsukses`
--

-- --------------------------------------------------------

--
-- Table structure for table `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `user_id` varchar(8) NOT NULL,
  `token` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fcm_tokens`
--

INSERT INTO `fcm_tokens` (`user_id`, `token`) VALUES
('57oGZFPL', 'c1S8HXTBQH-9hJFo0P7M2J:APA91bEKmn7SlfziPVz7hzCcHVZUUBoHJLknv3I2J7HFUtDuNgKYOaRRACU6soYCBvZx4L1OlYdpsRfNuE5O3K6tnPlrPGbhHCo62lsWnTn0Ji8S-Yoj8GZ3sUeC8cnJAIM9s73hN2jU'),
('zgpEc9Q7', 'dKZF6UVJToGARXsW2lZGhH:APA91bESYb2cVblTfcuPa0kViZXYcBH6hwAGCP_naFhiZ0Mnkp_lLsE7V85t8tD4eCzExpxzFHHvhuVfsknu-Ein93YMYkhXiVT_OFwKYA-m-8QPcyqlrN9PXsgFwoZ9ceSETOMjJzAn');

-- --------------------------------------------------------

--
-- Table structure for table `harga_emas`
--

CREATE TABLE `harga_emas` (
  `id` int(6) NOT NULL,
  `tanggal` varchar(10) NOT NULL,
  `harga` int(12) NOT NULL,
  `perubahan` float DEFAULT NULL,
  `status` enum('+','-') NOT NULL,
  `sumber` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `harga_emas`
--

INSERT INTO `harga_emas` (`id`, `tanggal`, `harga`, `perubahan`, `status`, `sumber`) VALUES
(1, '13-12-2020', 855000, 0, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(2, '14-12-2020', 908805, 0.063, '+', 'Tamasia'),
(3, '15-12-2020', 939000, 0.033, '+', 'Tamasia'),
(4, '16-12-2020', 962000, 0.024, '+', 'Tamasia'),
(5, '17-12-2020', 965000, 0.003, '+', 'Tamasia'),
(6, '18-12-2020', 972000, 0.007, '+', 'TribunNews'),
(7, '19-12-2020', 970000, 0.002, '-', 'TribunNews'),
(8, '20-12-2020', 970000, 0, '+', 'TribunNews'),
(9, '21-12-2020', 976000, 0.006, '+', 'TribunNews'),
(10, '22-12-2020', 970000, 0.006, '-', 'TribunNews'),
(11, '23-12-2020', 966000, 0.004, '-', 'TribunNews'),
(12, '24-12-2020', 971000, 0.005, '+', 'TribunNews'),
(13, '25-12-2020', 973000, 0.002, '+', 'TribunNews'),
(14, '26-12-2020', 934000, 0.04, '-', 'TribunNews'),
(15, '27-12-2020', 934000, 0, '+', 'TribunNews'),
(16, '28-12-2020', 977000, 0.046, '+', 'TribunNews'),
(17, '29-12-2020', 970500, 0.007, '-', 'TribunNews'),
(18, '30-12-2020', 914500, 0.058, '-', 'TribunNews'),
(19, '31-12-2020', 935000, 0.022, '+', 'TribunNews'),
(20, '01-01-2021', 980000, 0.048, '+', 'TribunNews'),
(21, '02-01-2021', 962500, 0.018, '-', 'TribunNews'),
(22, '03-01-2021', 958200, 0.004, '-', 'TribunNews'),
(23, '04-01-2021', 975000, 0.018, '+', 'TribunNews'),
(24, '05-01-2021', 975000, 0, '+', 'TribunNews'),
(25, '06-01-2021', 947125, 0.029, '-', 'Tamasia'),
(26, '07-01-2021', 936776, 0.011, '-', 'Tamasia'),
(27, '08-01-2021', 934668, 0.002, '-', 'Tamasia'),
(28, '09-01-2021', 918947, 0.017, '-', 'Tamasia'),
(29, '10-01-2021', 918947, 0, '+', 'Tamasia'),
(30, '11-01-2021', 916579, 0.003, '-', 'Tamasia'),
(31, '12-01-2021', 921649, 0.006, '+', 'Tamasia'),
(32, '15-01-2021', 850500, 0.077, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(33, '16-01-2021', 853500, 0.004, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(98, '19-01-2021', 863000, 0.011, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(99, '20-01-2021', 859500, 0.004, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(100, '21-01-2021', 867500, 0.009, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(101, '24-01-2021', 857000, 0.012, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(102, '26-01-2021', 850500, 0.008, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(103, '28-01-2021', 856000, 0.006, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(104, '30-01-2021', 849500, 0.008, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(105, '31-01-2021', 848500, 0.001, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(106, '01-02-2021', 854000, 0.006, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(107, '02-02-2021', 852500, 0.002, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(108, '24-02-2021', 841000, 0.013, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(110, '01-03-2021', 829500, 0.014, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(111, '02-03-2021', 832000, 0.003, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(112, '02-03-2021', 832000, 0.003, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(113, '16-03-2021', 837500, 0.007, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(114, '16-03-2021', 837500, 0.007, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(115, '19-03-2021', 835000, 0.003, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(116, '22-03-2021', 833000, 0.002, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(117, '25-03-2021', 828000, 0.006, '-', 'https://www.indogold.id/harga-emas-hari-ini'),
(118, '19-05-2021', 861500, 0.04, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(119, '02-06-2021', 881500, 0.023, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(120, '02-06-2021', 881500, 0.023, '+', 'https://www.indogold.id/harga-emas-hari-ini'),
(121, '03-06-2021', 871000, 0.012, '-', 'https://www.indogold.id/harga-emas-hari-ini');

-- --------------------------------------------------------

--
-- Table structure for table `harga_saham`
--

CREATE TABLE `harga_saham` (
  `id` int(6) NOT NULL,
  `tanggal` varchar(10) NOT NULL,
  `harga` float NOT NULL,
  `jenis` varchar(25) NOT NULL,
  `perubahan` float DEFAULT NULL,
  `status` enum('+','-') NOT NULL,
  `sumber` varchar(250) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `harga_saham`
--

INSERT INTO `harga_saham` (`id`, `tanggal`, `harga`, `jenis`, `perubahan`, `status`, `sumber`) VALUES
(1, '18-12-2020', 13285900, 'PPERY', 0, '+', 'http://mboum.com/'),
(3, '19-12-2020', 13139800, 'PPERY', 0.011, '-', 'http://mboum.com/'),
(5, '20-12-2020', 13310600, 'PPERY', 0.013, '+', 'http://mboum.com/'),
(4, '19-12-2020', 642432, 'UNLRF', 0.022, '-', 'http://mboum.com/'),
(2, '18-12-2020', 656883, 'UNLRF', 0, '+', 'http://mboum.com/\r\n'),
(59, '16-01-2021', 14357000, 'PPERY', 0.008, '+', 'http://mboum.com/'),
(60, '16-01-2021', 721300, 'UNLRF', 0.008, '+', 'http://mboum.com/'),
(6, '20-12-2020', 652068, 'UNLRF', 0.015, '+', 'http://mboum.com/'),
(7, '21-12-2020', 13177500, 'PPERY', 0.01, '-', 'http://mboum.com/\r\n'),
(8, '21-12-2020', 639027, 'UNLRF', 0.02, '-', 'http://mboum.com/'),
(9, '22-12-2020', 13282900, 'PPERY', 0.008, '+', 'http://mboum.com/'),
(10, '22-12-2020', 645417, 'UNLRF', 0.01, '+', 'http://mboum.com/'),
(11, '23-12-2020', 13163300, 'PPERY', 0.009, '-', 'http://mboum.com/'),
(12, '23-12-2020', 635736, 'UNLRF', 0.015, '-', 'http://mboum.com/'),
(13, '24-12-2020', 13071200, 'PPERY', 0.007, '-', 'http://mboum.com/'),
(14, '24-12-2020', 623021, 'UNLRF', 0.02, '-', 'http://mboum.com/'),
(15, '25-12-2020', 13463300, 'PPERY', 0.03, '+', 'http://mboum.com/'),
(16, '25-12-2020', 630497, 'UNLRF', 0.012, '+', 'http://mboum.com/'),
(17, '26-12-2020', 13476800, 'PPERY', 0.001, '+', 'http://mboum.com/'),
(18, '26-12-2020', 631128, 'UNLRF', 0.001, '+', ''),
(19, '27-12-2020', 13503700, 'PPERY', 0.002, '+', 'http://mboum.com/'),
(20, '27-12-2020', 639332, 'UNLRF', 0.013, '+', 'http://mboum.com/'),
(21, '28-12-2020', 13733300, 'PPERY', 0.017, '+', 'http://mboum.com/'),
(22, '28-12-2020', 650201, 'UNLRF', 0.017, '+', 'http://mboum.com/'),
(23, '29-12-2020', 13458600, 'PPERY', 0.02, '-', 'http://mboum.com/'),
(24, '29-12-2020', 637847, 'UNLRF', 0.019, '-', 'http://mboum.com/'),
(25, '30-12-2020', 13391400, 'PPERY', 0.005, '-', 'http://mboum.com/'),
(26, '30-12-2020', 628280, 'UNLRF', 0.015, '-', 'http://mboum.com/'),
(27, '31-12-2020', 14060900, 'PPERY', 0.05, '+', 'http://mboum.com/'),
(28, '31-12-2020', 659693, 'UNLRF', 0.05, '+', 'http://mboum.com/'),
(29, '01-01-2021', 14004700, 'PPERY', 0.004, '-', 'http://mboum.com/'),
(30, '01-01-2021', 643861, 'UNLRF', 0.024, '-', 'http://mboum.com/'),
(31, '02-01-2021', 14326800, 'PPERY', 0.023, '+', 'http://mboum.com/'),
(32, '02-01-2021', 663177, 'UNLRF', 0.03, '+', 'http://mboum.com/'),
(33, '03-01-2021', 14312500, 'PPERY', 0.001, '-', 'http://mboum.com/'),
(34, '03-01-2021', 662513, 'UNLRF', 0.001, '-', 'http://mboum.com/'),
(35, '04-01-2021', 14283800, 'PPERY', 0.002, '-', 'http://mboum.com/'),
(36, '04-01-2021', 661188, 'UNLRF', 0.002, '-', 'http://mboum.com/'),
(37, '05-01-2021', 14241000, 'PPERY', 0.003, '-', 'http://mboum.com/'),
(38, '05-01-2021', 655238, 'UNLRF', 0.009, '-', 'http://mboum.com/'),
(39, '06-01-2021', 14184000, 'PPERY', 0.004, '-', 'http://mboum.com/'),
(40, '06-01-2021', 652617, 'UNLRF', 0.004, '-', 'http://mboum.com/'),
(41, '07-01-2021', 14283300, 'PPERY', 0.007, '+', 'http://mboum.com/'),
(42, '07-01-2021', 683290, 'UNLRF', 0.047, '+', 'http://mboum.com/'),
(43, '08-01-2021', 14469000, 'PPERY', 0.013, '+', 'http://mboum.com/'),
(44, '08-01-2021', 712671, 'UNLRF', 0.043, '+', 'http://mboum.com/'),
(45, '09-01-2021', 14497900, 'PPERY', 0.002, '+', 'http://mboum.com/'),
(46, '09-01-2021', 728350, 'UNLRF', 0.022, '+', 'http://mboum.com/'),
(47, '10-01-2021', 14700900, 'PPERY', 0.014, '+', 'http://mboum.com/'),
(48, '10-01-2021', 737090, 'UNLRF', 0.012, '+', 'http://mboum.com/'),
(49, '11-01-2021', 14259900, 'PPERY', 0.03, '-', 'http://mboum.com/'),
(50, '11-01-2021', 714978, 'UNLRF', 0.03, '-', 'http://mboum.com/'),
(51, '12-01-2021', 14431000, 'PPERY', 0.012, '+', 'http://mboum.com/'),
(52, '12-01-2021', 723557, 'UNLRF', 0.012, '+', 'http://mboum.com/'),
(53, '13-01-2020', 14301100, 'PPERY', 0.009, '-', 'http://mboum.com/'),
(54, '13-01-2020', 718492, 'UNLRF', 0.007, '-', 'http://mboum.com/'),
(55, '14-01-2021', 14158100, 'PPERY', 0.01, '-', 'http://mboum.com/'),
(56, '14-01-2021', 711307, 'UNLRF', 0.01, '-', 'http://mboum.com/'),
(57, '15-01-2021', 14243000, 'PPERY', 0.006, '+', 'http://mboum.com/'),
(58, '15-01-2021', 715575, 'UNLRF', 0.006, '+', 'http://mboum.com/'),
(100, '19-01-2021', 13873200, 'PPERY', 0.034, '-', 'http://mboum.com/'),
(101, '19-01-2021', 723244, 'UNLRF', 0.003, '+', 'http://mboum.com/'),
(102, '20-01-2021', 13874100, 'PPERY', 0, '+', 'http://mboum.com/'),
(103, '20-01-2021', 719631, 'UNLRF', 0.005, '-', 'http://mboum.com/'),
(104, '20-01-2021', 33650900, 'PIFMY', 0, '+', 'http://mboum.com/'),
(105, '21-01-2021', 14817100, 'PPERY', 0.068, '+', 'http://mboum.com/'),
(106, '21-01-2021', 716557, 'UNLRF', 0.004, '-', 'http://mboum.com/'),
(115, '24-01-2021', 733661, 'UNLRF', 0.024, '+', 'http://mboum.com/'),
(114, '24-01-2021', 14476600, 'PPERY', 0.023, '-', 'http://mboum.com/'),
(116, '26-01-2021', 14744100, 'PPERY', 0.018, '+', 'http://mboum.com/'),
(117, '26-01-2021', 733704, 'UNLRF', 0, '+', 'http://mboum.com/'),
(121, '28-01-2021', 14302300, 'PPERY', 0.03, '-', 'http://mboum.com/'),
(120, '28-01-2021', 730763, 'UNLRF', 0.004, '-', 'http://mboum.com/'),
(122, '30-01-2021', 13268100, 'PPERY', 0.072, '-', 'http://mboum.com/'),
(123, '30-01-2021', 705448, 'UNLRF', 0.035, '-', 'http://mboum.com/'),
(124, '31-01-2021', 13064200, 'PPERY', 0.015, '-', 'http://mboum.com/'),
(125, '31-01-2021', 694603, 'UNLRF', 0.015, '-', 'http://mboum.com/'),
(126, '01-02-2021', 13064200, 'PPERY', 0, '-', 'http://mboum.com/'),
(127, '01-02-2021', 694603, 'UNLRF', 0, '-', 'http://mboum.com/'),
(128, '01-02-2021', 694603, 'UNLRF', 0, '-', 'http://mboum.com/'),
(129, '02-02-2021', 13477800, 'PPERY', 0.032, '+', 'http://mboum.com/'),
(130, '02-02-2021', 13477800, 'PPERY', 0, '-', 'http://mboum.com/'),
(131, '02-02-2021', 697126, 'UNLRF', 0.004, '+', 'http://mboum.com/'),
(132, '02-02-2021', 697126, 'UNLRF', 0, '-', 'http://mboum.com/'),
(133, '24-02-2021', 12591000, 'PPERY', 0.066, '-', 'http://mboum.com/'),
(134, '24-02-2021', 12591000, 'PPERY', 0, '+', 'http://mboum.com/'),
(135, '24-02-2021', 665201, 'UNLRF', 0.046, '-', 'http://mboum.com/'),
(136, '24-02-2021', 665201, 'UNLRF', 0, '-', 'http://mboum.com/'),
(137, '01-03-2021', 676004, 'UNLRF', 0.016, '+', 'http://mboum.com/'),
(138, '01-03-2021', 12027700, 'PPERY', 0.045, '-', 'http://mboum.com/'),
(139, '02-03-2021', 12918000, 'PPERY', 0.074, '+', 'http://mboum.com/'),
(140, '02-03-2021', 12918000, 'PPERY', 0, '+', 'http://mboum.com/'),
(141, '02-03-2021', 674813, 'UNLRF', 0.002, '-', 'http://mboum.com/'),
(142, '02-03-2021', 674813, 'UNLRF', 0, '-', 'http://mboum.com/'),
(143, '16-03-2021', 13371700, 'PPERY', 0.035, '+', 'http://mboum.com/'),
(144, '16-03-2021', 13371700, 'PPERY', 0, '-', 'http://mboum.com/'),
(145, '16-03-2021', 670049, 'UNLRF', 0.007, '-', 'http://mboum.com/'),
(146, '16-03-2021', 670049, 'UNLRF', 0, '-', 'http://mboum.com/'),
(147, '19-03-2021', 13405300, 'PPERY', 0.003, '+', 'http://mboum.com/'),
(148, '19-03-2021', 13405300, 'PPERY', 0, '-', 'http://mboum.com/'),
(149, '19-03-2021', 674623, 'UNLRF', 0.007, '+', 'http://mboum.com/'),
(150, '19-03-2021', 674623, 'UNLRF', 0, '-', 'http://mboum.com/'),
(151, '22-03-2021', 13287100, 'PPERY', 0.009, '-', 'http://mboum.com/'),
(152, '22-03-2021', 13287100, 'PPERY', 0, '-', 'http://mboum.com/'),
(153, '22-03-2021', 672496, 'UNLRF', 0.003, '-', 'http://mboum.com/'),
(154, '22-03-2021', 672496, 'UNLRF', 0, '-', 'http://mboum.com/'),
(155, '25-03-2021', 13128000, 'PPERY', 0.012, '-', 'http://mboum.com/'),
(156, '25-03-2021', 13128000, 'PPERY', 0, '+', 'http://mboum.com/'),
(157, '25-03-2021', 654668, 'UNLRF', 0.027, '-', 'http://mboum.com/'),
(158, '25-03-2021', 654668, 'UNLRF', 0, '-', 'http://mboum.com/'),
(159, '19-05-2021', 11690300, 'PPERY', 0.11, '-', 'http://mboum.com/'),
(160, '19-05-2021', 11690300, 'PPERY', 0, '+', 'http://mboum.com/'),
(161, '19-05-2021', 11690300, 'PPERY', 0, '+', 'http://mboum.com/'),
(162, '19-05-2021', 545549, 'UNLRF', 0.167, '-', 'http://mboum.com/'),
(163, '19-05-2021', 11690300, 'PPERY', 0, '+', 'http://mboum.com/'),
(164, '19-05-2021', 545549, 'UNLRF', 0, '-', 'http://mboum.com/'),
(165, '19-05-2021', 545549, 'UNLRF', 0, '-', 'http://mboum.com/'),
(166, '02-06-2021', 12018200, 'PPERY', 0.028, '+', 'http://mboum.com/'),
(167, '02-06-2021', 12018200, 'PPERY', 0, '+', 'http://mboum.com/'),
(168, '02-06-2021', 567497, 'UNLRF', 0.04, '+', 'http://mboum.com/'),
(169, '02-06-2021', 567497, 'UNLRF', 0, '-', 'http://mboum.com/'),
(170, '03-06-2021', 12179100, 'PPERY', 0.013, '+', 'http://mboum.com/'),
(171, '03-06-2021', 580657, 'UNLRF', 0.023, '+', 'http://mboum.com/'),
(172, '03-06-2021', 12179100, 'PPERY', 0, '+', 'http://mboum.com/');

-- --------------------------------------------------------

--
-- Table structure for table `infak`
--

CREATE TABLE `infak` (
  `id` int(5) NOT NULL,
  `id_transaksi` varchar(12) NOT NULL,
  `penyaluran` varchar(255) NOT NULL,
  `nominal_donasi` double NOT NULL,
  `keterangan` text NOT NULL,
  `bank_tujuan` varchar(10) NOT NULL,
  `no_rek_bank` varchar(25) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `bukti_pembayaran` varchar(255) NOT NULL,
  `dikonfirmasi` tinyint(1) NOT NULL DEFAULT 0,
  `tanggal_dibuat` varchar(40) NOT NULL,
  `on_update` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `infak`
--

INSERT INTO `infak` (`id`, `id_transaksi`, `penyaluran`, `nominal_donasi`, `keterangan`, `bank_tujuan`, `no_rek_bank`, `user_id`, `bukti_pembayaran`, `dikonfirmasi`, `tanggal_dibuat`, `on_update`) VALUES
(1, '02-202081632', 'Pendidikan', 35000, 'Bismillah', 'Bank BCA', '3046101500', 'zgpEc9Q7', '8b49e66382a5319d.jpeg', 0, 'Wed Aug 05 10:07:17 GMT+07:00 2020', '13:25 12-08-2020'),
(2, '02-202080272', 'Pendidikan', 50000, '', 'Bank BCA', '3046101500', 'zgpEc9Q7', '0f0decb92dbabc20.jpg', 1, 'Fri Aug 07 09:25:06 GMT+07:00 2020', '12:51 11-08-2020');

-- --------------------------------------------------------

--
-- Table structure for table `kekayaan`
--

CREATE TABLE `kekayaan` (
  `id` int(6) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `kategori` varchar(12) NOT NULL,
  `nama_item` varchar(60) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `kuantitas` double NOT NULL,
  `waktu_kepemilikan` varchar(10) NOT NULL,
  `waktu` varchar(5) NOT NULL,
  `tanggal` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `kekayaan`
--

INSERT INTO `kekayaan` (`id`, `user_id`, `kategori`, `nama_item`, `keterangan`, `kuantitas`, `waktu_kepemilikan`, `waktu`, `tanggal`) VALUES
(7, 'zgpEc9Q7', 'Perak', 'Cincin Perak', '', 2.5, '1/6/2020', '14:28', '05-07-2020'),
(8, 'zgpEc9Q7', 'Perak', 'Gelang Perak', '', 3, '10/2/2020', '14:29', '05-07-2020'),
(9, 'zgpEc9Q7', 'Emas', 'Emas', '', 5, '23/1/2021', '03:42', '31-01-2021'),
(10, 'Wm1RnN7H', 'Emas', 'Cincin', '', 5, '1/6/2020', '09:21', '07-07-2020'),
(11, 'Wm1RnN7H', 'Emas', 'Gelang', '', 10, '7/6/2020', '09:22', '07-07-2020'),
(12, 'zgpEc9Q7', 'Saham', 'PPERY', NULL, 3, '7/6/2020', '14:28', '05-07-2020'),
(13, 'zgpEc9Q7', 'Saham', 'UNLRF', '', 45, '10/6/2020', '15:28', '07-07-2020'),
(14, 'zgpEc9Q7', 'Emas', 'Emas', '', 5, '25/1/2021', '14:28', '31-01-2021'),
(15, 'zgpEc9Q7', 'Emas', 'Emas', NULL, 5, '29/1/2021', '12:21', '31-01-2021'),
(21, 'zgpEc9Q7', 'Properti', 'Rumah', NULL, 125000000, '27/9/2020', '12:13', '01-02-2021'),
(22, 'zgpEc9Q7', 'Properti', 'Kontrakan', '', 170000000, '31/11/2020', '19:53', '01-02-2021'),
(23, '57oGZFPL', 'Emas', 'LM', 'LM 25 Gr', 25, '1/10/2019', '09:38', '02-02-2021'),
(24, '57oGZFPL', 'Emas', 'LM', '', 100, '1/4/2020', '09:40', '02-02-2021'),
(25, '9uVvVbBF', 'Emas', 'logam mulia', '50 gram', 86, '14/2/2020', '21:39', '16-03-2021'),
(26, '9uVvVbBF', 'Emas', 'logam mulia', '50 gram', 100, '14/2/2020', '21:39', '16-03-2021'),
(27, '9uVvVbBF', 'Emas', 'logam mulia', '', 100, '14/2/2020', '21:39', '16-03-2021'),
(28, '9uVvVbBF', 'Emas', 'logam mulia', 'emas', 100, '14/2/2020', '21:39', '16-03-2021'),
(29, '9uVvVbBF', 'Emas', 'ggh', 'bhh', 965, '10/2/2021', '21:40', '16-03-2021'),
(30, '57oGZFPL', 'Emas', 'Logam mulia', '', 90, '25/2/2021', '13:49', '25-03-2021'),
(31, '57oGZFPL', 'Emas', 'Logam mulia', 'emas', 90, '25/2/2021', '13:50', '25-03-2021'),
(32, '57oGZFPL', 'Emas', 'Logam mulia', 'emas', 90, '25/2/2021', '13:50', '25-03-2021'),
(33, '57oGZFPL', 'Emas', 'logam mulia', '90 gram', 90, '1/5/2020', '13:01', '03-06-2021'),
(34, '57oGZFPL', 'Emas', 'logam mulia', 'ggg', 90, '1/5/2020', '13:01', '03-06-2021');

-- --------------------------------------------------------

--
-- Table structure for table `kenaikan_nilai`
--

CREATE TABLE `kenaikan_nilai` (
  `id` int(6) NOT NULL,
  `kategori` varchar(12) NOT NULL,
  `nama_item` varchar(12) DEFAULT NULL,
  `tanggal` varchar(10) NOT NULL,
  `perubahan` float NOT NULL,
  `status` enum('+','-') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kenaikan_nilai`
--

INSERT INTO `kenaikan_nilai` (`id`, `kategori`, `nama_item`, `tanggal`, `perubahan`, `status`) VALUES
(8, 'Saham', 'UNLRF', '03-06-2021', 0.002, '-'),
(7, 'Saham', 'PPERY', '03-06-2021', 0.001, '-'),
(10, 'Emas', NULL, '03-06-2021', 0.001, '+');

-- --------------------------------------------------------

--
-- Table structure for table `keuangan`
--

CREATE TABLE `keuangan` (
  `id` int(5) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `nominal` int(11) NOT NULL,
  `jenis_pencatatan` varchar(11) NOT NULL,
  `keterangan` text NOT NULL,
  `waktu` varchar(5) NOT NULL,
  `tanggal` int(2) NOT NULL,
  `bulan` int(2) NOT NULL,
  `tahun` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `keuangan`
--

INSERT INTO `keuangan` (`id`, `user_id`, `nominal`, `jenis_pencatatan`, `keterangan`, `waktu`, `tanggal`, `bulan`, `tahun`) VALUES
(1, 'zgpEc9Q7', 50000, 'Pemasukan', '', '17:06', 9, 7, 2020),
(2, 'zgpEc9Q7', 36000, 'Pengeluaran', '', '17:07', 9, 7, 2020),
(3, 'zgpEc9Q7', 3500, 'Pengeluaran', '', '08:18', 10, 6, 2020),
(4, 'zgpEc9Q7', 500000, 'Pemasukan', '', '08:27', 10, 6, 2020),
(5, 'zgpEc9Q7', 1000, 'Pemasukan', '', '08:29', 10, 6, 2020),
(6, 'zgpEc9Q7', 30000, 'Pengeluaran', '', '08:30', 10, 7, 2020),
(7, 'zgpEc9Q7', 70000, 'Pemasukan', '', '08:42', 10, 7, 2020),
(8, 'zgpEc9Q7', 3500, 'Pemasukan', '', '08:44', 10, 7, 2020),
(9, 'zgpEc9Q7', 50000, 'Pengeluaran', 'Beli Pulsa', '08:44', 10, 7, 2020),
(10, 'zgpEc9Q7', 36500, 'Pemasukan', '', '10:30', 10, 7, 2020),
(11, 'zgpEc9Q7', 50000, 'Pengeluaran', '', '10:34', 10, 7, 2020),
(12, 'zgpEc9Q7', 60000, 'Pemasukan', '', '21:45', 10, 7, 2020),
(13, 'zgpEc9Q7', 15000, 'Pengeluaran', 'Beli Bakso', '22:00', 10, 7, 2020),
(14, 'zgpEc9Q7', 35000, 'Pemasukan', '', '15:07', 13, 7, 2020),
(15, 'zgpEc9Q7', 10000, 'Pemasukan', '', '15:08', 13, 7, 2020),
(16, 'zgpEc9Q7', 7000, 'Pemasukan', '', '15:08', 13, 7, 2020),
(17, 'zgpEc9Q7', 5000, 'Pengeluaran', 'Beli Bubur', '15:09', 13, 7, 2020),
(18, 'zgpEc9Q7', 22000, 'Pengeluaran', 'Kopi', '19:19', 21, 7, 2020),
(19, 'zgpEc9Q7', 35000, 'Pemasukan', '', '20:42', 21, 7, 2020),
(24, 'zgpEc9Q7', 10000, 'Pemasukan', '', '03:23', 30, 7, 2020),
(25, '57oGZFPL', 5000000, 'Pemasukan', 'gaji', '16:43', 2, 2, 2021),
(26, '57oGZFPL', 1000000, 'Pengeluaran', 'listrik', '16:43', 2, 2, 2021),
(27, '9uVvVbBF', 30000000, 'Pemasukan', 'gaji', '21:36', 16, 3, 2021),
(28, '9uVvVbBF', 12000000, 'Pengeluaran', 'cicilan', '21:37', 16, 3, 2021);

-- --------------------------------------------------------

--
-- Table structure for table `nama_saham`
--

CREATE TABLE `nama_saham` (
  `id` int(6) NOT NULL,
  `kode` varchar(25) NOT NULL,
  `nama` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `nama_saham`
--

INSERT INTO `nama_saham` (`id`, `kode`, `nama`) VALUES
(1, 'PPERY', 'BANK MANDIRI (PERSERO) TBK'),
(2, 'UNLRF', 'UNILEVER INDONESIA');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(5) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `notifikasi` text NOT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `user_id`, `notifikasi`, `dibaca`, `date_created`) VALUES
(1, 'zgpEc9Q7', 'Pembayaran Infak 02-202083379 sedang di proses, silahkan menunggu', 1, '2020-08-05 03:07:34'),
(2, 'zgpEc9Q7', 'Pembayaran Zakat 01-202040190 sedang di proses, silahkan menunggu', 1, '2020-08-05 03:08:39'),
(3, 'zgpEc9Q7', 'Pembayaran Zakat 01-202078314 sedang di proses, silahkan menunggu', 1, '2020-08-06 01:03:49'),
(4, 'zgpEc9Q7', 'Pembayaran Zakat 01-202037208 sedang di proses, silahkan menunggu', 1, '2020-08-07 02:24:12'),
(5, 'zgpEc9Q7', 'Pembayaran Infak 02-202080272 sedang di proses, silahkan menunggu', 1, '2020-08-07 02:25:17'),
(6, 'zgpEc9Q7', 'Pembayaran Infak 02-202081632 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2020-08-10 08:33:45'),
(7, 'zgpEc9Q7', 'Pembayaran Zakat 01-202040190 telah di tolak.', 1, '2020-08-10 12:17:08'),
(8, 'zgpEc9Q7', 'Pembayaran Zakat 01-202040190 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2020-08-10 12:17:16'),
(9, 'zgpEc9Q7', 'Pembayaran Zakat 01-202040190 telah di tolak.', 1, '2020-08-10 12:17:32'),
(10, 'zgpEc9Q7', 'Pembayaran Zakat 01-202040190 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2020-08-11 05:41:01'),
(11, 'zgpEc9Q7', 'Pembayaran Infak 02-202080272 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2020-08-11 05:51:14'),
(12, 'zgpEc9Q7', 'Pembayaran Infak 02-202081632 telah di tolak.', 1, '2020-08-11 06:02:50'),
(14, 'zgpEc9Q7', 'Pembayaran Infak 02-202081632 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2020-08-12 06:25:55'),
(27, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.664.062,50', 1, '2021-02-02 09:35:11'),
(28, '57oGZFPL', 'Pembayaran Zakat 01-202134290 sedang di proses, silahkan menunggu', 1, '2021-02-02 09:45:49'),
(29, '57oGZFPL', 'Pembayaran Zakat 01-202193163 sedang di proses, silahkan menunggu', 1, '2021-02-02 09:47:47'),
(30, '57oGZFPL', 'Pembayaran Zakat 01-202193163 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2021-02-02 10:22:42'),
(31, '57oGZFPL', 'Pembayaran Zakat 01-202193163 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2021-02-02 10:22:59'),
(32, '57oGZFPL', 'Pembayaran Zakat 01-202134290 telah di konfirmasi, silahkan cek bukti transaksi untuk rincian', 1, '2021-02-02 10:23:17'),
(33, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.628.125,00', 1, '2021-02-24 08:05:10'),
(34, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.595.312,50', 1, '2021-03-01 10:45:02'),
(35, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.600.000,00', 1, '2021-03-02 04:59:59'),
(36, 'zgpEc9Q7', 'Pembayaran zakat untuk tabungan saham kamu di BANK MANDIRI (PERSERO) TBK diperkirakan pada tanggal 03-03-2021 membayar zakat senilai 3.875.400,00', 1, '2021-03-02 05:01:26'),
(37, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.617.187,50', 1, '2021-03-16 08:39:21'),
(38, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.609.375,00', 1, '2021-03-19 08:32:04'),
(39, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 28.202.125,00', 1, '2021-03-19 08:32:04'),
(40, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.603.125,00', 1, '2021-03-22 07:19:23'),
(41, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 28.134.575,00', 1, '2021-03-22 07:19:23'),
(42, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 01-10-2021 senilai 2.587.500,00', 1, '2021-03-25 06:46:25'),
(43, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 27.965.700,00', 0, '2021-03-25 06:46:26'),
(44, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 25-02-2022 senilai 8.507.312,50', 1, '2021-05-19 03:26:35'),
(45, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 29.097.162,50', 0, '2021-05-19 03:26:35'),
(46, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 25-02-2022 senilai 8.704.812,50', 1, '2021-06-02 12:25:31'),
(47, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 29.772.662,50', 0, '2021-06-02 12:25:31'),
(48, '57oGZFPL', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 25-02-2022 senilai 8.601.125,00', 1, '2021-06-03 05:56:43'),
(49, '9uVvVbBF', 'Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal 10-02-2022 senilai 29.418.025,00', 0, '2021-06-03 05:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `perkiraan_zakat`
--

CREATE TABLE `perkiraan_zakat` (
  `id` int(11) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `kategori` varchar(12) NOT NULL,
  `tanggal` varchar(10) NOT NULL,
  `tgl_zakat` varchar(10) DEFAULT NULL,
  `zakat` float DEFAULT NULL,
  `item_id` int(6) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `perkiraan_zakat`
--

INSERT INTO `perkiraan_zakat` (`id`, `user_id`, `kategori`, `tanggal`, `tgl_zakat`, `zakat`, `item_id`) VALUES
(6, 'zgpEc9Q7', 'Saham', '03-06-2021', NULL, NULL, 13),
(8, 'zgpEc9Q7', 'Saham', '03-06-2021', NULL, NULL, 12),
(9, 'zgpEc9Q7', 'Emas', '03-06-2021', NULL, NULL, NULL),
(10, 'Wm1RnN7H', 'Emas', '03-06-2021', NULL, NULL, NULL),
(11, 'zgpEc9Q7', 'Perak', '03-06-2021', NULL, NULL, NULL),
(12, 'zgpEc9Q7', 'Properti', '01-02-2021', '27-09-2021', 3125000, 21),
(13, 'zgpEc9Q7', 'Properti', '01-02-2021', '01-12-2021', 4250000, 22),
(14, '57oGZFPL', 'Emas', '03-06-2021', '25-02-2022', 8601120, NULL),
(15, '9uVvVbBF', 'Emas', '03-06-2021', '10-02-2022', 29418000, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(4) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `sapaan` varchar(8) NOT NULL,
  `nama_lengkap` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `password` varchar(40) NOT NULL,
  `no_telp` varchar(15) NOT NULL,
  `tgl_cek` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `user_id`, `sapaan`, `nama_lengkap`, `email`, `password`, `no_telp`, `tgl_cek`) VALUES
(1, 'zgpEc9Q7', 'Bapak', 'Firman Ilham Laksan', 'firmanilham@fircom.com', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', '085777068022', ''),
(2, 'Wm1RnN7H', 'Bapak', 'Budi Utomo', 'budi@yahoo.com', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', '085477889966', ''),
(3, '57oGZFPL', 'Ibu', 'Feni', 'fenandria@yahoo.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', '087781941736', NULL),
(4, 'wyfH3RhI', 'Ibu', 'dewi', 'gggg', 'dd06edfd214747367a07be88a2be943140e87f71', '08', NULL),
(5, 'Sy3De81v', 'Bapak', 'Agus Tudanto', 'agus@gmail.com', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', '0811234567', NULL),
(6, '9uVvVbBF', 'Bapak', 'm.hafiz.alfatih@gmail.com', 'm.hafiz.alfatih@gmail.com', '03c3f5997537ee55454b46aaa11a641a48bec1ba', '087781941736', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_admin`
--

CREATE TABLE `whatsapp_admin` (
  `id` int(1) NOT NULL,
  `user` varchar(25) NOT NULL,
  `number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `whatsapp_admin`
--

INSERT INTO `whatsapp_admin` (`id`, `user`, `number`) VALUES
(1, 'admin', '6285777068011');

-- --------------------------------------------------------

--
-- Table structure for table `zakat`
--

CREATE TABLE `zakat` (
  `id` int(6) NOT NULL,
  `id_transaksi` varchar(12) NOT NULL,
  `jenis_zakat` varchar(17) NOT NULL,
  `nominal` double NOT NULL,
  `bank_tujuan` varchar(10) NOT NULL,
  `no_rek_bank` varchar(25) NOT NULL,
  `ket_zakat` varchar(255) NOT NULL,
  `user_id` varchar(8) NOT NULL,
  `bukti_pembayaran` varchar(255) NOT NULL,
  `tanggal_dibuat` varchar(40) NOT NULL,
  `dikonfirmasi` tinyint(1) NOT NULL DEFAULT 0,
  `on_update` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `zakat`
--

INSERT INTO `zakat` (`id`, `id_transaksi`, `jenis_zakat`, `nominal`, `bank_tujuan`, `no_rek_bank`, `ket_zakat`, `user_id`, `bukti_pembayaran`, `tanggal_dibuat`, `dikonfirmasi`, `on_update`) VALUES
(1, '01-202040190', 'Zakat Penghasilan', 120000, 'Bank BCA', '3046101500', '-', 'zgpEc9Q7', '59cca880f696ec95.jpeg', 'Wed Aug 05 10:08:30 GMT+07:00 2020', 1, '12:41 11-08-2020'),
(2, '01-202078314', 'Zakat Penghasilan', 189000, 'Bank BCA', '3046101500', '-', 'zgpEc9Q7', 'bb1551c5d68a6cd2.jpg', 'Thu Aug 06 08:01:23 GMT+07:00 2020', 0, '14:06 10-08-2020'),
(3, '01-202037208', 'Zakat Penghasilan', 150000, 'Bank BCA', '3046101500', '-', 'zgpEc9Q7', 'd8c617e4ca6f1705.jpg', 'Fri Aug 07 09:23:54 GMT+07:00 2020', 1, '13:47 10-08-2020'),
(4, '01-202134290', 'Zakat Penghasilan', 500000, 'Bank BCA', '3046101500', '-', '57oGZFPL', '6e80ed2f57bc3d24.jpeg', 'Tue Feb 02 16:44:51 GMT+07:00 2021', 1, '17:23 02-02-2021'),
(5, '01-202193163', 'Zakat Penghasilan', 5000000, 'Bank BCA', '3046101500', '-', '57oGZFPL', '9ebf378aabd0d661.jpeg', 'Tue Feb 02 16:46:35 GMT+07:00 2021', 1, '17:22 02-02-2021');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `harga_emas`
--
ALTER TABLE `harga_emas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `harga_saham`
--
ALTER TABLE `harga_saham`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `infak`
--
ALTER TABLE `infak`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kekayaan`
--
ALTER TABLE `kekayaan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kenaikan_nilai`
--
ALTER TABLE `kenaikan_nilai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keuangan`
--
ALTER TABLE `keuangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nama_saham`
--
ALTER TABLE `nama_saham`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `perkiraan_zakat`
--
ALTER TABLE `perkiraan_zakat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_admin`
--
ALTER TABLE `whatsapp_admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zakat`
--
ALTER TABLE `zakat`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `harga_emas`
--
ALTER TABLE `harga_emas`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `harga_saham`
--
ALTER TABLE `harga_saham`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `infak`
--
ALTER TABLE `infak`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kekayaan`
--
ALTER TABLE `kekayaan`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `kenaikan_nilai`
--
ALTER TABLE `kenaikan_nilai`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `keuangan`
--
ALTER TABLE `keuangan`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `nama_saham`
--
ALTER TABLE `nama_saham`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `perkiraan_zakat`
--
ALTER TABLE `perkiraan_zakat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `whatsapp_admin`
--
ALTER TABLE `whatsapp_admin`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `zakat`
--
ALTER TABLE `zakat`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
