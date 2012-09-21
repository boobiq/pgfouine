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

class HtmlReportAggregator extends ReportAggregator {
	var $geshi;
	var $stylesheets = array('common.css');
	var $scripts = array();
	
	function HtmlReportAggregator(& $logReader, $outputFilePath = false) {
		$this->ReportAggregator($logReader, $outputFilePath);
		
		$this->geshi = new GeSHi('', 'sql');
		$this->geshi->enable_classes();
		$this->geshi->set_header_type(GESHI_HEADER_NONE);
	}
	
	function addStylesheet($stylesheetPath) {
		$this->stylesheets[] = $stylesheetPath;
	}
	
	function addScript($scriptPath) {
		$this->scripts[] = $scriptPath;
	}
	
	function highlightSql($sql, $prepend = '', $append = '') {
		if(substr($sql, -1, 1) != ';') {
			$sql .= ';';
		}
		$this->geshi->set_source($sql);
		return '<div class="sql">'.$prepend.$this->geshi->parse_code().$append.'</div>';
	}
	
	protected function getHeader() {
		$header = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>'.htmlspecialchars(CONFIG_REPORT_TITLE).'</title>
		<style type="text/css">
			'.$this->getStyles().'
		</style>
		<script type="text/javascript">
			 /* <![CDATA[ */
			function toggle(idButton, idDiv, label) {
				if(document.getElementById(idDiv)) {
					if(document.getElementById(idDiv).style.display == \'none\') {
						document.getElementById(idDiv).style.display = \'block\';
						document.getElementById(idButton).value = \'Hide \'+label;
					} else {
						document.getElementById(idDiv).style.display = \'none\';
						document.getElementById(idButton).value = \'Show \'+label;
					}
				}
			}

			'.$this->getScripts().'
			/* ]]> */
		</script>
	</head>
	<body>
		<div id="content">
			<h1 id="top">'.htmlspecialchars(CONFIG_REPORT_TITLE).'</h1>
		';
		return $header;
	}
	
	protected function dumpBody($file) {
		$count = count($this->reportBlocks);
		
		$menu = '<div class="menu">';
		
		$hasNormalizedReports = false;
		
		for($i = 0; $i < $count; $i++) {
			$reportBlock =& $this->reportBlocks[$i];
			if($i > 0) {
				$menu .= ' | ';
			}
			$menu .= '<a href="#'.$reportBlock->getReportClass().'">'.$reportBlock->getTitle().'</a>';
			
			if(is_a($reportBlock, 'NormalizedReport') || is_a($reportBlock, 'NormalizedErrorsReport')) {
				$hasNormalizedReports = true;
			}
			
			unset($reportBlock);
		}
		$menu .= '</div>';
		
		$output = $menu."\n";
		
		if($hasNormalizedReports) {
			$output .= '<p>Normalized reports are marked with a "(N)".</p>';
		}
		
		$output .= '<div class="information"><ul>'.
			'<li>Generated on '.date('Y-m-d H:i').'</li>'.
			'<li>Parsed '.$this->getFileName().' ('.$this->formatInteger($this->getLineParsedCount()).' lines) in '.$this->formatLongDuration($this->getTimeToParse(), 0).'</li>';
		if($this->getFirstLineTimestamp() || $this->getLastLineTimestamp()) {
			$output .= '<li>Log from '.$this->formatTimestamp($this->getFirstLineTimestamp()).' to '.$this->formatTimestamp($this->getLastLineTimestamp()).'</li>';
		}
		if($hostname = getenv('HOSTNAME'))	{
			$output .= '<li>Executed on '.$hostname.'</li>';
		}
		if(CONFIG_FILTER) {
			$output .= '<li><strong>Filtered on '.CONFIG_FILTER.'</strong></li>';
		}
		$output .= '</ul></div>';
		fwrite($file, $output);
		unset($output);
		
		
		fwrite($file, '<div class="reports">');

		for($i = 0; $i < $count; $i++) {
			$reportBlock =& $this->reportBlocks[$i];
			fwrite($file, $reportBlock->getHtmlTitle());
			$this->dumpHtmlOutput($file, $reportBlock);
			fwrite($file, "\n");

			if(is_a($reportBlock, 'NormalizedReport') || is_a($reportBlock, 'NormalizedErrorsReport')) {
				$hasNormalizedReports = true;
			}

			unset($reportBlock);
		}

		fwrite($file, '</div>');
	}
	
	protected function dumpHtmlOutput($file, $reportBlock) {
		$reportBlock->dumpHtml($file);
	}
	
	protected function getFooter() {
		$footer = '
			<div class="footer">
				Report generated by <a href="http://pgfouine.projects.postgresql.org/">pgFouine</a> '.VERSION.'. pgFouine is free software.
			</div>
		</div>
		<div id="littleToc">
			<div id="littleTocContent">
				<ul>
					<li><a href="#top">^ Back to top</a></li>';
		for($i = 0, $count = count($this->reportBlocks); $i < $count; $i++) {
			$reportBlock =& $this->reportBlocks[$i];
			$footer .= '<li><a href="#'.$reportBlock->getReportClass().'">'.$reportBlock->getTitle().'</a></li>';
		}
		$footer .= '
				</ul>
			</div>
			<div id="littleTocTitle">Table of contents</div>
		</div>
	</body>
</html>';
		return $footer;
	}
	
	function getStyles() {
		$styles = '';
		$this->stylesheets = array_unique($this->stylesheets);
		foreach($this->stylesheets AS $stylesheetPath) {
			$styles .= "\n/* ".$stylesheetPath." */\n";
			$styles .= $this->getWebFileContent('css/'.$stylesheetPath);
			$styles .= "\n";
		}
		$styles .= $this->geshi->get_stylesheet();
		return $styles;
	}
	
	function getScripts() {
		$scripts = '';
		$this->scripts = array_unique($this->scripts);
		foreach($this->scripts AS $scriptPath) {
			$scripts .= "\n/* ".$scriptPath." */\n";
			$scripts .= $this->getWebFileContent('js/'.$scriptPath);
			$scripts .= "\n";
		}
		return $scripts;
	}
	
	function getWebFileContent($path) {
		ob_start();
		include('web/'.$path);
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	function formatRealQuery(& $query, $prepend = '', $append = '') {
		$html = $this->highlightSql($this->shortenQueryText($query->getText()), $prepend, $append);
		$notices = $query->getNotices();
		foreach($notices AS $notice) {
			$html .= '<div class="queryNotice">Notice: '.$notice.'</div>';
		}
		if($query->getLocation()) {
			$html .= '<div class="queryNotice">Location: '.$query->getLocation().'</div>';
		}
		return $html;
	}
	
	function getWebContent() {
		
	}
}

