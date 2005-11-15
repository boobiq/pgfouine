<?php

class NormalizedQueriesMostTimeReport extends Report {
	function NormalizedQueriesMostTimeReport(& $reportAggregator) {
		$this->Report($reportAggregator, 'Queries that took up the most time - normalized', array('NormalizedQueriesListener'));
	}
	
	function getText() {
		$listener = $this->reportAggregator->getListener('NormalizedQueriesListener');
		$text = '';
		
		$queries =& $listener->getQueriesMostTime();
		
		$count = count($queries);
		
		for($i = 0; $i < $count; $i++) {
			$query =& $queries[$i];
			$text .= ($i+1).') '.$this->formatDuration($query->getTotalDuration()).' - '.$query->getTimesExecuted().' - '.$query->getNormalizedText()."\n";
			$text .= "--\n";
		}
		return $text;
	}
	
	function getHtml() {
		$listener = $this->reportAggregator->getListener('NormalizedQueriesListener');
		$html = '
<table class="queryList">
	<tr>
		<th>Rank</th>
		<th>Total time&nbsp;(s)</th>
		<th>Times executed</th>
		<th>Average time&nbsp;(s)</th>
		<th>Query</th>
	</tr>';
		$queries =& $listener->getQueriesMostTime();
		$count = count($queries);
		
		for($i = 0; $i < $count; $i++) {
			$query =& $queries[$i];
			$html .= '<tr class="'.$this->getRowStyle($i).'">
				<td class="center top">'.($i+1).'</td>
				<td class="relevantInformation top center">'.$this->formatDuration($query->getTotalDuration()).'</td>
				<td class="top center">'.$query->getTimesExecuted().'</td>
				<td class="top center">'.$this->formatDuration($query->getAverageDuration()).'</td>
				<td>'.$this->highlightSql($query->getNormalizedText()).'</td>
			</tr>';
			$html .= "\n";
		}
		$html .= '</table>';
		return $html;
	}
}

?>