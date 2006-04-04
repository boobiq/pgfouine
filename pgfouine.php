#! /usr/bin/php -qC
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

define('VERSION', '0.5.99');

ini_set('max_execution_time', 18000);
ini_set('memory_limit', '128M');

if(strpos(phpversion(), '4.4') === 0) {
	error_reporting(E_ALL - E_NOTICE);
} else {
	error_reporting(E_ALL);
}

require_once('include/lib/common.lib.php');
require_once('include/base.lib.php');
require_once('include/listeners/listeners.lib.php');
require_once('include/postgresql/postgresql.lib.php');
require_once('include/reporting/reports.lib.php');

$stderr = fopen('php://stderr', 'w');

function usage($error = false) {
	if($error) {
		stderr('Error: '.$error);
	}
	echo "\n";
	echo 'Usage: '.$GLOBALS['executable'].' -file <file> [-top <n>] [-format <format>] [-logtype <logtype>] [-report [outputfile=]<block1,block2>]
  -file <file>                           log file to analyze
  -                                      read the log from stdin instead of -file
  -top <n>                               number of queries in lists. Default is 20.
  -format <format>                       output format: html, html-with-graphs or text. Default is html.
  -logtype <logtype>                     log type: syslog or stderr. Default is stderr.
                                          for stderr, you have to use the following log_line_prefix: \'%t [%p]: [%l-1] \'
  -report [outputfile=]<block1,block2>   list of report blocks separated by a comma
                                         report blocks can be: overall, hourly, bytype, slowest, n-mosttime,
                                          n-mostfrequent, n-slowestaverage, n-mostfrequenterrors
                                         you can add several -report options if you want to generate several reports at once
  -examples <n>                          maximum number of examples for a normalized query
  -onlyselect                            ignore all queries but SELECT
  -from "<date>"                         ignore lines logged before this date (uses strtotime)
  -to "<date>"                           ignore lines logged after this date (uses strtotime)
  -database <database>                   consider only queries on this database
  -user <user>                           consider only queries executed by this user
  -debug                                 debug mode
  -profile                               profile mode
  -help                                  this help
';
	if($error) {
		exit(1);
	} else {
		exit(0);
	}
}

function checkOutputFilePath($filePath) {
	if(!$filePath) {
		return false;
	}
	
	$tmpOutputFilePath = $filePath;
	$tmpOutputDirectory = dirname($tmpOutputFilePath);
	$tmpOutputFileName = basename($tmpOutputFilePath);

	if(file_exists($tmpOutputFilePath) && (!is_file($tmpOutputFilePath) || !is_writable($tmpOutputFilePath))) {
		usage($tmpOutputFilePath.' already exists and is not a file or is not writable');
		return false;
	} elseif(!is_dir($tmpOutputDirectory) || !is_writable($tmpOutputDirectory)) {
		usage($tmpOutputDirectory.' is not a directory, does not exist or is not writable');
		return false;
	} elseif(!$tmpOutputFileName) {
		usage('cannot find a valid basename in '.$tmpOutputFilePath);
		return false;
	} else {
		$outputFilePath = realpath($tmpOutputDirectory).'/'.$tmpOutputFileName;
		return $outputFilePath;
	}
}

$executable = array_shift($argv);

$options = array();
$argvCount = count($argv);
for($i = 0; $i < $argvCount; $i++) {
	if(strpos($argv[$i], '-') === 0) {
		if($argv[$i] == '-') {
			define('CONFIG_STDIN', true);
		} else {
			$optionKey = substr($argv[$i], 1);
			$value = false;
			if(($i+1 < $argvCount) && (strpos($argv[$i+1], '-') !== 0)) {
				$value = $argv[$i+1];
				$i++;
			}
			if($optionKey == 'report' || $optionKey == 'reports') {
				if(!isset($options['reports'])) {
					$options['reports'] = array();
				}
				$options['reports'][] = $value;
			} else {
				$options[$optionKey] = $value;
			}
		}
	} else {
		usage('invalid options format');
	}
}

if(!defined('CONFIG_STDIN')) {
	define('CONFIG_STDIN', false);
}

if(isset($options['help']) || isset($options['h']) || isset($options['-help'])) {
	usage();
}

if(isset($options['debug'])) {
	define('DEBUG', 1);
} else {
	define('DEBUG', 0);
}
if(isset($options['profile'])) {
	define('PROFILE', 1);
} else {
	define('PROFILE', 0);
}

if(!CONFIG_STDIN) {
	if(!isset($options['file'])) {
		usage('the -file option is required');
	} elseif(!$options['file']) {
		usage('you have to specify a file path');
	} elseif(!is_readable($options['file'])) {
		usage('file '.$options['file'].' cannot be read');
	} else {
		$filePath = realpath($options['file']);
	}
} else {
	$filePath = 'php://stdin';
}

if(isset($options['top'])) {
	if((int) $options['top'] > 0) {
		$top = (int) $options['top'];
	} else {
		usage('top option should be a valid integer');
	}
} else {
	$top = 20;
}
define('CONFIG_TOP_QUERIES_NUMBER', $top);

$outputToFiles = false;
$supportedReportBlocks = array(
	'overall' => 'OverallStatsReport',
	'hourly' => 'HourlyStatsReport',
	'bytype' => 'QueriesByTypeReport',
	'slowest' => 'SlowestQueriesReport',
	'n-mosttime' => 'NormalizedQueriesMostTimeReport',
	'n-mostfrequent' => 'NormalizedQueriesMostFrequentReport',
	'n-slowestaverage' => 'NormalizedQueriesSlowestAverageReport',
	'n-mostfrequenterrors' => 'NormalizedErrorsMostFrequentReport',
);
$defaultReportBlocks = array('overall', 'bytype', 'slowest', 'n-mosttime', 'n-mostfrequent', 'n-slowestaverage');

$reports = array();
if(isset($options['reports'])) {
	foreach($options['reports'] AS $report) {
		if(strpos($report, '=') !== false) {
			list($outputFilePath, $blocks) = explode('=', $report);
			$outputToFiles = true;
		} else {
			$outputFilePath = false;
			$blocks = $report;
			$outputToFiles = false;
		}
		$selectedBlocks = explode(',', $blocks);
		$notSupportedBlocks = array_diff($selectedBlocks, array_keys($supportedReportBlocks));
		
		if(empty($notSupportedBlocks)) {
			$outputFilePath = checkOutputFilePath($outputFilePath);
			$reports[] = array(
				'blocks' => $selectedBlocks,
				'file' => $outputFilePath
			);
		} else {
			usage('report types not supported: '.implode(',', $notSupportedBlocks));
		}
	}
} else {
	$reports[] = array(
		'blocks' => $defaultReportBlocks,
		'file' => false
	);
}

$supportedFormats = array('text' => 'TextReportAggregator', 'html' => 'HtmlReportAggregator', 'html-with-graphs' => 'HtmlWithGraphsReportAggregator');
if(isset($options['format'])) {
	if(array_key_exists($options['format'], $supportedFormats)) {
		if($options['format'] == 'html-with-graphs') {
			if(!function_exists('imagegd2')) {
				usage('HTML with graphs format requires GD2 library and extension');
			}
			if(!$outputToFiles) {
				usage('you need to define an output file to use HTML with graphs format (use -report outputfile=block1,block2,...)');
			}
		}
		$aggregator = $supportedFormats[$options['format']];
	} else {
		usage('format not supported');
	}
} else {
	$aggregator = $supportedFormats['html'];
}

$supportedLogTypes = array('syslog' => 'SyslogPostgreSQLParser', 'stderr' => 'StderrPostgreSQLParser');
if(isset($options['logtype'])) {
	if(array_key_exists($options['logtype'], $supportedLogTypes)) {
		$parser = $supportedLogTypes[$options['logtype']];
	} else {
		usage('log type not supported');
	}
} else {
	$parser = $supportedLogTypes['syslog'];
}

if(isset($options['examples'])) {
	$maxExamples = (int) $options['examples'];
} else {
	$maxExamples = 3;
}
define('CONFIG_MAX_NUMBER_OF_EXAMPLES', $maxExamples);

if(isset($options['onlyselect'])) {
	define('CONFIG_ONLY_SELECT', true);
} else {
	define('CONFIG_ONLY_SELECT', false);
}

if(isset($options['database']) && !empty($options['database'])) {
	define('CONFIG_DATABASE', $options['database']);
} else {
	define('CONFIG_DATABASE', false);
}

if(isset($options['user']) && !empty($options['user'])) {
	define('CONFIG_USER', $options['user']);
} else {
	define('CONFIG_USER', false);
}

if(isset($options['from']) && !empty($options['from'])) {
	$fromTimestamp = strtotime($options['to']);
	if($fromTimestamp <= 0) {
		$fromTimestamp = false;
	}
} else {
	$fromTimestamp = false;
}
define('CONFIG_FROM_TIMESTAMP', $fromTimestamp);

if(isset($options['to']) && !empty($options['to'])) {
	$toTimestamp = strtotime($options['to']);
	if($toTimestamp <= 0) {
		$toTimestamp = false;
	}
} else {
	$toTimestamp = false;
}
define('CONFIG_TO_TIMESTAMP', $toTimestamp);

if(CONFIG_FROM_TIMESTAMP || CONFIG_TO_TIMESTAMP) {
	define('CONFIG_TIMESTAMP_FILTER', true);
} else {
	define('CONFIG_TIMESTAMP_FILTER', false);
}

$logReader = new GenericLogReader($filePath, $parser, 'PostgreSQLAccumulator');

foreach($reports AS $report) {
	$reportAggregator = new $aggregator($logReader, $report['file']);
	foreach($report['blocks'] AS $block) {
		$reportAggregator->addReportBlock($supportedReportBlocks[$block]);
	}
	$logReader->addReportAggregator($reportAggregator);
	unset($reportAggregator);
}

$logReader->parse();
$logReader->output();

fclose($stderr);

?>