<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *
 * $Id$
 *                                                                        */

/**
 * This finisher stores the submitted values into a table in a different database than the TYPO3 database according to the configuration.
 * This class uses the extension 'adodb' to query the database.
 *
 * Example configuration:
 *
 * <code>
 * finishers.1.class = F3_MailformPlusPlus_Finisher_DifferentDB
 * finishers.1.config.host = 127.0.0.1
 * finishers.1.config.port = 666
 * finishers.1.config.db = typo3_421
 * finishers.1.config.username = root
 * finishers.1.config.password = rootpass
 * finishers.1.config.driver = oci8
 * </code>
 *
 * Further configuration equals the configuration of F3_MailformPlusPlus_Finisher_DB.
 *
 * @package	F3_MailformPlusPlus
 * @subpackage	Finisher
 * @see F3_MailformPlusPlus_Finisher_DB
 */
class F3_MailformPlusPlus_Finisher_DifferentDB extends F3_MailformPlusPlus_Finisher_DB {

	/**
	 * The name of the database driver to use.
	 *
	 * @access protected
	 * @var string
	 */
	protected $driver;

	/**
	 * The name of the database host.
	 *
	 * @access protected
	 * @var string
	 */
	protected $host;

	/**
	 * The port the database listens.
	 *
	 * @access protected
	 * @var integer
	 */
	protected $port;

	/**
	 * The name of the database.
	 *
	 * @access protected
	 * @var string
	 */
	protected $db;

	/**
	 * The username to use.
	 *
	 * @access protected
	 * @var string
	 */
	protected $user;

	/**
	 * The password to use.
	 *
	 * @access protected
	 * @var string
	 */
	protected $password;

	/**
	 * Method to query the database making an insert or update statement using the given fields.
	 *
	 * @see F3_MailformPlusPlus_Finisher_DB::save()
	 * @param array &$queryFields Array holding the query fields
	 * @return void
	 */
	protected function save(&$queryFields) {

		//if adodb is installed
		if(t3lib_extMgm::isLoaded('adodb')) {
			require_once(t3lib_extMgm::extPath('adodb') . 'adodb/adodb.inc.php');
				
			//build sql
				
			//insert query
			if(!$this->doUpdate) {
				foreach($queryFields as $dbfield=>$value) {
					$fields[$dbfield] = $value;
					if(!is_numeric($value)) {
						$fields[$dbfield] = "'" . $value . "'";
					}
				}
				$sql = 'INSERT INTO ' . $this->table . ' (' . (implode(',', array_keys($fields))) . ') VALUES (' . (implode(',', $fields)) . ')';

				//update query
			} else {

				//check if uid of record to update is in GP
				$uid = $this->gp['uid'];
				if(!$uid) {
					$uid = $this->gp[$this->key];
				}
				if($uid) {
					$fields = array();
					foreach($queryFields as $dbfield => $value) {
						if(is_numeric($value)) {
							$fields[] = $dbfield . '=' . $value;
						} else {
							$fields[] = $dbfield . "='" . $value . "'";
						}
					}
					$fields = implode(',', $fields);
					$sql = 'UPDATE ' . $this->table . ' SET (' . $fields . ') WHERE ' . $this->key . '=' . $uid;
				} else {
					F3_MailformPlusPlus_StaticFuncs::debugMessage('no_update_possible');
				}
			}
				
			//open connection
			$conn = &NewADOConnection($this->driver);
			$host = $this->host;
			if($this->port) {
				$host .= ':' . $this->port;
			}
			if($this->db) {
				$conn->Connect($host, $this->user, $this->password, $this->db);
			} else {
				$conn->Connect($host, $this->user, $this->password);
			}
				
			//insert data
			$conn->Execute($sql);
				
			//close connection
			$conn->Close();
		} else {
			F3_MailformPlusPlus_StaticFuncs::throwException('extension_required', 'adodb', 'F3_MailformPlsuPlus_Finisher_DifferentDB');
		}
	}

	/**
	 * Inits the finisher mapping settings values to internal attributes.
	 *
	 * @see F3_MailformPlusPlus_Finisher_DB::init
	 * @return void
	 */
	protected function init() {
		parent::init();
		$this->driver = $this->settings['driver'];
		$this->db = $this->settings['db'];
		$this->host = $this->settings['host'];
		$this->port = $this->settings['port'];
		$this->user = $this->settings['username'];
		$this->password = $this->settings['password'];
		if(!$this->driver) {
			throw new Exception('No driver given!');
		}
	}

}
?>
