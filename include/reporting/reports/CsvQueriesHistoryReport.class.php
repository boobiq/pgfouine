<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2006-2009 Guillaume Smet
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

/*
Table schema:
create table log (
id integer,
date timestamp,
connection_id integer,
database text,
"user" text,
duration float,
query text);

log=# COPY log FROM 'pgfouine-output.csv' WITH CSV;
*/

class CsvQueriesHistoryReport extends Report {
	function CsvQueriesHistoryReport(& $reportAggregator) {
		$this->Report($reportAggregator, 'Queries history in CSV format', array('QueriesHistoryListener'), false);
	}
	
	function dumpText($file) {
		$listener =& $this->reportAggregator->getListener('QueriesHistoryListener');
		
		$queries =& $listener->getQueriesHistory();
		$i = 0;
		foreach ($queries as $query) {
			$line = array(
				$i+1,
				$this->formatTimestamp($query->getTimestamp()),
				$query->getConnectionId(),
				$query->getDatabase(),
				$query->getUser(),
				$query->getDuration(),
				$query->getText(),
			);
			$line = str_putcsv($line, ',', '"');
			fwrite($file, $line);
			$i += 1;
		}
	}
	
	function dumpHtml($file) {
		fwrite($file, '<p>Report not supported by HTML format</p>');
	}
}

