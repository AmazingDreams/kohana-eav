###
### This is sample MYSQL structure for the Kohana EAV module
### I used 'products' because EAV is often used in webshops
###

##
## TABLE STRUCTURE FOR products
##
CREATE TABLE IF NOT EXISTS `products` (
	`id`           INT(11) UNSIGNED  NOT NULL   AUTO_INCREMENT,
	`name`         VARCHAR(500)      NOT NULL,
	`description`  TEXT              NOT NULL   DEFAULT '',
	
	PRIMARY KEY    (`id`)
	
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

##
## TABLE STRUCTURE FOR product_attributes
##
CREATE TABLE IF NOT EXISTS `product_attributes` (
	`id`          INT(11) UNSIGNED  NOT NULL    AUTO_INCREMENT,
	`product_id`  INT(11) UNSIGNED  NOT NULL,
	`type`        VARCHAR(500)      NOT NULL,
	`name`        VARCHAR(500)      NOT NULL,
	
	PRIMARY KEY    (`id`),
	INDEX          (`product_id`),
	
	CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
	
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

##
## TABLE STRUCTURE FOR product_attribute_values
##
CREATE TABLE IF NOT EXISTS `product_attribute_values` (
	`product_attribute_id`  INT(11) UNSIGNED   NOT NULL,
	`value`                 VARCHAR(500)       NOT NULL,
	
	INDEX    (`product_attribute_id`),
	
	CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE
	
) ENGINE=InnoDB DEFAULT CHARSET=utf8;