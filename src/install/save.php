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

class Config {
	protected $options = array();
	
	public function addOption($name, $value) {
		$this->options[] = array(
			'type' => gettype($value),
			'name' => $name,
			'value' => $value
		);
	}
	
	public function write() {
		echo "< ?php\n";
		foreach($this->options as $i) {
			switch($i['type']){
				case 'string':
					$a = "'".$i['value']."'";
					break;
				case 'array':
					$a = 'array(';
					foreach($i['value'] as $v){
						$a .= "'".$v."', ";
					}
					$a .= ')';
					break;
				case 'boolean':
					$a = $i['value'] ? 'True' : 'False';
					break;
				default:
					$a = '';
					break;
			}
			echo "SmartWFM_Registry::set('".$i['name']."', ".$a.");\n";
		}
		echo "?>\n";
	}
};

$requiredSettings = array('basepath', 'commandspath', 'mimetype_detection_mode', 'filesystem_type');
$optionalSettings = array('commands', 'use_x_sendfile');

$settings = array_merge($requiredSettings, $optionalSettings);

header("Content-Type: text/plain");
$c = new Config();
foreach($_GET as $k => $v) {
	if(!in_array($k, $settings))
		print 'ERROR1'; //TODO
	else
		$c->addOption($k, $v);
	if(in_array($k, $requiredSettings))
		unset($requiredSettings[array_search($k, $requiredSettings)]);
}
if(count($requiredSettings) != 0)
	print 'ERROR2'; //TODO
$c->write();


?>
