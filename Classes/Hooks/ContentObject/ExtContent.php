<?php
namespace thinkopen_at\kbDisplay\Hooks\ContentObject;
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2014 Bernhard Kraft <kraftb@think-open.at>
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


use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for content object "EXT_CONTENT"
 *
 * @author Bernhard Kraft <kraftb@think-open.at>
 * @package TYPO3
 * @subpackage kb_display
 */
class ExtContent {

	/*
	 * Renders the extended CONTENT cObject
	 *
	 * @param string $name: Should be "EXT_CONTENT"
	 * @param array $conf: The TypoScript configuration for this content object
	 * @param string $TSkey: Path to the currently rendered TS object
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObj: A pointer to the parent content object renderer
	 */
	public function cObjGetSingleExt($name, array $conf, $TSkey, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$parentObj) {
		$theValue='';
		$this->parentObj = &$parentObj;

		$originalRec = $GLOBALS['TSFE']->currentRecord;
		if ($originalRec)	{		// If the currentRecord is set, we register, that this record has invoked this function. It's should not be allowed to do this again then!!
			$GLOBALS['TSFE']->recordRegister[$originalRec]++;
		}

		if ($conf['table']=='pages' || substr($conf['table'],0,3)=='tt_' || substr($conf['table'],0,3)=='fe_' || substr($conf['table'],0,3)=='tx_' || substr($conf['table'],0,4)=='ttx_' || substr($conf['table'],0,5)=='user_' || substr($conf['table'],0,7)=='static_') {

			$renderObjName = $conf['renderObj'] ? $conf['renderObj'] : '<'.$conf['table'];
			$renderObjKey = $conf['renderObj'] ? 'renderObj' : '';
			$renderObjConf = $conf['renderObj.'];

			$slide = intval($conf['slide'])?intval($conf['slide']):0;
			$slideCollect = intval($conf['slide.']['collect'])?intval($conf['slide.']['collect']):0;
			$slideCollectReverse = intval($conf['slide.']['collectReverse'])?true:false;
			$slideCollectFuzzy = $slideCollect?(intval($conf['slide.']['collectFuzzy'])?true:false):true;
			$again = false;

			do {
				$res = $this->exec_getQuery($conf['table'],$conf['select.']);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error()) {
					$GLOBALS['TT']->setTSlogMessage($error,3);
				} else {
					$parentObj->currentRecordTotal = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
					$GLOBALS['TT']->setTSlogMessage('NUMROWS: '.$GLOBALS['TYPO3_DB']->sql_num_rows($res));
					$cObj = GeneralUtility::makeInstance('tslib_cObj');
					$cObj->setParent($parentObj->data,$parentObj->currentRecord);
					$parentObj->currentRecordNumber=0;
					$cobjValue = '';
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

							// Versioning preview:
						$GLOBALS['TSFE']->sys_page->versionOL($conf['table'],$row,TRUE);

							// Language Overlay:
						if (is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
							$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($conf['table'],$row,$GLOBALS['TSFE']->sys_language_content,$GLOBALS['TSFE']->sys_language_contentOL);
						}

						if (is_array($row)) { // Might be unset in the sys_language_contentOL
							if (!$GLOBALS['TSFE']->recordRegister[$conf['table'].':'.$row['uid'].':'.md5(serialize($conf))]) {
								$parentObj->currentRecordNumber++;
								$cObj->parentRecordNumber = $parentObj->currentRecordNumber;
								$GLOBALS['TSFE']->currentRecord = $conf['table'].':'.$row['uid'].':'.md5(serialize($conf));
								$parentObj->lastChanged($row['tstamp']);
								$cObj->start($row,$conf['table']);
								$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
								$cobjValue .= $tmpValue;
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
				if ($slideCollectReverse) {
					$theValue = $cobjValue.$theValue;
				} else {
					$theValue .= $cobjValue;
				}
				if ($slideCollect>0) {
					$slideCollect--;
				}
				if ($slide) {
					if ($slide>0) {
						$slide--;
					}
					$conf['select.']['pidInList'] = $parentObj->getSlidePids($conf['select.']['pidInList'], $conf['select.']['pidInList.']);
					$again = strlen($conf['select.']['pidInList'])?true:false;
				}
			} while ($again&&(($slide&&!strlen($tmpValue)&&$slideCollectFuzzy)||($slide&&$slideCollect)));
		}

		$theValue = $parentObj->wrap($theValue,$conf['wrap']);
		if ($conf['stdWrap.']) $theValue = $parentObj->stdWrap($theValue,$conf['stdWrap.']);

		$GLOBALS['TSFE']->currentRecord = $originalRec;	// Restore
		return $theValue;
	}

	function exec_getQuery($table, $conf)	{
		$queryParts = $this->getQuery($table, $conf, TRUE);

		return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
	}

	function getQuery($table, $conf, $returnQueryArray=FALSE)	{

			// Construct WHERE clause:
		$conf['pidInList'] = trim($this->parentObj->stdWrap($conf['pidInList'],$conf['pidInList.']));
		if (!strcmp($conf['pidInList'],''))	{
			$conf['pidInList'] = 'this';
		}
		$queryParts = $this->getWhere($table,$conf,TRUE);

			// Fields:
		$queryParts['SELECT'] = $conf['selectFields'] ? $conf['selectFields'] : '*';

			// Setting LIMIT:
		if ($conf['max'] || $conf['begin']) {
			$error=0;

				// Finding the total number of records, if used:
			if (strstr(strtolower($conf['begin'].$conf['max']),'total'))	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $table, $queryParts['WHERE'], $queryParts['GROUPBY']);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error())	{
					$GLOBALS['TT']->setTSlogMessage($error);
				} else {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					$conf['max'] = eregi_replace('total', $row[0], $conf['max']);
					$conf['begin'] = eregi_replace('total', $row[0], $conf['begin']);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			if (!$error)	{
				$conf['begin'] = MathUtility::forceIntegerInRange(ceil($this->parentObj->calc($conf['begin'])),0);
				$conf['max'] = MathUtility::forceIntegerInRange(ceil($this->parentObj->calc($conf['max'])),0);
				if ($conf['begin'] && !$conf['max'])	{
					$conf['max'] = 100000;
				}

				if ($conf['begin'] && $conf['max'])	{
					$queryParts['LIMIT'] = $conf['begin'].','.$conf['max'];
				} elseif (!$conf['begin'] && $conf['max'])	{
					$queryParts['LIMIT'] = $conf['max'];
				}
			}
		}

		if (!$error)	{

				// Setting up tablejoins:
			$joinPart='';
			if ($conf['join'])	{
				$joinPart = 'JOIN ' .trim($conf['join']);
			} elseif ($conf['leftjoin'])	{
				$joinPart = 'LEFT OUTER JOIN ' .trim($conf['leftjoin']);
			} elseif ($conf['rightjoin'])	{
				$joinPart = 'RIGHT OUTER JOIN ' .trim($conf['rightjoin']);
			}

				// Compile and return query:
			$queryParts['FROM'] = trim($table.' '.$joinPart);
			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
						$queryParts['SELECT'],
						$queryParts['FROM'],
						$queryParts['WHERE'],
						$queryParts['GROUPBY'],
						$queryParts['ORDERBY'],
						$queryParts['LIMIT']
					);
			return $returnQueryArray ? $queryParts : $query;
		}
	}

	function getWhere($table,$conf, $returnQueryArray=FALSE)	{
		global $TCA;

			// Init:
		$query = '';
		$pid_uid_flag=0;
		$queryParts = array(
			'SELECT' => '',
			'FROM' => '',
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);

		if (trim($conf['uidInList']))	{
			$listArr = GeneralUtility::intExplode(',',str_replace('this',$GLOBALS['TSFE']->contentPid,$conf['uidInList']));  // str_replace instead of ereg_replace 020800
			if (count($listArr)==1)	{
				$query.=' AND '.$table.'.uid='.intval($listArr[0]);
			} else {
				$query.=' AND '.$table.'.uid IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($listArr)).')';
			}
			$pid_uid_flag++;
		}
		if (trim($conf['pidInList']))	{
			$listArr = GeneralUtility::intExplode(',',str_replace('this',$GLOBALS['TSFE']->contentPid,$conf['pidInList']));	// str_replace instead of ereg_replace 020800
				// removes all pages which are not visible for the user!
			$listArr = $this->checkPidArray($listArr);
			if (count($listArr))	{
				$query.=' AND '.$table.'.pid IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($listArr)).')';
				$pid_uid_flag++;
			} else {
				$pid_uid_flag=0;		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			}
		}
		if (!$pid_uid_flag)	{		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			$query.=' AND '.$table.'.uid=0';
		}
		if ($where = trim($conf['where']))	{
			$query.=' AND '.$where;
		}

		if ($conf['languageField'])	{
			if ($GLOBALS['TSFE']->sys_language_contentOL && $TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'])	{
					// Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
				$sys_language_content = '0,-1';
			} else {
				$sys_language_content = intval($GLOBALS['TSFE']->sys_language_content);
			}
			$query.=' AND '.$conf['languageField'].' IN ('.$sys_language_content.')';
		}

		$andWhere = trim($this->parentObj->stdWrap($conf['andWhere'],$conf['andWhere.']));
		if ($andWhere)	{
			$query.=' AND '.$andWhere;
		}

			// enablefields
		if ($table=='pages')	{
			$query.=' '.$GLOBALS['TSFE']->sys_page->where_hid_del.
						$GLOBALS['TSFE']->sys_page->where_groupAccess;
		} else {
			$query.=$this->parentObj->enableFields($table);
		}
			// We also allow sysfolders!
		$query = str_replace(' AND pages.doktype<200', '', $query);

			// MAKE WHERE:
		if ($query)	{
			$queryParts['WHERE'] = trim(substr($query,4));	// Stripping of " AND"...
			$query = 'WHERE '.$queryParts['WHERE'];
		}

			// GROUP BY
		if (trim($conf['groupBy']))	{
			$queryParts['GROUPBY'] = trim($conf['groupBy']);
			$query.=' GROUP BY '.$queryParts['GROUPBY'];
		}

			// ORDER BY
		if (trim($conf['orderBy']))	{
			$queryParts['ORDERBY'] = trim($conf['orderBy']);
			$query.=' ORDER BY '.$queryParts['ORDERBY'];
		}

			// Return result:
		return $returnQueryArray ? $queryParts : $query;
	}

	function checkPidArray($listArr)	{
		$outArr = Array();
		if (is_array($listArr) && count($listArr))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid IN ('.implode(',',$listArr).')'.$this->parentObj->enableFields('pages').' AND doktype NOT IN ('.$this->parentObj->checkPid_badDoktypeList.')');
			if ($error = $GLOBALS['TYPO3_DB']->sql_error())	{
				$GLOBALS['TT']->setTSlogMessage($error.': '.$query,3);
			} else {
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$outArr[] = $row['uid'];
				}
			}
			if (in_array(0, $listArr, true)) {
				$outArr[] = 0;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $outArr;
	}

}
