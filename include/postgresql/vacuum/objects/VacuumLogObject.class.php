<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2006 Open Wide
 * Copyright (c) 2006 Guillaume Smet
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

class VacuumLogObject {
	var $database;
	var $schema;
	var $table;
	

	function VacuumLogObject($database, $schema, $table, $ignored = false) {
		$this->database = $database;
		$this->schema = $schema;
		$this->table = $table;
		$this->ignored = $ignored;
	}
	
	function getEventType() {
		return false;
	}

	function accumulateTo(& $accumulator) {
		if(!$this->isIgnored()) {
			$accumulator->fireEvent($this);
		}
	}

	function isIgnored() {
		return $this->ignored;
	}
	
	function getDatabase() {
		return $this->database;
	}
	
	function getSchema() {
		return $this->schema;
	}
	
	function getTable() {
		return $this->table;
	}
}

?>