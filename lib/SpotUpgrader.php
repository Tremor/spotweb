<?php

class SpotUpgrader {
	private $_db;
	private $_dbEngine;
	
	function __construct($dbSettings) {
		$this->_db = new SpotDb($dbSettings);
		$this->_db->connect();
		$this->_dbEngine = $dbSettings['engine'];
	} # ctor
	
	/*
	 * Upgrade de settings
	 */
	function settings($settings) {
		require_once "settings.php";
		
		# Creer het settings object
		$settings = SpotSettings::singleton($this->_db, $settings);
		$spotSettingsUpgrader = new SpotSettingsUpgrader($this->_db, $settings);
		$spotSettingsUpgrader->update();
	} # settings
	
	/*
	 * Creeert en upgrade de database
	 */
	function database() {
		# Instantieeer een struct object
		switch($this->_dbEngine) {	
			case 'mysql'			:
			case 'pdo_mysql'		: $dbStruct = new SpotStruct_mysql($this->_db); break;
			
			case 'pdo_sqlite'		: $dbStruct = new SpotStruct_sqlite($this->_db); break;
			
			default					: throw new Exception("Onbekende database engine");
		} # switch
		
		$dbStruct->createDatabase();
		$dbStruct->updateSchema();
	 } # database

	/*
	 * Optimaliseert de database
	 */
	function analyze() {
		# Instantieeer een struct object
		switch($this->_dbEngine) {	
			case 'mysql'			:
			case 'pdo_mysql'		: $dbStruct = new SpotStruct_mysql($this->_db); break;
			
			case 'pdo_sqlite'		: $dbStruct = new SpotStruct_sqlite($this->_db); break;
			
			default					: throw new Exception("Onbekende database engine");
		} # switch
		
		$dbStruct->analyze();
	 } # analyze
	 
} # SpotUpgrader

