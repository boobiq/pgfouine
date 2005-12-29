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

class LogBlock {
	var $logStream;
	var $commandNumber;
	var $lines = array();
	var $host = '';
	var $port = '';
	var $user = '';
	var $db = '';
	var $complete = false;
	
	function LogBlock($logStream, $commandNumber, & $line) {
		$this->logStream =& $logStream;
		$this->commandNumber = $commandNumber;
		$this->addLine($line);
	}
	
	function getCommandNumber() {
		return $this->commandNumber;
	}
	
	function & getLines() {
		return $this->lines;
	}
	
	function addLine(& $line) {
		$this->complete = $this->complete || $line->complete();
		$this->lines[] =& $line;
	}
	
	function isComplete() {
		return $this->complete;
	}
	
	function close() {
		$count = count($this->lines);
		$logObject =& $this->lines[0]->getLogObject($this->logStream);
				
		if($logObject) {
			for($i = 1; $i < $count; $i++) {
				$this->lines[$i]->appendTo($logObject);
			}
		}
		return $logObject;
	}
}

?>