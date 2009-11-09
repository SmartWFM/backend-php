<?php
###############################################################################
# This file is a part of ACPI Module Tester.                                  #
# Copyright (C) 2009 Philipp Seidel <phibo@oss.dinotools.de>                  #
#                                                                             #
# SmartWFM is free software; you can redestribute it and/or modify            #
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPL for more details.                             #
###############################################################################

define('SMARTWFM_DEBUG', false);

require_once("lib/FirePHPCore/fb.php");

if(SMARTWFM_DEBUG == true) {
	ini_set(’display_errors’,1);
	error_reporting(E_ALL|E_STRICT);

	FB::setEnabled(true);
}

require_once("lib/SmartWFM/main.php");

$smartwfm = new SmartWFM();
$smartwfm->init();
$smartwfm->process();

?>
