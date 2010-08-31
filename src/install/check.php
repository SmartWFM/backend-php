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

ini_set('display_errors',1);
error_reporting(E_ALL|E_STRICT);

$CONFIG_PATH = '../config/';
$CONFIG_FILE = 'local.php';



$check = intval($_GET['check']);

$result = array();
$result['error'] = false;
switch($check){
	case 1: # check directory and files
		$result['result']['config'] = array(
			'base_path' => '/var/www',
		
		);
		$result['result']['writable'] = false;
		$result['result']['overwrite'] = false;
		if(file_exists($CONFIG_PATH)) {
			if(is_writable($CONFIG_PATH))
				$result['result']['writable'] = true;
		}
		if(file_exists($CONFIG_PATH.$CONFIG_FILE)) {
			$result['result']['overwrite'] = true;
			//$result['result']['config'] = NULL; //TODO read config
		}		
		break;
	case 2:
		$path = @$_GET['path'];
		if($path == '')
			$result['error'] = true;
		else {
			if(!file_exists($path))
				$result['result']['correct'] = false;
			else
				$result['result']['correct'] = true;			
		}
		break;
	default:
		$result['error'] = true;
		break;

}


header("Content-Type: application/json");
print json_encode($result);
?>
