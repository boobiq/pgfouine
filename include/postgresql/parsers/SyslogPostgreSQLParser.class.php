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

class SyslogPostgreSQLParser extends PostgreSQLParser {
	var $regexpPostgresPid;
	
	function SyslogPostgreSQLParser($syslogString = 'postgres') {
		$this->regexpSyslogContext = new RegExp('/^([A-Z][a-z]{2} [ 0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) .*? '.$syslogString.'\[(\d{1,5})\]: \[(\d{1,10})(?:\-(\d{1,5}))?\] /');
	}

	function & parse($data) {
		$syslogContextMatch =& $this->regexpSyslogContext->match($data);
		if($syslogContextMatch === false) {
			return false;
		}
		
		$matches = $syslogContextMatch->getMatches();
		$text = $syslogContextMatch->getPostMatch();
		
		if(count($matches) < 4 || !$text) {
			return false;
		}
		
		$formattedDate = $matches[1][0];
		$timestamp = strtotime($formattedDate.' '.date('Y'));
		if($timestamp > time()) {
			$timestamp = strtotime($formattedDate.' '.(date('Y')-1));	
		}
		
		$connectionId = $matches[2][0];
		$commandNumber = $matches[3][0];
		
		if(isset($matches[4][0])) {
			$lineNumber = $matches[4][0];
		} else {
			$lineNumber = 1;
		}
		
		$line =& parent::parse($text);
		
		if(!$line) {
			return false;
		}
		
		$line->setContextInformation($timestamp, $connectionId, $commandNumber, $lineNumber);
		
		if($timestamp < getConfig('from_timestamp') || $timestamp > getConfig('to_timestamp')) {
			return false;
		}
		
		return $line;
	}
}

?>