CREATE TABLE `campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(300) DEFAULT NULL,
  `package_id` varchar(200) DEFAULT NULL,
  `icon` varchar(300) DEFAULT NULL,
  `price` bigint(20) DEFAULT 0,
  `os` enum('ios','android') DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `type` enum('cpi','rate') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4