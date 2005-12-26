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

class LogObject {
	var $text;
	var $db;
	var $user;
	var $timestamp;
	var $commandNumber = 0;
	var $duration = false;
	var $ignored;
	var $context;
	var $complete = false;

	function LogObject($user, $db, $text = '', $ignored = false) {
		$this->user = $user;
		$this->db = $db;
		$this->text = $text;
		$this->ignored = $ignored;
	}
	
	function setContextInformation($timestamp, $commandNumber) {
		$this->timestamp = $timestamp;
		$this->commandNumber = $commandNumber; 
	}
	
	function getCommandNumber() {
		return $this->commandNumber;
	}
	
	function getTimestamp() {
		return $this->timestamp;
	}
	
	function getEventType() {
		return false;
	}

	function append($text) {
		if(DEBUG > 1 && !$text) stderr('Empty text for append', true);
		$this->text .= ' '.$text;
	}
	
	function setContext($context) {
		$this->context = normalizeWhitespaces($context);
	}
	
	function getNormalizedText() {
		$regexpRemoveText = "/'[^']*'/";
		$regexpRemoveNumbers = '/([^a-zA-Z_\$])([0-9]{1,10})/';

		$text = '';
		if($this->text) {
			$text = normalizeWhitespaces($this->text);
			$text = str_replace("\\'", '', $text);
			$text = preg_replace($regexpRemoveText, "''", $text);
			$text = preg_replace($regexpRemoveNumbers, '${1}0', $text);
		}
		return $text;
	}
	
	function accumulateTo(& $accumulator) {
		if(!$this->isIgnored()) {
			$this->text = normalizeWhitespaces($this->text);
			$accumulator->fireEvent($this);
		}
	}

	function isIgnored() {
		return $this->ignored;
	}
	
	function getDb() {
		return $this->db;
	}
	
	function getUser() {
		return $this->user;
	}
	
	function getText() {
		return $this->text;
	}
	
	function getContext() {
		return $this->context;
	}
}

?>