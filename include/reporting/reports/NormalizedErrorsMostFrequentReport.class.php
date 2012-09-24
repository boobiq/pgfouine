<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2005-2008 Guillaume Smet
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

class NormalizedErrorsMostFrequentReport extends NormalizedErrorsReport {
	function NormalizedErrorsMostFrequentReport(& $reportAggregator) {
		$this->NormalizedErrorsReport($reportAggregator, 'Most frequent errors');
	}
	
	function dumpText($file) {
		$listener =& $this->reportAggregator->getListener('NormalizedErrorsListener');
		
		$errors =& $listener->getMostFrequentErrors();
		
		$i = 0;
		foreach ($errors as $error) {
			$text = ($i+1).') '.$this->formatInteger($error->getTimesExecuted()).' - '.$error->getNormalizedText()."\n";
			if($error->isTextAStatement()) {
				$text .= 'Error: '.$error->getError()."\n";
			}
			if($error->getDetail()) {
				$text .= 'Detail: '.$error->getDetail()."\n";
			}
			if($error->getHint()) {
				$text .= 'Hint: '.$error->getHint()."\n";
			}
			$text .= "--\n";
			fwrite($file, $text);
			$i++;
		}
	}
	
	function dumpHtml($file) {
		$listener =& $this->reportAggregator->getListener('NormalizedErrorsListener');
		$errors =& $listener->getMostFrequentErrors();
		$found = false;
		
		if(true) {
			$i = 0;
			foreach ($errors as $error) {
				if (!$found) {
					$found = true;
					fwrite($file, '
<table class="queryList">
	<tr>
		<th>Rank</th>
		<th>Times reported</th>
		<th>Error</th>
	</tr>');
				}
				
				$html = '<tr class="'.$this->getRowStyle($i).'">
					<td class="center top">'.($i+1).'</td>
					<td class="relevantInformation top center"><div class="tooltipLink"><span class="information">'.$this->formatInteger($error->getTimesExecuted()).'</span>'.$this->getHourlyStatisticsTooltip($error).'</div></td>
					<td><div class="error">Error: '.htmlspecialchars($error->getError()).'</div>';
				if($error->getDetail() || $error->getHint()) {
					$html .= '<div class="errorInformation">';
					if($error->getDetail()) {
						$html .= 'Detail: '.htmlspecialchars($error->getDetail());
						$html .= '<br />';
					}
					if($error->getHint()) {
						$html .= 'Hint: '.htmlspecialchars($error->getHint());
						$html .= '<br />';
					}
					$html .= '</div>';
				}
				if($error->isTextAStatement()) {
					$html .= $this->highlightSql($error->getNormalizedText());
				}
				$html .= $this->getNormalizedErrorWithExamplesHtml($i, $error).'</td>
				</tr>';
				$html .= "\n";
				fwrite($file, $html);
				$i += 1;
			}
			if (!$found) {
				fwrite($file, '<p>No error found</p>');
			} else {
				fwrite($file, '</table>');
			}
		}
	}
}

