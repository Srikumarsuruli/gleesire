-- Create marketing_files table
CREATE TABLE IF NOT EXISTS `marketing_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `marketing_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create marketing_data table
CREATE TABLE IF NOT EXISTS `marketing_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `campaign_date` date DEFAULT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `amount_spent` decimal(10,2) NOT NULL,
  `impressions` int(11) NOT NULL,
  `cpm` decimal(10,2) NOT NULL,
  `reach` int(11) NOT NULL,
  `link_clicks` int(11) NOT NULL,
  `cpc` decimal(10,2) NOT NULL,
  `results` int(11) NOT NULL,
  `cost_per_result` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `marketing_data_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `marketing_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add privileges for marketing data management
INSERT INTO `privileges` (`id`, `name`, `description`) VALUES
(NULL, 'upload_marketing_data', 'Can upload marketing data files'),
(NULL, 'view_marketing_data', 'Can view marketing data'),
(NULL, 'delete_marketing_data', 'Can delete marketing data files');