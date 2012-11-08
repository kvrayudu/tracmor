<?php
/*
 * This script updates a Tracmor database to version 0.4.1
 */
require_once('../includes/prepend.inc.php');

$objDatabase = QApplication::$Database[1];

// Check if this script has already been run
$objDbResult = $objDatabase->Query("SHOW TABLES LIKE '_version'");
if (count($objDbResult->FetchArray())) {
echo('This script has already been run! Exiting...');
exit;
}

// Put the following in a transaction and rollback if there are any problems
try {
$objDatabase->TransactionBegin();

// Create _version table
$strQuery = "CREATE TABLE IF NOT EXISTS `_version` (`version` VARCHAR(50)) ENGINE = INNODB";
$objDatabase->NonQuery($strQuery);

// Set version to 0.4.0
$strQuery = "INSERT INTO `_version` (`version`) VALUES ('0.4.1')";
$objDatabase->NonQuery($strQuery);

// Change default value for custom_field all_asset_model_flag
$strQuery = "ALTER TABLE `custom_field` CHANGE
             COLUMN `all_asset_models_flag` `all_asset_models_flag` BIT(1) NULL DEFAULT 1;";
$objDatabase->NonQuery($strQuery);

// Change asset_model table
$strQuery =
"ALTER TABLE `asset_model` ADD COLUMN `depreciation_class_id` INT NULL;
 ALTER TABLE `asset_model` ADD INDEX `depreciation_class_id` (`depreciation_class_id` ASC) ;
";
$objDatabase->NonQuery($strQuery);

// Change asset table
$strQuery = "
  ALTER TABLE `asset` ADD COLUMN `depreciation_flag` BIT(1) DEFAULT NULL,
					  ADD COLUMN `purchase_date`    DATETIME  DEFAULT NULL,
					  ADD COLUMN `purchase_cost`    DECIMAL(10,2) DEFAULT NULL;
";
$objDatabase->NonQuery($strQuery);


// Add new Depreciation Tables and set foreign keys for them
$strQuery =
"  CREATE TABLE  depreciation_class(
   depreciation_class_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
   depreciation_method_qtype_id INTEGER UNSIGNED NOT NULL,
   short_description VARCHAR(255)   NOT NULL,
   life INTEGER UNSIGNED   NULL,
   PRIMARY KEY (depreciation_class_id),
   INDEX depreciation_class_fkindex1 ( depreciation_class_id ),
   UNIQUE (depreciation_method_qtype_id),
   UNIQUE (short_description),
   INDEX depreciation_class_fkindex2 ( depreciation_method_qtype_id )
)
ENGINE = INNODB;

CREATE TABLE depreciation_method_qtype(
  depreciation_method_qtype_id  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  short_description  VARCHAR(255)   NOT NULL,
  PRIMARY KEY (depreciation_method_qtype_id),
  INDEX depreciation_method_qtype_fkindex1 (depreciation_method_qtype_id))
ENGINE = INNODB;

ALTER TABLE depreciation_class
  ADD CONSTRAINT FOREIGN KEY(depreciation_method_qtype_id) references depreciation_method_qtype (
    depreciation_method_qtype_id
  )
ON Delete NO ACTION ON Update NO ACTION;

ALTER TABLE asset_model
  ADD CONSTRAINT FOREIGN KEY(depreciation_class_id) references depreciation_class (
    depreciation_class_id
  )
ON Delete NO ACTION ON Update NO ACTION;
";
$objDatabase->NonQuery($strQuery);
$objDatabase->TransactionCommit();
echo('Update successful!');

} catch (QMySqlDatabaseException $objExc) {
// Something went wrong
$objDatabase->TransactionRollback();
echo('Update failed!');

}