<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2009-2010 Philipp Seidel <phibo@oss.dinotools.de>             #
#                    2010 Morris Jobke <kabum@users.sourceforge.net>          #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class SettingActions_Load extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$filename = SmartWFM_Registry::get('setting_filename', NULL);

		$response = new SmartWFM_Response();
		$response->data = array();

		if($filename != NULL && @file_exists($filename) && !@is_dir($filename)) {
			$data = @parse_ini_file($filename);
			if($data !== False) {
				foreach($params as $key => $value) {
					if(array_key_exists($key, $data)) {
						if($value == 'bool') {
							$response->data[$key] = (boolean) $data[$key];
							continue;
						}
						if($value == 'select') {
							$response->data[$key] = (integer) $data[$key];
							continue;
						}
						if($value == 'string-select') {
							$response->data[$key] = (string) $data[$key];
							continue;
						}
					}
				}
			}

		}
		return $response;
	}
}

SmartWFM_CommandManager::register('setting.load', new SettingActions_Load());

class SettingActions_Store extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$filename = SmartWFM_Registry::get('setting_filename', NULL);

		$fp = fopen($filename, 'w');

		$response = new SmartWFM_Response();

		if($fp === False) {
			throw new SmartWFM_Exception('Can\'t save settings.', -1);
		} else {
			fwrite($fp, "[SmartWFM]\r\n");

			foreach($params as $key => $value) {
				if(is_bool($value)) {
					if($value == True) {
						$value = 'true';
					} else {
						$value = 'false';
					}
				}
				fwrite($fp, $key . '=' . $value . "\r\n");
			}

			$response->data = true;
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('setting.save', new SettingActions_Store());

