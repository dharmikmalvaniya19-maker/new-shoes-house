-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 24, 2025 at 12:01 PM
-- Server version: 5.7.24
-- PHP Version: 7.2.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoes_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'dharmik', '2ecf77b3881c4f4fb7009282a76aa88b'),
(3, 'divy prajapati', '59f8a17fa14d1d5bef01f0f0f025474c');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Men'),
(2, 'Women'),
(3, 'Kids');

-- --------------------------------------------------------

--
-- Table structure for table `contact_message`
--

DROP TABLE IF EXISTS `contact_message`;
CREATE TABLE IF NOT EXISTS `contact_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `contact_message`
--

INSERT INTO `contact_message` (`id`, `name`, `email`, `message`, `submitted_at`) VALUES
(1, 'Arjun Mehra', 'arjun.mehra@example.com', 'Yo, your shoe collection is dope! But Iâ€™m confused about the sizing chart for the sneakers. Can you guys add more details or maybe a guide for picking the right size?', '2025-09-21 07:22:31'),
(2, 'meera chopra92', 'meera$chopra92@gmail.com', 'I tried ordering a pair of boots, but the payment page crashed. Any chance you can fix this? Need those shoes ASAP for a trip!', '2025-09-21 07:24:33'),
(3, 'kevat pavan b', 'pavan@gmail.com', 'Hi, I ordered some white sneakers last week, but the tracking link isnâ€™t working. Can someone check whatâ€™s up with my order?', '2025-09-21 07:29:43');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_name` varchar(255) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_zip` varchar(20) NOT NULL,
  `shipping_country` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `order_status` varchar(50) DEFAULT 'Pending',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_name`, `shipping_address`, `shipping_city`, `shipping_zip`, `shipping_country`, `payment_method`, `order_status`, `order_date`) VALUES
(1, 2, '8999.00', 'parmar rohit', 'near manav mandir', 'surendranagar', '363001', 'India', 'cod', 'Completed', '2025-09-16 04:14:25'),
(2, 2, '19997.00', 'parmar rohit', 'near manav mandir', 'surendranagar', '363001', 'India', 'cod', 'Pending', '2025-09-17 03:22:45'),
(3, 1, '29447.00', 'kevat pavan b', 'l', 'k;llk', '3', 'j', 'cod', 'Delivered', '2025-09-19 06:03:13'),
(4, 4, '135491.00', 'rajdeep_1995', 'near junction road', 'surendranagar', '363001', 'india', 'cod', 'Pending', '2025-09-23 05:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name_at_purchase` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `image_at_purchase` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name_at_purchase`, `quantity`, `price_at_purchase`, `image_at_purchase`) VALUES
(1, 1, 32, 'ASICS Gel-Noosa Tri 15 Gs Trainers', 1, '8999.00', '1010.jpg'),
(2, 2, 2, 'Nike Air Max 2', 1, '3499.00', '02.jpeg'),
(3, 2, 14, 'Japan S White/Blue', 1, '6499.00', '02.webp'),
(4, 2, 18, 'Rs-x Soft WNS', 1, '9999.00', '05.jpg'),
(5, 3, 15, 'Gel-renma Indoor', 2, '9999.00', '0003.webp'),
(6, 3, 19, 'Nike Women Casual', 1, '9449.00', '06.jpg'),
(7, 4, 35, 'Nike Air Force 1', 1, '14999.00', 'AIR+FORCE white.jpg'),
(8, 4, 36, 'Air Jordan I High G', 1, '17999.00', 'jorder01.jpg'),
(9, 4, 37, 'Air Jordan 1 Brooklyn Low', 6, '14999.00', 'nike.jpg'),
(10, 4, 38, 'Air Jordan 4 RM', 1, '12499.00', 'jordan01.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `brand_name` varchar(255) NOT NULL,
  `tag` varchar(50) DEFAULT 'all',
  `stocks` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_category` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `price`, `description`, `image`, `created_at`, `brand_name`, `tag`, `stocks`, `category_id`) VALUES
(1, '9999.00', 'The Men\'s Nike Air Zoom Running Shoes offer lightweight breathability with a mesh upper. Air Zoom cushioning ensures responsive support for running or gym workouts. Perfect for athletes seeking style and performance at a great price.', 'c-13.jpg', '2025-09-12 14:39:42', 'Nike Air Zoom', 'best-seller', 24, 1),
(2, '3499.00', 'The Men\'s Nike Air Max 270 combines style and performance with a breathable mesh upper. Its signature Air Max unit provides lightweight cushioning for all-day comfort. Perfect for running or casual wear in bold black and red.\r\n\r\n', '02.jpeg', '2025-09-12 14:46:11', 'Nike Air Max 2', 'sports', 42, 1),
(3, '3999.00', 'Men\'s Adidas Ultraboost 22 delivers superior energy return with Boost midsole technology. The breathable Primeknit upper ensures a snug, adaptive fit for intense workouts. Perfect for running or gym sessions in sleek navy blue.', 'c-1.jpg', '2025-09-12 14:49:12', 'Adidas Ultraboost 22', 'sports', 33, 1),
(4, '4999.00', 'One Shoe, Endless Possibilities: Whether youâ€™re into casual flip flops for man or formal dressers, these sneakers merge everyday ease with sharp style. Your ideal upgrade from formal shoes to streetwear.', '04.webp', '2025-09-12 14:53:59', 'Gazelle mesa brown', 'casual', 63, 1),
(5, '7999.00', 'These styles are supplied by a premium and authenticated sneaker marketplace. Stocking only the most sought-after footwear, they source and curate some of the most hard to find sneakers from around the world.', '05.webp', '2025-09-12 15:05:58', 'Air pegasus 83', 'casual', 21, 1),
(6, '2499.00', 'Adidas uses masterful technology to design footwear for the open-air-loving and the sports-driven young so that they get the comfort level which unshackles all agility. ', '06.webp', '2025-09-12 15:09:57', 'Adidas Galba', 'sports', 72, 1),
(7, '3999.00', 'Step into timeless style with the PUMA Club II Era â€” where classic design meets modern flair. With a simple silhouette, these sneakers are perfect for both the court and the streets. Comfortable, versatile, and always on point, the Club II Era keeps you ahead of the game. ', '07.jpg', '2025-09-12 15:15:05', 'Unisex-Adult', 'casual', 42, 1),
(8, '3499.00', 'Step into the future with PWRFRAME. This innovative style fuses street and tech, standing out with a progressive design and futuristic feel. Originally developed for the football pitch, our PWRFRAME technology features a continuous ring of support from heel to midfoot Ã¢â‚¬â€œ providing optimal comfort in this cutting-edge kick.', '08.jpg', '2025-09-12 15:17:20', 'Unisex Pwrframe', 'sports', 33, 1),
(9, '6899.00', 'This all new version of the popular GEL-Contend shoe is designed to provide ASICS hallmark performance through exceptional cushioning and great fit - ideal for entry-level or low mileage runners.', '09.jpg', '2025-09-12 15:20:26', 'Gel-Contend', 'sports', 41, 1),
(10, '3799.00', 'Designed for field sports or outdoor training, the GEL-COVER shoe has been formulated with a durable construction and advanced stability properties..This outdoor sports shoe features a stable upper construction that\'s constructed with synthetic leather overlays and reinforcement panels at the sides for better midfoot support', '10.jpg', '2025-09-12 15:22:45', 'Gel-Cover Cricket Shoe', 'casual', 23, 1),
(11, '13999.00', 'The JAPAN S shoes are based on one of our throwback offerings from 1981. This shoe features a court-inspired toe box and classic colorways that take your mind and body to new heights. Updated with a lightweight design and a modified cupsole, this shoe is made for comfort', 'c-15.jpeg', '2025-09-12 15:24:42', 'ASICS Men Lace Up', 'best-seller', 19, 1),
(12, '5999.00', ' Crafted with fine technology and futuristic design, PUMA shoe is surely here to uplift your style and track games. Kick off on street and field in this shoe from the worldâ€™s leading and much loved sports brand, PUMA.', '12.jpg', '2025-09-12 15:30:34', 'Cabana Retroflex Sneaker', 'casual', 56, 1),
(13, '12999.00', 'The ASICS Gel-Nimbus 22 Womenâ€™s Running Shoe delivers exceptional comfort, support, and durability for long-distance runners and daily training. Featuring GEL cushioning in the forefoot and rearfoot, this shoe absorbs shock on impact and toe-off for a smooth, responsive ride. The FLYTEFOAM midsole technology offers lightweight support, while the engineered mesh upper provides superior breathability and a flexible fit.', '0001.webp', '2025-09-14 02:56:09', 'Gel-Nimbus 22', 'best-seller', 43, 2),
(14, '6499.00', 'The JAPAN S shoes are based on one of our throwback offerings from 1981. This shoe features a court-inspired toe box and classic colorways that take your mind and body to new heights. Updated with a lightweight design and a modified cupsole, this shoe is made for comfort. It\'s also paired with nostalgic branding, like the ASICS Stripes on the quarter-panels.', '02.webp', '2025-09-14 03:02:10', 'Japan S White/Blue', 'casual', 61, 2),
(15, '9999.00', 'The GEL-RENMAâ„¢ shoe is designed for pickleball players looking to take their game to the next level. When you\'re performing multi-directional movements, you need a shoe that will help increase your stability and flexibility.', '0003.webp', '2025-09-14 03:05:31', 'Gel-renma Indoor', 'sports', 78, 2),
(16, '3499.00', 'Built for movement and made to keep up, this pair is all about the essentials done right. The lightweight design and low boot cut offer freedom with every stride, while the breathable upper makes sure you stay cool through the daily miles. Whether itâ€™s the morning jog or a city walk, this is the kind of comfort that lasts. ', '04.jpg', '2025-09-14 03:12:17', 'Unisex-Adult Skyvolt', 'best-seller', 62, 2),
(17, '6799.00', 'Named for the woman-specific double X chromosome DNA sequence, the Run XX NITROâ„¢ stands behind PUMAâ€™s RUN FOR HER motto with a progressive, female-specific design. Featuring an updated fit that hugs a womanâ€™s foot at the heel, instep, and arch, the running shoeâ€™s new, firmer durometer NITROâ„¢ foam midsole is the perfect balance of durable and lightweight.', 'aa.jpg', '2025-09-14 03:16:52', 'Run XX Nitro 2', 'sports', 42, 2),
(18, '9999.00', 'Geek out in style with PUMA\'s latest innovation in footwear designed for sneaker heads. These cutting-edge shoes combine retro style with modern technology, featuring a bulky silhouette and classic colorways. These kicks feature a textile upper, synthetic leather overlays, and a webbing tongue puller for easy on/off. With a cushioned midsole and durable rubber outsole, these sneakers will keep you looking cool and feeling comfortable every step of the way. ', '05.jpg', '2025-09-14 03:20:23', 'Rs-x Soft WNS', 'casual', 51, 2),
(19, '9449.00', 'The NIKE TC 7900 is a versatile and stylish training shoe designed for women who seek both performance and comfort. Featuring a lightweight mesh upper for breathability, it ensures your feet stay cool during intense workouts. The cushioned midsole provides excellent support and shock absorption, making it ideal for various training activities, from running to gym sessions. The rubber outsole offers superior traction, enhancing stability on different surfaces.', '06.jpg', '2025-09-14 03:23:02', 'Nike Women Casual', 'casual', 87, 2),
(20, '10499.00', 'Designed for comfortable wear for sports and street style, NIKE is always fun to wear. Upgrade in style with a wide range from the worldâ€™s leading and much-loved sports brand, NIKE.\r\n\r\n', '0007.jpg', '2025-09-14 03:26:18', 'Nike W V2k Run', 'sports', 65, 2),
(21, '5999.00', 'Every run is a journey of discovery. The Cloudfoam midsole cushions your stride to keep you comfortable as you build endurance while a durable textile upper offers an all-around supportive feel.', '008.jpg', '2025-09-14 03:33:56', 'Adidas galaxy 7 W', 'sports', 36, 2),
(22, '2499.00', 'Inspired by the adidas archives, these shoes are an everyday essential that bridge the gap between style and comfort. The synthetic upper is built to keep you comfortable all day. A durable cupsole provides support and cushioning for your stride.', '0009.jpeg', '2025-09-14 03:36:44', 'adidas Lace Up', 'casual', 73, 2),
(23, '3499.00', 'This Running Shoe is built for record-breaking speed and delivers ultra-lightweight support.', '01.jpg', '2025-09-14 03:42:15', 'Nike Unisex Sports', 'sports', 64, 3),
(24, '2699.00', 'Nike Footwear are made with premium quality material and gives ultimate comfort.\r\n', '02.jpg', '2025-09-14 03:47:31', 'Nike Mesh', 'casual', 61, 3),
(25, '2599.00', 'This colourful little shoe with sublimation upper , printed pop 3 stripes on quarter that makes them easy to get on to wiggly little feet. Camoflauge upper pattern with an overlay of gradient on the mesh that makes the shoe look unique and detailed. A bright and flat logo helps bring a contrasting look.', '03.jpeg', '2025-09-14 03:50:55', 'Adidas Essento 2.0 K, CORE Black', 'casual', 43, 3),
(26, '1999.00', 'This colourful little shoe with sublimation upper , printed pop 3 stripes on quarter that makes them easy to get on to wiggly little feet. Camoflauge upper pattern with an overlay of gradient on the mesh that makes the shoe look unique and detailed. A bright and flat logo helps bring a contrasting look. It also radiates an energetic vibe.', '004.webp', '2025-09-14 03:53:41', 'Adidas Dectron 1.0 K, TECH Indigo', 'sports', 52, 3),
(27, '2799.00', 'Ring in the new season with Style with a pair of PUMA shoes perfect to make this summer, better !', '0005.jpg', '2025-09-14 03:56:23', 'Puma Unisex Kid Punch Comfort', 'casual', 43, 3),
(28, '1499.00', 'Stand-out and dance to the style tunes with the PUMA Shoes.DETAILS Heel type: Flat Shoe width: Regular fit Synthetic Upper provides durabilty Low boot Construction & lace closure gives an optimal fit PUMA Branding gives great design elevation Rubber Outsole for better traction', '006.jpg', '2025-09-14 04:00:48', 'Puma Unisex Racer Junior V1', 'sports', 95, 3),
(29, '2999.00', 'Crafted with fine technology and futuristic design, PUMA shoe is surely here to uplift your style and track games. Kick off on street and field in this shoe from the worldâ€™s leading and much loved sports brand, PUMA.', '007.jpg', '2025-09-14 04:03:50', 'Puma Softride', 'best-seller', 65, 3),
(30, '2499.00', 'Inspired by our GT running series, the CONTEND 4B + shoe is a multi-functional style thatâ€™s engineered for everyday use. Featuring a comfortable fit in the forefoot, this shoe is complemented with stitched down overlays to improve support in the upper.', '0808.jpg', '2025-09-14 04:05:32', 'ASICS Contend 4B', 'best-seller', 85, 3),
(31, '7999.00', 'Our approach for the GT-1000â„¢ 12 KIDS shoe was to modernize the shape and make it more comfortable. It\'s designed for an active lifestyle and is functional for running or wearing it to the gym.  The shoe is reimagined with a soft mesh upper, a new heel shape, and a more comfortable sockliner. ', '0909.jpg', '2025-09-14 04:09:29', 'ASICS Gt-1000 12 Gs', 'sports', 73, 3),
(32, '8999.00', 'The GEL-NOOSA TRI 15 GS (elementary school) is inspired by the design of our flagship triathlon shoe and features the same bright colour scheme, accompanied by technical style. With a mesh upper that increases air circulation and keeps feet cool, this shoe is combined with a range of eye-catching \"NOOSA\" graphics thanks to its vibrant colourway.', '1010.jpg', '2025-09-14 04:12:17', 'ASICS Gel-Noosa Tri 15 Gs Trainers', 'casual', 25, 3),
(33, '10499.00', 'Elements of five classic Jordans come together in the Spizike to create one iconic sneaker. A homage to Spike Lee formally introducing Hollywood and hoops, these great-looking kicks come packed with history.', 'JORDAN boy.jpg', '2025-09-21 06:13:07', 'Jordan Spizike Low SE', 'best-seller', 32, 3),
(34, '8999.00', 'This special-edition mid-top AJ1 comes through with serious sparkle. Soft suede and sturdy denim mix with pops of pink and a sequin Swoosh logo for fun style you can wear every day.', 'jordan01.jpg', '2025-09-21 06:15:18', 'Air Jordan 1 Mid SE', 'best-seller', 23, 3),
(35, '14999.00', 'Comfortable, durable and timelessâ€”it\'s number one for a reason. The classic \'80s construction pairs durable leather with bold details for style that tracks whether you\'re on court or on the go.', 'AIR+FORCE white.jpg', '2025-09-21 06:20:30', 'Nike Air Force 1', 'best-seller', 19, 1),
(36, '17999.00', 'Feel unbeatable, from the tee box to the final putt. Inspired by one of the most iconic sneakers of all time, the Air Jordan 1 G is an instant classic on the course. With Air cushioning underfoot, a Wings logo on the heel and an integrated traction pattern to help you power through your swing, it delivers all the clubhouse cool of the original AJ1â€”plus everything you need to play 18 holes in comfort.', 'jorder01.jpg', '2025-09-22 04:17:19', 'Air Jordan I High G', 'best-seller', 12, 1),
(37, '14999.00', 'Luxe full-grain leather is combined with a chunky platform to pump up your personal style. Exaggerated outsole lugs and Nike Air cushioning support your every step. And don\'t forget about the AJ1 DNA: a diamond inset on the heel displays Jumpman insignia, grounding your look in hoops heritage.', 'nike.jpg', '2025-09-22 04:20:44', 'Air Jordan 1 Brooklyn Low', 'best-seller', 24, 2),
(38, '12499.00', 'Made for life on the go, these sneakers reimagine the AJ4 with comfort and durability in mind. The layered upper blends genuine leather with a strong, flexible cage to add a pop of ruggedness to your commute. And Max Air cushioning helps support you through the gridiron.', 'jordan01.jpg', '2025-09-22 04:25:35', 'Air Jordan 4 RM', 'best-seller', 51, 2);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 38, 5, 4, 'Absolutely love these sneakers! Super comfortable for long walks and the design is sleek. Got so many compliments already. Worth every penny!', '2025-09-22 04:35:09', '2025-09-22 04:35:09'),
(2, 33, 4, 3, 'Decent shoes for the price. Comfortable but the color started fading after a few months of regular use. Good value for money though', '2025-09-22 04:37:15', '2025-09-22 04:37:15'),
(3, 35, 8, 5, 'Stylish and comfortable casual wear. Perfect for college and hanging out with friends. Fast delivery too!', '2025-09-22 04:38:19', '2025-09-22 04:38:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `profile_picture`, `phone`) VALUES
(1, 'kevat pavan b', 'pavan@gmail.com', '$2y$10$WndZb9qVVazEkxZLQoh.te5FNuqmiWoq3ly.GwXHsRbfBqwNrI7tq', 'Uploads/profile_pictures/1_1757868490_OIP (1).webp', NULL),
(2, 'parmar rohit', 'rohit@gmail.com', '$2y$10$0eoEeIjGndkVrv2fH1FVFO3LvM/1RXk0at5BLbv0plOsKFoU4qScu', 'Uploads/profile_pictures/2_1758079429_admin1.jpg', NULL),
(3, 'mohit parmar', 'mohit@gmail.com', '$2y$10$A6Z2JySj0kQdgwrwqU9KWuE4yNDobqXsi4DR9SENiJ75BBSbX06xy', NULL, NULL),
(4, 'rajdeep_1995', 'rajdeep1995@gmail.com', '$2y$10$GhMcgC8YsBkXCPxccIL2i.bjxs3DvlcrCr3AOT/.kE9rHhq6SOkhG', 'Uploads/profile_pictures/4_1758606687_profile-picture-african-american-person-flat-cartoon-style-minimalist-style_1099486-1.avif', NULL),
(5, 'Rohan Gupta', 'rohan.gupta@gmail.com', '$2y$10$CyzzHH0o2jtzz.utETyH..IaVdp7rgtlX9Es/VnVkgjqiMK82V.NC', NULL, NULL),
(6, 'Tanvi Shah', 'tanvi.shah@gmail.com', '$2y$10$3XMjJKA/9o6tPaNSQzUY5uom941crFVOlY1Y6b7aVxqXAdC12ZVzy', NULL, NULL),
(7, 'arjun_patel_88', 'arjun_patel_88@gmail.com', '$2y$10$VMixSpIcSjISIogNFVTbtuMolpMa9StB1M1MbnzgM/6eq37KEDGtG', NULL, NULL),
(8, 'meera chopra92', 'meera$chopra92@gmail.com', '$2y$10$.T1Z/CMbLdDnuzDqc1wBPOP3NHsER/UusdtNquKXF5vDD8G5NQvb2', NULL, NULL),
(9, 'swati_jain', 'swati_jain454@gmail.com', '$2y$10$xZ7NZg2djV9W1Oh8cy1nUuY7cv1W8qjNg1mywwHy4SaqWf7tgiMHu', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
