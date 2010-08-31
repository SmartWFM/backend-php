<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2010 Morris Jobke <kabum@users.sourceforge.net>               #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

$requiredSettings = array('basepath', 'commandspath', 'mimetype_detection_mode', 'filesystem_type', 'commands');
$optionalSettings = array();

$settings = array_merge($requiredSettings, $optionalSettings);

echo '<pre>';
print_r($_GET);
foreach($_GET as $k => $v) {
	if(!in_array($k, $settings))
		print 'ERROR'; //TODO
	if(in_array($k, $requiredSettings))
		unset($requiredSettings[array_search($k, $requiredSettings)]);
}
if(count($requiredSettings) != 0)
	print 'ERROR'; //TODO

echo '</pre>';

?>
