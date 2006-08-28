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

$postgreSQLRegexps = array();

// PostgreSQLParser
$postgreSQLRegexps['LogLine'] = new RegExp("/^(.*?)(LOG|DEBUG|CONTEXT|WARNING|ERROR|FATAL|PANIC|HINT|DETAIL|NOTICE|STATEMENT|INFO):[\s]+/");
$postgreSQLRegexps['LogLinePrefix'] = new RegExp('/([a-z]*)=([^ ,]*)/');
$postgreSQLRegexps['ContinuationLine'] = new RegExp("/^(\^I|\s)/");
$postgreSQLRegexps['QueryStartPart'] = new RegExp("/^(query|statement):[\s]+/");
$postgreSQLRegexps['StatusPart'] = new RegExp("/^(connection|received|unexpected EOF)/");
$postgreSQLRegexps['DurationPart'] = new RegExp("/^duration:([\s\d\.]+)(sec|ms|us)/");
$postgreSQLRegexps['PreparedStatementPart'] = new RegExp("/^(prepare|bind|execute) ([^:]*):[\s]+/");

// PostgreSQLStatusLine
$postgreSQLRegexps['ConnectionReceived'] = new RegExp('/connection received: host=([^\s]+) port=([\d]+)/');
$postgreSQLRegexps['ConnectionAuthorized'] = new RegExp('/connection authorized: user=([^\s]+) database=([^\s]+)/');

// PostgreSQLQueryStartWithDurationLine
$postgreSQLRegexps['QueryOrStatementPart'] = new RegExp('/[\s]*(query|statement):[\s]*/i');

// PostgreSQLContextLine
$postgreSQLRegexps['ContextSqlStatement'] = new RegExp('/^SQL statement "/');
$postgreSQLRegexps['ContextSqlFunction'] = new RegExp('/([^\s]+)[\s]+function[\s]+"([^"]+)"(.*)$/');

// PreparedStatement
$postgreSQLRegexps['BindDetail'] = new RegExp('/(\$[0-9]+) = "(.*)"(?=(?:, \$[0-9]+ = |\z))/U');

$GLOBALS['postgreSQLRegexps'] =& $postgreSQLRegexps;

?>