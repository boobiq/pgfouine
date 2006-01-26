<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2005-2006 Guillaume Smet
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

class PostgreSQLQueryStartLine extends PostgreSQLLogLine {
	function PostgreSQLQueryStartLine($text, $duration = false) {
		$this->PostgreSQLLogLine($this->filterQuery($text), $duration);
	}

	function filterQuery($text) {
		$loweredText = strtolower(trim($text));
		$this->ignore = (strpos($loweredText, 'begin') !== false) || (strpos($loweredText, 'vacuum') !== false) || ($loweredText == 'select 1');
		return $text;
	}
	
	function & getLogObject(& $logStream) {
		$query = new QueryLogObject($logStream->getUser(), $logStream->getDb(), $this->text, $this->ignore);
		$query->setContextInformation($this->timestamp, $this->commandNumber);
		
		return $query;
	}
	
	function appendTo(& $logObject) {
		$query = new QueryLogObject($logObject->getUser(), $logObject->getDb(), $this->text, $this->ignore);
		$query->setContextInformation($this->timestamp, $this->commandNumber);
		
		$logObject->addSubQuery($logObject);
	}
}

?>