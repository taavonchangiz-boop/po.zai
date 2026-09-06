-- Auth hardening: username is the password-login identifier; mobile is optional.
ALTER TABLE `User` MODIFY COLUMN `mobile` VARCHAR(191) NULL;
ALTER TABLE `User` ADD COLUMN `username` VARCHAR(32) NULL;
CREATE UNIQUE INDEX `User_username_key` ON `User`(`username`);
