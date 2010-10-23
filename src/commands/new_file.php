<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2010 Philipp Seidel <phibo@oss.dinotools.de>                  #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class NewFile_List extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		// check params		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'lang' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		$response = new SmartWFM_Response();
		$ini = parse_ini_file('config/new_file.cfg', True);
		$response->data = array();
		foreach($ini as $key => $value) {
			$tmp = array();
			$tmp['id'] = $key;
			if (array_key_exists($params['lang'] . '.title', $value)) {
				$tmp['title'] = $value[$params['lang'] . '.title'];
			} elseif (array_key_exists('title', $value)) {
				$tmp['title'] = $value['title'];
			} else {
				throw new SmartWFM_Exception('Error', -1);
			}
			array_push($response->data, $tmp);
		}
		return $response;
	}	
}

SmartWFM_CommandManager::register('new_file.list', new NewFile_List());

?>

