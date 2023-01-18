-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`user` (
  `idUser` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roles` JSON NOT NULL,
  `username` VARCHAR(50) NULL,
  `pwd` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `lastName` VARCHAR(255) NULL,
  `firstName` VARCHAR(200) NULL,
  `mobile` VARCHAR(10) NULL,
  `postMail` VARCHAR(255) NULL,
  `postMailComplement` VARCHAR(255) NULL,
  `zipCode` VARCHAR(5) NULL,
  `city` VARCHAR(255) NULL,
  PRIMARY KEY (`idUser`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC) VISIBLE,
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`order`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`order` (
  `idOrder` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idCustomer` INT UNSIGNED NOT NULL,
  `idAgent` INT UNSIGNED NOT NULL,
  `orderDate` DATETIME NULL,
  `ref` VARCHAR(100) NULL,
  `status` VARCHAR(50) NULL,
  `lastChange` DATETIME NULL,
  `dateCreation` DATETIME NULL,
  `dateOpen` DATETIME NULL,
  `datePending` DATETIME NULL,
  `dateClosed` DATETIME NULL,
  `dateCancelled` DATETIME NULL,
  PRIMARY KEY (`idOrder`),
  INDEX `fk_commande_user_idx` (`idCustomer` ASC) VISIBLE,
  INDEX `fk_commande_user1_idx` (`idAgent` ASC) VISIBLE,
  UNIQUE INDEX `ref_UNIQUE` (`ref` ASC) VISIBLE,
  CONSTRAINT `fk_commande_user`
    FOREIGN KEY (`idCustomer`)
    REFERENCES `mydb`.`user` (`idUser`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_commande_user1`
    FOREIGN KEY (`idAgent`)
    REFERENCES `mydb`.`user` (`idUser`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`category` (
  `idCategory` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NULL,
  PRIMARY KEY (`idCategory`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`type`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`type` (
  `idType` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NULL,
  PRIMARY KEY (`idType`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`product` (
  `idProduct` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idAuthor` INT UNSIGNED NOT NULL,
  `idCategory` INT UNSIGNED NOT NULL,
  `idType` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NULL,
  `description` LONGTEXT NULL,
  `price` DOUBLE UNSIGNED NULL,
  `stock` INT NULL,
  `gender` TINYTEXT NULL,
  `species` VARCHAR(200) NULL,
  `race` VARCHAR(200) NULL,
  `birth` SMALLINT UNSIGNED NULL,
  `requiresCertification` TINYINT UNSIGNED NULL,
  `dimensionsMax` DOUBLE NULL,
  `dimensionsUnit` TINYTEXT NULL,
  `specification` VARCHAR(50) NULL,
  `specificationValue` DOUBLE NULL,
  `specificationUnit` VARCHAR(50) NULL,
  `isVisible` TINYINT UNSIGNED NULL,
  PRIMARY KEY (`idProduct`),
  INDEX `fk_produit_user1_idx` (`idAuthor` ASC) VISIBLE,
  INDEX `fk_product_category1_idx` (`idCategory` ASC) VISIBLE,
  INDEX `fk_product_type1_idx` (`idType` ASC) VISIBLE,
  CONSTRAINT `fk_produit_user1`
    FOREIGN KEY (`idAuthor`)
    REFERENCES `mydb`.`user` (`idUser`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_product_category1`
    FOREIGN KEY (`idCategory`)
    REFERENCES `mydb`.`category` (`idCategory`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_product_type1`
    FOREIGN KEY (`idType`)
    REFERENCES `mydb`.`type` (`idType`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`line`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`line` (
  `idLine` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idOrder` INT UNSIGNED NOT NULL,
  `idProduct` INT UNSIGNED NOT NULL,
  `quantity` INT NULL,
  `price` DOUBLE NULL,
  PRIMARY KEY (`idLine`),
  INDEX `fk_ligne_commande1_idx` (`idOrder` ASC) VISIBLE,
  INDEX `fk_ligne_produit1_idx` (`idProduct` ASC) VISIBLE,
  CONSTRAINT `fk_ligne_commande1`
    FOREIGN KEY (`idOrder`)
    REFERENCES `mydb`.`order` (`idOrder`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_ligne_produit1`
    FOREIGN KEY (`idProduct`)
    REFERENCES `mydb`.`product` (`idProduct`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`recommandation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`recommandation` (
  `idRecommandation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idProduit1` INT UNSIGNED NOT NULL,
  `idProduit2` INT UNSIGNED NOT NULL,
  INDEX `fk_recommandation_produit1_idx` (`idProduit2` ASC) VISIBLE,
  INDEX `fk_recommandation_produit2_idx` (`idProduit1` ASC) VISIBLE,
  PRIMARY KEY (`idRecommandation`),
  CONSTRAINT `fk_recommandation_produit1`
    FOREIGN KEY (`idProduit2`)
    REFERENCES `mydb`.`product` (`idProduct`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_recommandation_produit2`
    FOREIGN KEY (`idProduit1`)
    REFERENCES `mydb`.`product` (`idProduct`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`favorites`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`favorites` (
  `idfavorites` INT UNSIGNED NOT NULL,
  `idUser` INT UNSIGNED NOT NULL,
  `idProduct` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`idfavorites`),
  INDEX `fk_favoris_user1_idx` (`idUser` ASC) VISIBLE,
  INDEX `fk_favoris_produit1_idx` (`idProduct` ASC) VISIBLE,
  CONSTRAINT `fk_favoris_user1`
    FOREIGN KEY (`idUser`)
    REFERENCES `mydb`.`user` (`idUser`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_favoris_produit1`
    FOREIGN KEY (`idProduct`)
    REFERENCES `mydb`.`product` (`idProduct`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
