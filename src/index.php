<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2009-2010 Philipp Seidel <phibo@oss.dinotools.de>             #
#                    2010 Morris Jobke   <kabum@users.sourceforge.net>        #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

/**
 * source: http://trac.phtagr.org/attachment/ticket/83/patch-broken-escapeshellarg.diff
 *
 * In some php versions escapeshellarg() removes special characters like german
 * umlaut. This bug causes wrong system calls to files like import of media
 * with umlaut. The call setlocale() to an UTF8 character set fixes this
 * problem. Check your character sets of our environment with "local -a" and
 * enter it below.
 *
 * See also http://bugs.php.net/bug.php?id=44945
 *          http://bugs.php.net/bug.php?id=44564
 */
setlocale(LC_CTYPE, 'UTF8', 'de_DE.UTF-8');

# initialize loggin
openlog('SWFM', LOG_PID, LOG_LOCAL0);

define('SMARTWFM_DEBUG', false);

require_once("lib/FirePHPCore/fb.php");

if(SMARTWFM_DEBUG == true) {
	date_default_timezone_set("Europe/Berlin");
	ini_set('display_errors',1);
	error_reporting(E_ALL|E_STRICT);

	FB::setEnabled(true);
}

require_once("lib/SmartWFM/main.php");
require_once("lib/SmartWFM/tools.php");
require_once("lib/SmartWFM/validate.php");

$smartwfm = new SmartWFM();
$smartwfm->init();
$smartwfm->process();

?>
