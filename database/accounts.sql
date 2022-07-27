DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `hs_id` bigint(20) NOT NULL,
  `is_active` BOOLEAN DEFAULT FALSE,
  `is_suspended` BOOLEAN DEFAULT FALSE,
  `activation_token` TEXT,
  `reset_token` TEXT,
  `smtp_server` TEXT,
  `smtp_email` TEXT,
  `smtp_password` TEXT,
  `smtp_port` smallint(6),
  `smtp_ssl` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
