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

class LogObject {
	var $text;
	var $connectionId;
	var $database;
	var $user;
	var $timestamp;
	var $commandNumber = 0;
	var $ignored;
	var $context;
	var $notices = array();

	function LogObject($connectionId, $user, $database, $text = '', $ignored = false) {
		$this->connectionId = $connectionId;
		$this->user = $user;
		$this->database = $database;
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
	
	function addNotice($notice) {
		$this->notices[] = $notice;
	}
	
	function getNotices() {
		return $this->notices;
	}
	
	function setContext($context) {
		$this->context = normalizeWhitespaces($context);
	}
	
	function getNormalizedText() {
		$regexpRemoveText = "/'[^']*'/";
		$regexpRemoveNumbers = '/([^a-zA-Z_\$-])-?([0-9]{1,10})/';

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
		if(
			(CONFIG_DATABASE && $this->database != CONFIG_DATABASE) ||
			(CONFIG_USER && $this->user != CONFIG_USER) ||
			(CONFIG_TIMESTAMP_FILTER && ($this->timestamp < CONFIG_FROM_TIMESTAMP || $this->timestamp > CONFIG_TO_TIMESTAMP))
		) {
			$this->ignored = true;
		}
		return $this->ignored;
	}
	
	function getConnectionId() {
		return $this->connectionId;
	}
	
	function getDatabase() {
		return $this->database;
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
	
	function getDetailedInformation() {
		$detailedInformation = formatTimestamp($this->getTimestamp());
		if($this->getUser() && $this->getDatabase()) {
			$detailedInformation .= ' - '.$this->getUser().'@'.$this->getDatabase();
		}
		return $detailedInformation;
	}
}

?>