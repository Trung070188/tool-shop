CREATE TABLE `error_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exception` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `trace` longtext DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `sent` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4