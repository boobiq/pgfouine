<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2006-2008 Guillaume Smet
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

class QueriesHistoryListener extends QueryListener {
	var $counter = 0;
	var $bulk = 10000;

	function __construct() {
		$this->dbFile = tempnam(sys_get_temp_dir(), 'pgfouine-history');
		$this->db = new SQLite3($this->dbFile);
		$this->db->exec('CREATE TABLE log (timestamp INTEGER, connection_id INTEGER, data BLOB)');
		$this->db->exec('BEGIN');
		$this->inTransaction = true;
	}

	function __destruct() {
		unlink($this->dbFile);
	}

	private function ensureCommit() {
		if ($this->inTransaction) {
			$this->db->exec('COMMIT');
			$this->inTransaction = false;
		}
	}

	function fireEvent($logObject) {
		$this->counter ++;
		$logObject->setNumber($this->counter);

		$stmt = $this->db->prepare('INSERT INTO log VALUES (:timestamp, :connection_id, :data)');
		$stmt->bindValue(':timestamp', $logObject->getTimestamp(), SQLITE3_INTEGER);
		$stmt->bindValue(':connection_id', $logObject->getConnectionId(), SQLITE3_INTEGER);
		$stmt->bindValue(':data', serialize($logObject), SQLITE3_BLOB);
		$stmt->execute();

		if (($this->counter % $this->bulk) == 0) {
			$this->db->exec('COMMIT');
			$this->db->exec('BEGIN');
		}
	}
	
	function getQueriesHistory() {
		$this->ensureCommit();
		$this->db->exec('CREATE INDEX IF NOT EXISTS idx_log_timestamp ON log (timestamp ASC)');
		$result = $this->db->query('SELECT data FROM log ORDER BY timestamp ASC');
		return new DBResultIterator($result);
	}
	
	function getQueriesHistoryPerConnection() {
		$this->ensureCommit();
		$this->db->exec('CREATE INDEX IF NOT EXISTS idx_log_conn_id ON log (connection_id ASC)');
		$result = $this->db->query('SELECT data FROM log ORDER BY connection_id ASC');
		return new DBResultIterator($result);
	}

	function getConnectionCount() {
		$this->ensureCommit();
		return $this->db->querySingle('SELECT COUNT(DISTINCT connection_id) FROM log');
	}
}

class DBResultIterator implements Iterator {
	function __construct($result) {
		$this->result = $result;
		$this->valueSet = false;
		$this->value = null;
		$this->position = 0;
	}

	function rewind() {
		$this->result->reset();
		$this->position = 0;
	}

	function current() {
		if (!$this->valueSet) {
			$this->next();
		}
		return $this->value;
	}

	function key() {
		return $this->position;
	}

	function next() {
		$row = $this->result->fetchArray(SQLITE3_NUM);
		if ($row === false) {
			$this->value = null;
			$this->valid = false;
		} else {
			$this->valid = true;
			$this->value = unserialize(reset($row));
			$this->position++;
		}
		$this->valueSet = true;
	}

	function valid() {
		if (!$this->valueSet) {
			$this->next();
		}

		return $this->valid;
	}
}

