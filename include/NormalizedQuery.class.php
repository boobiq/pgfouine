<?php

/*
 * This file is part of pgFouine.
 * 
 * pgFouine - a PostgreSQL log analyzer
 * Copyright (c) 2005-2006 Guillaume Smet
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

class NormalizedQuery {
	var $normalizedText;
	var $duration = 0;
	var $count = 0;
	var $examples = false;
	
	function NormalizedQuery(& $query) {
		$this->normalizedText = $query->getNormalizedText();
		$maxExamples = getConfig('max_number_of_examples');
		if($maxExamples) {
			$this->examples = new SlowestQueryList($maxExamples);
		}
		
		$this->addQuery($query);
	}
	
	function addQuery(& $query) {
		$this->count ++;
		$this->duration += $query->getDuration();
		if($this->examples) {
			if($this->count == 1) {
				$this->examples->addQuery($query);
			} else {
				if(intval(rand(1, 100)) == 50) {
					$this->examples->addQuery($query);
				}
			}
		}
	}
	
	function getNormalizedText() {
		return $this->normalizedText;
	}
	
	function getTotalDuration() {
		return $this->duration;
	}
	
	function getTimesExecuted() {
		return $this->count;
	}
	
	function getAverageDuration() {
		$average = 0;
		if($this->count > 0) {
			$average = ($this->duration/$this->count);
		}
		return $average;
	}
	
	function & getFilteredExamplesArray() {
		$returnExamples = false;
		
		$examples =& $this->examples->getSortedQueries();
		$exampleCount = count($examples);
		for($i = 0; $i < $exampleCount; $i++) {
			$example =& $examples[$i];
			if($example->getText() != $this->getNormalizedText()) {
				return $examples;
			}
			unset($example);
		}
		return array();
	}
}

?>