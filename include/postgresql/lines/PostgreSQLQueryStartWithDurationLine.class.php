<?php

class PostgreSQLQueryStartWithDurationLine extends PostgreSQLQueryStartLine {
	var $ignore = false;
	
	function PostgreSQLQueryStartWithDurationLine($text, $timeString, $unit) {
		$regexp = new RegExp('/[\s]*(query|statement):[\s]*/i');
		if($match = $regexp->match($text)) {
			$this->PostgreSQLQueryStartLine($match->getPostMatch(), $this->parseDuration($timeString, $unit));
		} else {
			stderr('Found garbage after Duration line: '.$text);
			$this->PostgreSQLQueryStartLine($text, $this->parseDuration($timeString, $unit));
		}
	}

	function & appendTo(& $queries) {
		$queries->gotDuration();
		$closedQuery = $queries->pop();
		
		$query = new Query($this->text, $this->ignore);
		$query->setDuration($this->duration);
		$queries->push($query);
		
		return $closedQuery;
	}
}

?>