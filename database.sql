DROP TABLE IF EXISTS `experiments`;
CREATE TABLE `experiments` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE KEY,
    `data` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `treatments`;
CREATE TABLE `treatments` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `experiment_id` int(11) NOT NULL,
    FOREIGN KEY `fk_treatment_experiment_id` (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uniq_treatment_name_expid` (`experiment_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users_treatments`;
CREATE TABLE `users_treatments` (
    `identity` varchar(200) NOT NULL,
    `treatment_id` int(11) NOT NULL,
    `experiment_id` int(11) NOT NULL,
    `completed` tinyint(4) DEFAULT 0,
    UNIQUE KEY `uniq_ut_identity_experiment` (`identity`,`experiment_id`),
    FOREIGN KEY `fk_ut_experiment` (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY `fk_ut_treatment` (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;