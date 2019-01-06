<?php
namespace Froxlor\Frontend\Modules;

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright (c) the authors
 * @author Florian Lippert <flo@syscp.org> (2003-2009)
 * @author Froxlor team <team@froxlor.org> (2010-)
 * @license GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package Panel
 *         
 */

use Froxlor\Database\Database;
use Froxlor\Frontend\FeModule;

class AdminLogger extends FeModule
{

	public function overview()
	{
		if (\Froxlor\CurrentUser::getField('change_serversettings') != '1') {
			// not allowed
			\Froxlor\UI\Response::standard_error('noaccess', __METHOD__);
		}

		$fields = array(
			'date' => $lng['logger']['date'],
			'type' => $lng['logger']['type'],
			'user' => $lng['logger']['user'],
			'text' => $lng['logger']['action']
		);
		$paging = new \Froxlor\UI\Paging($userinfo, TABLE_PANEL_LOG, $fields, null, null, 0, 'desc', 30);
		$query = 'SELECT * FROM `' . TABLE_PANEL_LOG . '` ' . $paging->getSqlWhere(false) . ' ' . $paging->getSqlOrderBy();
		$result_stmt = Database::query($query . ' ' . $paging->getSqlLimit());
		$result_cnt_stmt = Database::query($query);
		$logs_count = $result_cnt_stmt->rowCount();
		$paging->setEntries($logs_count);
		$sortcode = $paging->getHtmlSortCode($lng);
		$arrowcode = $paging->getHtmlArrowCode($filename . '?page=' . $page . '&s=' . $s);
		$searchcode = $paging->getHtmlSearchCode($lng);
		$pagingcode = $paging->getHtmlPagingCode($filename . '?page=' . $page . '&s=' . $s);
		$clog = array();

		while ($row = $result_stmt->fetch(\PDO::FETCH_ASSOC)) {

			if (! isset($clog[$row['action']]) || ! is_array($clog[$row['action']])) {
				$clog[$row['action']] = array();
			}
			$clog[$row['action']][$row['logid']] = $row;
		}

		if ($paging->sortfield == 'date' && $paging->sortorder == 'desc') {
			krsort($clog);
		} else {
			ksort($clog);
		}

		$i = 0;
		$count = 0;
		$log_count = 0;
		$log = '';
		foreach ($clog as $action => $logrows) {
			$_action = 0;
			foreach ($logrows as $row) {
				// if ($paging->checkDisplay($i)) {
				$row = \Froxlor\PhpHelper::htmlentitiesArray($row);
				$row['date'] = date("d.m.y H:i:s", $row['date']);

				if ($_action != $action) {
					switch ($action) {
						case \Froxlor\FroxlorLogger::USR_ACTION:
							$_action = $lng['admin']['customer'];
							break;
						case \Froxlor\FroxlorLogger::RES_ACTION:
							$_action = $lng['logger']['reseller'];
							break;
						case \Froxlor\FroxlorLogger::ADM_ACTION:
							$_action = $lng['logger']['admin'];
							break;
						case \Froxlor\FroxlorLogger::CRON_ACTION:
							$_action = $lng['logger']['cron'];
							break;
						case \Froxlor\FroxlorLogger::LOGIN_ACTION:
							$_action = $lng['logger']['login'];
							break;
						case \Froxlor\FroxlorLogger::LOG_ERROR:
							$_action = $lng['logger']['intern'];
							break;
						default:
							$_action = $lng['logger']['unknown'];
							break;
					}

					$row['action'] = $_action;
					eval("\$log.=\"" . \Froxlor\UI\Template::getTemplate('logger/logger_action') . "\";");
				}

				$log_count ++;
				$row['type'] = \Froxlor\FroxlorLogger::getInstanceOf()->getLogLevelDesc($row['type']);
				eval("\$log.=\"" . \Froxlor\UI\Template::getTemplate('logger/logger_log') . "\";");
				$count ++;
				$_action = $action;
				// }
				$i ++;
			}
			$i ++;
		}

		eval("echo \"" . \Froxlor\UI\Template::getTemplate('logger/logger') . "\";");
	}

	public function truncate()
	{
		if (isset($_POST['send']) && $_POST['send'] == 'send') {
			$truncatedate = time() - (60 * 10);
			$trunc_stmt = Database::prepare("
				DELETE FROM `" . TABLE_PANEL_LOG . "` WHERE `date` < :trunc
			");
			Database::pexecute($trunc_stmt, array(
				'trunc' => $truncatedate
			));
			\Froxlor\FroxlorLogger::getLog()->addWarning('truncated the system-log (mysql)');
			\Froxlor\UI\Response::redirectTo('index.php?module=AdminLogger');
		} else {
			\Froxlor\UI\HTML::askYesNo('logger_reallytruncate', 'index.php?module=AdminLogger&view=' . __FUNCTION__, array(), TABLE_PANEL_LOG);
		}
	}
}