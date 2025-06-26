-- Create marketing_files table
CREATE TABLE IF NOT EXISTS `marketing_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_name` (`file_name`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `marketing_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create marketing_data table
CREATE TABLE IF NOT EXISTS `marketing_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `campaign_date` date DEFAULT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `amount_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `cpm` decimal(10,2) DEFAULT NULL,
  `reach` int(11) DEFAULT NULL,
  `link_clicks` int(11) DEFAULT NULL,
  `cpc` decimal(10,2) DEFAULT NULL,
  `results` int(11) DEFAULT NULL,
  `cost_per_result` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `marketing_data_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `marketing_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add privilege for marketing data upload
INSERT INTO `privileges` (`id`, `name`, `description`) VALUES
(NULL, 'upload_marketing_data', 'Upload and view marketing data');

-- Grant privilege to admin role (assuming role_id 1 is admin)
INSERT INTO `user_privileges` (`role_id`, `menu_name`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES
(1, 'upload_marketing_data', 1, 1, 1, 1);