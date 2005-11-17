<?php

class ReportAggregator {
	var $reports = array();
	var $logReader;
	
	function ReportAggregator(& $logReader) {
		$this->logReader =& $logReader;
	}
	
	function addReport($reportName) {
		$report = new $reportName($this);
		$this->reports[] =& $report;
	}
	
	function & getListener($listenerName) {
		return $this->logReader->getListener($listenerName);
	}
	
	function getOutput() {
		$needs = $this->getNeeds();
		foreach($needs AS $need) {
			$this->logReader->addListener($need);
		}
		$this->logReader->parse();
		
		$output = '';
		$output .= $this->getHeader();
		$output .= $this->getBody();
		$output .= $this->getFooter();
		
		return $output;
	}
	
	function getNeeds() {
		$needs = array();
		$count = count($this->reports);
		for($i = 0; $i < $count; $i++) {
			$needs = array_merge($needs, $this->reports[$i]->getNeeds());
		}
		$needs = array_unique($needs);
		return $needs;
	}
	
	function getFileName() {
		return $this->logReader->getFileName();
	}
	
	function getTimeToParse() {
		return $this->logReader->getTimeToParse();
	}
	
	function getLineParsedCount() {
		return $this->logReader->getLineParsedCount();
	}
	
	function pad($string, $length) {
		return str_pad($string, $length, ' ', STR_PAD_LEFT);
	}
	
	function getPercentage($number, $total) {
		return $this->pad(number_format($number*100/$total, 1), 5);
	}
	
	function formatDuration($duration, $decimals = 2) {
		return number_format($duration, $decimals);
	}
	
	function formatLongDuration($duration, $decimals = 1) {
		$formattedDuration = '';
		
		if($duration > 60) {
			$duration = intval($duration);
			if($duration > 3600) {
				$formattedDuration .= intval($duration/3600).'h';
				$duration = $duration % 3600;
			}
			if($duration > 59) {
				$minutes = intval($duration/60);
				if(!empty($formattedDuration)) {
					$minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
				}
				$formattedDuration .= $minutes.'m';
				$duration = $duration % 60;
			}
			if($duration > 0) {
				$formattedDuration .= intval($duration).'s';
			}
		} else {
			$formattedDuration = $this->formatDuration($duration, $decimals).'s';
		}
		
		return $formattedDuration;
	}
}

?>