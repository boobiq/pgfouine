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

class TsungSessionsReport extends Report {
	function TsungSessionsReport(& $reportAggregator) {
		$this->Report($reportAggregator, 'Tsung sessions', array('QueriesHistoryListener'), false);
	}

	function dumpText($file) {
		$listener =& $this->reportAggregator->getListener('QueriesHistoryListener');
		$queries =& $listener->getQueriesHistoryPerConnection();
		$sessionsCount = $listener->getConnectionCount();
		$probabilityLeft = 100;
		
		fwrite($file, '<sessions>'."\n");
		
		$lastConnectionId = null;
		$firstQueryInSession = null;

		$connection_index = 0;
		foreach ($queries as $query) {
			$connectionId = $query->getConnectionId();
			if ($lastConnectionId !== $connectionId) {
				if ($lastConnectionId !== null) {
					fwrite($file, "\t".'</session>'."\n");
					$connection_index++;
				}

				$lastConnectionId = $connectionId;
				$prevQueryInSession = null;

				if($connection_index == ($sessionsCount - 1)) {
					$currentProbability = $probabilityLeft;
				} else {
					$currentProbability = (int) (100 / $sessionsCount);
					$probabilityLeft -= $currentProbability;
				}
				fwrite($file, "\t".'<session name="pgfouine-'.$connectionId.'" probability="'.$currentProbability.'" type="ts_pgsql">'."\n");
			}

			if($prevQueryInSession === null) {
				fwrite($file, "\t\t".'<request><pgsql type="connect" database="'.$query->getDatabase().'" username="'.$query->getUser().'" /></request>'."\n");
			} else {
				$thinkTime = (int) ($query->getTimestamp() - ($prevQueryInSession->getTimestamp() + $prevQueryInSession->getDuration()));
				if($thinkTime >= 1) {
					fwrite($file, "\t\t".'<thinktime random="true" value="'.$thinkTime.'" />'."\n");
				}
			}
			fwrite($file, "\t\t".'<request><pgsql type="sql"><![CDATA['.$query->getText().']]></pgsql></request>'."\n");			
			$prevQueryInSession = $query;
		}
		
		fwrite($file, "\t".'</session>'."\n");
		
		fwrite($file, '</sessions>'."\n");
	}
	
	function dumpHtml($file) {
		$html = '<p>Report not supported by HTML format</p>';
		fwrite($file, $html);
	}
}

