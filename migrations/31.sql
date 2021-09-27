CREATE TABLE `event_status` (
    `id` bigint(11) NOT NULL AUTO_INCREMENT,
    `callsid` varchar(255) DEFAULT NULL,
    `status` int(11) DEFAULT NULL,
    `event_id` int(11) DEFAULT NULL,
    `created_at` timestamp NULL,
    `updated_at` timestamp NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC)
)
