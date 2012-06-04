<?php

class BurdenQueriesReport extends NormalizedReport {
	function BurdenQueriesReport(& $reportAggregator) {
        $this->threshold = 0.05;
        $t = round($this->threshold*100, 1);
		$this->NormalizedReport(
            $reportAggregator,
            "Queries taking more than {$t}% of total metric",
            array('GlobalCountersListener', 'NormalizedQueriesListener')
        );
	}

    function __getBadQueries() {
        $badQueries = array();
        $globalListener = $this->reportAggregator->getListener('GlobalCountersListener');
        $normalizedListener = $this->reportAggregator->getListener('NormalizedQueriesListener');

        // Queries taking too much time
        $period = $globalListener->lastQueryTimestamp - $globalListener->firstQueryTimestamp;
        $queries = $normalizedListener->getQueriesMostTime();
        $durationUnitSeconds = CONFIG_DURATION_UNIT == 'ms'? 0.001 : 1;
        $totalDurationThreshold = $this->threshold * $period / $durationUnitSeconds;

        foreach ($queries as $query) {
            if ($query->getTotalDuration() > $totalDurationThreshold) {
                $badQueries[] = $query;
                #'share' => $durationUnitSeconds * $query->getTotalDuration() / ($period*100),
            }
        }

        // Queries executed too many times
        $queries = $normalizedListener->getMostFrequentQueries();
        $totalQueryCount = $globalListener->getQueryCount();
        $countThreshold = $this->threshold * $totalQueryCount;

        foreach ($queries as $query) {
            if ($query->getTimesExecuted() > $countThreshold) {
                $unique = true;
                foreach ($badQueries as $q) {
                    if ($q->getNormalizedText() === $query->getNormalizedText()) {
                        $unique = false;
                        break;
                    }
                }
                if ($unique) {
                    $badQueries[] = $query;
                }
                # 'share' => $durationUnitSeconds * $query->getTotalDuration() / ($totalQueryCount*100),
            }
        }

        return $badQueries;
    }
	
	function getText() {
		$text = '';
		
		$queries = $this->__getBadQueries();
		
		$i = 0;
        foreach ($queries as $query) {
			$text .= ($i+1).') '.$this->formatLongDuration($query->getTotalDuration()).' - '.$this->formatInteger($query->getTimesExecuted()).' - '.$this->shortenQueryText($query->getNormalizedText())."\n";
			$text .= "--\n";
            $i += 1;
		}
		return $text;
	}
	
	function getHtml() {
        $queries = $this->__getBadQueries();

		$html = '
<table class="queryList">
	<tr>
		<th>Rank</th>
		<th>Total duration</th>
		<th>Times executed</th>
		<th>Av.&nbsp;duration&nbsp;('.CONFIG_DURATION_UNIT.')</th>
		<th>Query</th>
	</tr>';

		$i = 0;
        foreach ($queries as $query) {
			$html .= '<tr class="'.$this->getRowStyle($i).'">
				<td class="center top">'.($i+1).'</td>
				<td class="relevantInformation top center">'.$this->formatLongDuration($query->getTotalDuration()).'</td>
				<td class="top center"><div class="tooltipLink"><span class="information">'.$this->formatInteger($query->getTimesExecuted()).'</span>'.$this->getHourlyStatisticsTooltip($query).'</div></td>
				<td class="top center">'.$this->formatDuration($query->getAverageDuration()).'</td>
				<td>'.$this->getNormalizedQueryWithExamplesHtml($i, $query).'</td>
			</tr>';
			$html .= "\n";
            $i += 1;
		}
		$html .= '</table>';
		return $html;
	}
}
