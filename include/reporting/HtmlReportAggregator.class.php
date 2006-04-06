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

class HtmlReportAggregator extends ReportAggregator {
	var $geshi;
	
	function HtmlReportAggregator(& $logReader, $outputFilePath = false) {
		$this->ReportAggregator($logReader, $outputFilePath);
		
		$this->geshi = new GeSHi('', 'sql');
		$this->geshi->enable_classes();
		$this->geshi->set_header_type(GESHI_HEADER_NONE);
	}
	
	function highlightSql($sql, $prepend = '', $append = '') {
		if(substr($sql, -1, 1) != ';') {
			$sql .= ';';
		}
		$this->geshi->set_source($sql);
		return '<div class="sql">'.$prepend.$this->geshi->parse_code().$append.'</div>';
	}
	
	function getHeader() {
		$header = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>'.htmlspecialchars(CONFIG_REPORT_TITLE).'</title>
		<style type="text/css">
			'.$this->getStyles().'
		</style>
		<script type="text/javascript">
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
		</script>
	</head>
	<body>
		<div id="content">
			<h1 id="top">'.htmlspecialchars(CONFIG_REPORT_TITLE).'</h1>
		';
		return $header;
	}
	
	function getBody() {
		$count = count($this->reportBlocks);
		
		$reportsOutput = '';
		$menu = '<div class="menu">';
		
		for($i = 0; $i < $count; $i++) {
			$reportBlock =& $this->reportBlocks[$i];
			if($i > 0) {
				$menu .= ' | ';
			}
			$menu .= '<a href="#'.$reportBlock->getReportClass().'">'.$reportBlock->getTitle().'</a>';
			$reportsOutput .= $reportBlock->getHtmlTitle();
			$reportsOutput .= $this->getHtmlOutput($reportBlock);
			$reportsOutput .= "\n";
		}
		$menu .= '</div>';
		
		$output = $menu."\n";
		
		$output .= '<p>Normalized reports are marked with a "(N)".</p>';
		
		$output .= '<div class="information"><ul>'.
			'<li>Generated on '.date('Y-m-d H:i').'</li>'.
			'<li>Parsed '.$this->getFileName().' ('.$this->formatInteger($this->getLineParsedCount()).' lines) in '.$this->formatLongDuration($this->getTimeToParse(), 0).'</li>';
		$output .= '<li>Log from '.$this->formatTimestamp($this->getFirstLineTimestamp()).' to '.$this->formatTimestamp($this->getLastLineTimestamp()).'</li>';
		if($hostname = getenv('HOSTNAME'))	{
			$output .= '<li>Executed on '.$hostname.'</li>';
		}
		$output .= '</ul></div>';
		
		$output .= '<div class="reports">';
		$output .= $reportsOutput;
		$output .= '</div>';
		
		return $output;
	}
	
	function getHtmlOutput(& $reportBlock) {
		return $reportBlock->getHtml();
	}
	
	function getFooter() {
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
		$styles = '
			body { background-color: #FFFFFF; }
			* { font-family: Verdana, Arial, Helvetica; }
			div, p, th, td { font-size:12px; }
			h1 { font-size:16px; color:#FFFFFF; font-weight:normal; padding:6px; background-color:rgb(180, 80, 80); margin-bottom:0px; }
			h2 { margin-top:15px; margin-bottom:10px; font-weight:normal; font-size:14px; padding:2px 10px 2px 0px; border-bottom:1px solid #7B8CBE; color:#7B8CBE; }
			h2 a, h2 a:hover { color:black; text-decoration:none; }
			div.menu { background-color:rgb(220,230,252); padding:4px; margin-bottom:20px; }
			div.menu a { padding-right:3px; padding-left:3px; color:black; text-decoration:none; }
			div.menu a:hover { padding-right:3px; padding-left:3px; padding-top:2px; padding-bottom:2px; text-decoration:none; background-color:rgb(180, 80, 80); color:white; -moz-border-radius:3px; }
			div.information { border:1px solid #FFB462; -moz-border-radius:6px;	padding:10px; margin-top:5px; background-color:#FEE3C4; }
			ul { padding-left: 14px; padding-top: 0px; padding-bottom: 0px; margin-bottom: 0px; margin-top: 0px; }
			ul li { list-style-type: square; }
			div.reports { padding:4px; }
			table.queryList td, table.queryList th { padding: 2px; }
			table.queryList th { background-color: #DDDDDD; border:1px solid #CCCCCC; }
			table.queryList tr.row0 td { background-color: #FFFFFF; border: 1px solid #EEEEEE; }
			table.queryList tr.row1 td { background-color: #EEEEEE; border: 1px solid #EEEEEE; }
			table.queryList td.top { vertical-align:top; }
			table.queryList td.right { text-align:right; }
			table.queryList td.center { text-align:center; }
			table.queryList td.relevantInformation { font-weight:bold; }
			table.queryList div.examples { background-color:#EBF0FC; border:1px solid #FFFFFF; -moz-border-radius:10px; padding:6px; margin:5px; }
			table.queryList div.examples div.example0 { padding:2px; }
			table.queryList div.examples div.example1 { background-color:#FFFFFF; padding:2px; border:1px solid #EBF0FC; -moz-border-radius:5px; }
			div.tooltipLink { position:relative; cursor:pointer; }
			div.tooltipLink span.information { border-bottom:1px dotted gray; z-index:10; }
			div.tooltipLink div.tooltip { display:none; background-color:#EBF0FC; border:1px solid #FFFFFF; -moz-border-radius:10px; padding:6px; width:250px; }
			div.tooltipLink div.tooltip table { background-color:white; width:250px; }
			div.tooltipLink div.tooltip table tr.row0 td { background-color: #FFFFFF; border: 1px solid #EEEEEE; }
			div.tooltipLink div.tooltip table tr.row1 td { background-color: #EEEEEE; border: 1px solid #EEEEEE; }
			div.tooltipLink div.tooltip th { font-size:10px; }
			div.tooltipLink div.tooltip td { font-size:9px; font-weight:normal; padding:1px; }
			div.tooltipLink:hover div.tooltip { display:block; z-index:20; position:absolute; top:1.5em; left:2em; }
			table.queryList div.error { color: #D53131; font-weight:bold; }
			table.queryList div.errorInformation { color: #8D8D8D; font-style:italic; }
			table.queryList input { border:1px solid black; background-color:#FFFFFF; padding:1px; font-size:11px; }
			div.footer { font-size:12px; margin-top:30px; margin-bottom:50px; background-color:rgb(180, 80, 80); padding:5px; text-align:right; color:white; }
			div.footer a, div.footer a:hover { color:white; text-decoration:underline; }

			div#littleToc { display:none; }
			html>body div#littleToc { display:block; background-color:white; color:black; position:fixed; bottom:10px; right:10px; width:160px; font-size:11px; text-align:left; border:1px dotted #BBBBBB; }
			div#littleToc div#littleTocContent { display:none; padding:2px; }
			div#littleToc:hover { width:205px; }
			div#littleToc:hover div#littleTocContent { display:block; border-right:5px solid #BBBBBB; }
		
			div#littleToc div#littleTocTitle { font-weight:bold; text-align:center;padding:2px; }
			div#littleToc:hover div#littleTocTitle { display:none; }
		
			div#littleToc ul { padding:0px; text-indent:0px; margin:0px; }
			div#littleToc li { font-size:11px; list-style-type:none; padding:0px; text-indent:0px; margin:0px; }
		
			div#littleToc a { color:#000000; padding:2px; margin:2px; display:block; text-decoration:none; border:1px solid #CCCCCC; }
			div#littleToc a:hover { text-decoration:none; background-color:#DDDDDD; }
		';
		$styles .= $this->geshi->get_stylesheet();
		return $styles;
	}
}

?>