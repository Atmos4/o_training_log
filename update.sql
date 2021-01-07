CREATE TABLE `carnet_ffco`.`planning_vote` ( 
    `id` INT(9) UNSIGNED NOT NULL AUTO_INCREMENT , 
    `runner_id` SMALLINT(5) UNSIGNED NOT NULL , 
    `planning_id` MEDIUMINT(8) NOT NULL , 
    `vote` BOOLEAN NOT NULL , 
    PRIMARY KEY (`id`)
) 
ENGINE = InnoDB;