<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2005 Guillaume Smet
 *
 * pgFouine is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * pgFouine is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with pgFouine; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

class PostgreSQLContextLine extends PostgreSQLLogLine {
	var $ignore = false;
	var $recognized = true;

	function PostgreSQLContextLine($text) {
		global $postgreSQLRegexps;
		
		$statementMatch =& $postgreSQLRegexps['ContextSqlStatement']->match($text);
		if($statementMatch) {
			$this->PostgreSQLLogLine(substr($statementMatch->getPostMatch(), -1, 1));
		} else {
			$functionMatch =& $postgreSQLRegexps['ContextSqlFunction']->match($text);
			if($functionMatch) {
				$this->PostgreSQLLogLine($functionMatch->getMatch(2));
			} else {
				$this->recognized = false;
				$this->PostgreSQLLogLine($text);
			}
		}
	}

	function appendTo(& $logObject) {
		if(is_a($logObject, 'ErrorLogObject')) {
			// we have an error query so we put the context in a subquery
			$lastLogObject->addContext($this->text);
		} else {
			if(DEBUG && !$this->recognized) stderr('Unrecognized context or context for an error', true);
			$logObject->addContext($this->text);
		}
		return false;
	}
}

?>