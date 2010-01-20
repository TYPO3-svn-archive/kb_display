<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Bernhard Kraft <kraftb@think-open.at>
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
 *
 * Smarty plugin "mysql_escape"
 * -------------------------------------------------------------
 * File:    modifier.mysql_escape.php
 * Type:    modifier
 * Name:    mysql_escape
 * Version: 1.0
 * Author:  Bernhard Kraft <kraftb@think-open.at>
 * Purpose: Passes a value through TYPO3_DB->quoteStr()
 * Example: {$assignedPHPvariable|mysql_escape}
 * Note:	See mysql_escape_string for more information
 * -------------------------------------------------------------
 *
 **/


function smarty_modifier_mysql_escape($text, $setup=false) {
	return $GLOBALS['TYPO3_DB']->quoteStr($text, '');
}

?>
