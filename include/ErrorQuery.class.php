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

class ErrorQuery extends Query {
	var $hint = '';
	var $detail = '';
	var $error = '';
	
	function ErrorQuery($text = 'No error message') {
		$this->error = $text;
		$this->Query($text);
	}
	
	function appendStatement($text) {
		if(DEBUG > 1 && empty($text)) stderr('Empty text for error statement', true);
		// the text may have been appended so we copy it in error before overwriting it
		$this->error = $this->text;
		
		$this->text = $text;
	}
	
	function appendHint($hint) {
		if(DEBUG > 1 && empty($hint)) stderr('Empty text for error hint', true);
		$this->hint = $hint;
	}
	
	function appendDetail($detail) {
		if(DEBUG > 1 && empty($detail)) stderr('Empty text for error detail', true);
		$this->detail = $detail;
	}
	
	function appendContext($context) {
		if(DEBUG > 1 && empty($context)) stderr('Empty text for error context', true);
		$this->context = $context;
	}
	
	function accumulateTo(& $accumulator) {
		if(!$this->isIgnored()) {
			$this->text = normalizeWhitespaces($this->text);
			$accumulator->appendError($this);
		}
	}
	
	function isIgnored() {
		 if($this->error == 'terminating connection due to administrator command' ||
		 	$this->error == 'the database system is shutting down'
		 	) {
		 	return true;
		 } else {
		 	return false;
		 }
	}
	
	function getError() {
		return $this->error;
	}
	
	function getHint() {
		return $this->hint;
	}
	
	function getDetail() {
		return $this->detail;
	}
}

?>