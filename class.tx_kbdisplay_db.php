<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2012 Bernhard Kraft <kraftb@think-open.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Class for generating and executing the query puzzled together
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_db extends t3lib_DB {

	/**
	 * Open a (persistent) connection to a MySQL server
	 * mysql_pconnect() wrapper function
	 * Usage count/core: 12
	 *
	 * @param	string		Database host IP/domain
	 * @param	string		Username to connect with.
	 * @param	string		Password to connect with.
	 * @return	pointer		Returns a positive MySQL persistent link identifier on success, or FALSE on error.
	 */
	function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password) {
			// mysql_error() is tied to an established connection
			// if the connection fails we need a different method to get the error message
		@ini_set('track_errors', 1);
		@ini_set('html_errors', 0);

			// check if MySQL extension is loaded
		if (!extension_loaded('mysql')) {
			$message = 'Database Error: It seems that MySQL support for PHP is not installed!';
			throw new RuntimeException($message, 1271492606);
		}

			// Check for client compression
		$isLocalhost = ($TYPO3_db_host == 'localhost' || $TYPO3_db_host == '127.0.0.1');
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect']) {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress'] && !$isLocalhost) {
					// We use PHP's default value for 4th parameter (new_link), which is false.
					// See PHP sources, for example: file php-5.2.5/ext/mysql/php_mysql.c,
					// function php_mysql_do_connect(), near line 525
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, true, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, true);
			}
		} else {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress'] && !$isLocalhost) {
					// See comment about 4th parameter in block above
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, true, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, true);
			}
		}

		$error_msg = $php_errormsg;
		@ini_restore('track_errors');
		@ini_restore('html_errors');

		if (!$this->link) {
			t3lib_div::sysLog('Could not connect to MySQL server ' . $TYPO3_db_host .
					' with user ' . $TYPO3_db_username . ': ' . $error_msg,
				'Core',
				4
			);
		} else {
			$setDBinit = t3lib_div::trimExplode(LF, str_replace("' . LF . '", LF, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']), TRUE);
			foreach ($setDBinit as $v) {
				if (mysql_query($v, $this->link) === FALSE) {
					t3lib_div::sysLog('Could not initialize DB connection with query "' . $v .
							'": ' . mysql_error($this->link),
						'Core',
						3
					);
				}
			}
			$this->setSqlMode();
		}

		return $this->link;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/class.tx_kbdisplay_db.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/class.tx_kbdisplay_db.php']);
}

?>
