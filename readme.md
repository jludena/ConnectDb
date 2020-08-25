## ConnectDb

ConnectDb is a lightweight library to connect to MySql using PDO, it has methods implemented in the ActiveRecord class to perform database operations (select, insert, update, delete)

## Create database in mysql server to run unit test

````
create database connectdb;
````

````
CREATE TABLE IF NOT EXISTS `connectdb`.`role` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(30) NOT NULL,
  `status` TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;

CREATE TABLE IF NOT EXISTS `connectdb`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_id` INT NOT NULL,
  `username` VARCHAR(45) NOT NULL,
  `password` VARCHAR(120) NOT NULL,
  `status` TINYINT(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  INDEX `fk_user_role_idx` (`role_id` ASC),
  CONSTRAINT `fk_user_role`
    FOREIGN KEY (`role_id`)
    REFERENCES `connectdb`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;

````
## Run composer
````
composer install
````

## Run unit test
````
>> cd ConnectDb/tests

>> ../vendor/bin/phpunit ConnectDbTest
````
