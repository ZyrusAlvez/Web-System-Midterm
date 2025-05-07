-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2025 at 07:58 PM
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
-- Database: `mydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `product_id`, `created_at`, `quantity`, `status`) VALUES
(26, 24, 29, '2025-03-31 16:38:17', 1, 'completed'),
(27, 24, 29, '2025-03-31 16:42:22', 1, 'completed'),
(28, 19, 31, '2025-04-02 18:00:07', 3, 'completed'),
(29, 19, 31, '2025-04-02 18:01:05', 4, 'pending'),
(30, 19, 31, '2025-04-02 18:01:23', 2, 'completed'),
(32, 31, 18, '2025-04-04 01:08:08', 1, 'pending'),
(34, 31, 32, '2025-04-04 01:27:20', 3, 'cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` decimal(3,2) DEFAULT 0.00 CHECK (`rate` between 0 and 5),
  `description` text DEFAULT NULL,
  `image` blob DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `quantity`, `rate`, `description`, `image`, `created_at`, `category`) VALUES
(10, 'Nike T-shirt', 99.00, 54, 0.00, 'for men Summer Breathable Sweat Absorption Trend Sportswear t-shirt men', 0x75706c6f6164732f363765383533613431356638352e706e67, '2025-03-29 20:10:12', 'men\'s apparel'),
(11, 'ABT Pro Club Shirt', 164.00, 423, 0.00, 'American Size Oversize Plain Shirts Organic Men and Women Clothes Cotton Fabric', 0x75706c6f6164732f363765383534633832366163382e706e67, '2025-03-29 20:15:04', 'men\'s apparel'),
(12, 'Oversized Streetstyle T shirt ', 94.00, 344, 0.00, 'Mens Urban Fashion unisex trendy white OST1', 0x75706c6f6164732f363765383535336463363730612e706e67, '2025-03-29 20:17:01', 'men\'s apparel'),
(13, 'MR.COTTON POWER MONEY', 295.00, 874, 0.00, 'Design Original Basic Street Oversize Pro club Shirt Cotton Premium T-shirt For Men Menswear Top', 0x75706c6f6164732f363765383535396132363537312e706e67, '2025-03-29 20:18:34', 'men\'s apparel'),
(14, 'ABT Oversized Shirts NBA', 197.00, 54, 0.00, 'Bootleg Pro Club Unisex Men and Women T shirt American Size Cotton', 0x75706c6f6164732f363765383535656364323936322e706e67, '2025-03-29 20:19:56', 'men\'s apparel'),
(15, 'SIMPLE RUSH 2025', 63.00, 543, 0.00, 'NEW drifit t-shirt Unisex KHAKI color round neck T-shirt', 0x75706c6f6164732f363765383536316334616330622e706e67, '2025-03-29 20:20:44', 'men\'s apparel'),
(16, 'Simple Vans Cotton', 114.00, 434, 0.00, 'Tshirt Classic Logo Trending Tees Unisex tshirt for men summer Cotton tshirt', 0x75706c6f6164732f363765383536356331383834392e706e67, '2025-03-29 20:21:48', 'men\'s apparel'),
(18, 'CHALLIS TERNO BLOUSE AND TOKONG', 220.00, 433, 0.00, 'SQUAREPANTS FOR WOMEN UP TO XL\r\n\r\n', 0x75706c6f6164732f363765383537366261646639622e706e67, '2025-03-29 20:26:19', 'women\'s apparel'),
(19, 'V neck oversize knitted Top', 97.00, 324, 0.00, 'Hot Sale Basic V neck oversize knitted Top women apparel Blouse BB', 0x75706c6f6164732f363765383537616432646235372e706e67, '2025-03-29 20:27:25', 'women\'s apparel'),
(20, 'Plain Round Neck T-shirt', 45.99, 2345, 0.00, 'Women’s Basic Plain Round Neck T-shirt(Direct Supplier)', 0x75706c6f6164732f363765383538343437333165622e706e67, '2025-03-29 20:28:54', 'women\'s apparel'),
(21, ' Luncheon Meat', 231.00, 234, 0.00, '12pcs 198g luncheon meat buy 2 get 4 luncheon meat lower-salt Mr. Squirrel luncheon meat', 0x75706c6f6164732f363765383538666430353532322e706e67, '2025-03-29 20:33:01', 'groceries'),
(22, 'GROCERY PACKAGE WORTH 450', 450.00, 3243, 0.00, 'Worth the package...or not', 0x75706c6f6164732f363765383539386465333031662e706e67, '2025-03-29 20:35:25', 'groceries'),
(23, 'Fried Peanuts', 226.00, 324, 0.00, 'S&R Premium Fried Peanuts Super Garlic 1kg', 0x75706c6f6164732f363765383561646563626563362e706e67, '2025-03-29 20:41:02', 'groceries'),
(24, 'JC Magnetic Phone Grip', 1045.00, 67, 0.00, 'Bluetooth Remote Shutter Desktop Stand for Apple 16 15 14 13 12 Pro Max for Mag-Safe Phone Case Selfie Vlog', 0x75706c6f6164732f363765383562656331623062662e706e67, '2025-03-29 20:45:32', 'mobile & gadgets'),
(26, 'HODEKT Electric Kettle', 299.00, 78, 0.00, '1.8L/2.3L Capacity Electric Heater Stainless Steel Material Prevent burns', 0x75706c6f6164732f363765383563393230636339622e706e67, '2025-03-29 20:48:18', 'home appliances'),
(27, 'Electric portable breakfast machine', 217.00, 344, 0.00, 'One key high appearance level automatic power off egg boiling ma', 0x75706c6f6164732f363765383564353335323736302e706e67, '2025-03-29 20:51:31', 'home appliances'),
(28, 'Mini Rice Cooker', 419.00, 344, 0.00, 'Stainless steel Mini Rice Cooker Small 1.5/2L Multi-function Cooker with steamer Non-stick Inner pot', 0x75706c6f6164732f363765383564393231313133612e706e67, '2025-03-29 20:52:34', 'home appliances'),
(29, 'Uric Acid Formula', 193.00, 304, 0.00, 'Uric Acid Cleanse Reduces Acidity - Pure Green Coffee Bean - Pearl Grass - Vitamin B-6 - Health Support', 0x75706c6f6164732f363765383564646433353934632e706e67, '2025-03-29 20:53:49', 'health & personal care'),
(30, 'Bunkka Cranberry Extract', 198.00, 342, 0.00, 'Concentrate Capsules Support Urinary Tract Healthy Bladder Supplement', 0x75706c6f6164732f363765383565323530396636372e706e67, '2025-03-29 20:54:45', 'health & personal care'),
(31, 'First Aids Kit Outdoor', 780.00, 2440, 0.00, '250PCS First Aids Kit Outdoor Camping tourniquet Survival Set Travel Multifunction First A', 0x75706c6f6164732f363765383565356565643338362e706e67, '2025-03-29 20:55:58', 'sports & travel'),
(32, 'Compression Pants', 99.00, 342, 0.00, 'Compression Cool Dry Sports Tights Pants Baselyer Running Leggings Basketball Yoga Men and', 0x75706c6f6164732f363765383565393330656362612e706e67, '2025-03-29 20:56:51', 'sports & travel'),
(33, 'Knee Pad', 76.00, 234, 0.00, '1 pcs Knee Pad Support/Equipment For Sports basketball volleyball running For Men and Women', 0x75706c6f6164732f363765383565643439356630662e706e67, '2025-03-29 20:57:30', 'sports & travel');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `address`, `email`, `password`, `account_type`) VALUES
(18, 'dwadawd', 'awdawdaw', 'zyy', '123', '1'),
(19, 'zyy pogi', 'wadwd', 'zyyadmin', '123', '1'),
(22, 'kel', 'kel', 'kel', 'kel', '2'),
(23, 'kol', 'kol', 'kol', 'kol', '2'),
(24, 'lala', 'lala', 'lala', 'lala', '2'),
(25, 'bago', 'bago', 'bago', 'bago', '2'),
(26, 'luam', 'luam', 'luam', 'luam', '2'),
(31, 'user', 'user', 'user', 'user', '0');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
