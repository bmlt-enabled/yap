ALTER TABLE `migrations`
ADD COLUMN `timestamp` TIMESTAMP DEFAULT now();
