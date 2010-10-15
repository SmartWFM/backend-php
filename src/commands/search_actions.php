<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C)      2010 Morris Jobke <kabum@users.sourceforge.net>          #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class SearchActions_Search extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		// check params		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'options' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'name' => new SmartWFM_Param( 'string' )
					)
				)
			)
		);

		$params = $param_test->validate($params);

		// join path
		/*$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$path = Path::join(
			$root_path,
			$params['name']
		);

		// validate path
		if(Path::validate($BASE_PATH, $root_path) != true || Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong filename');
		}

		// check some stuff
		if($fs_type == 'afs') {
			$afs = new afs($root_path);
			if(!$afs->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_writable($root_path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(preg_match('!/!', $params['name'])) {
			throw new SmartWFM_Exception( 'Can\'t create folder recursively.', -3 );
		}


		if(@file_exists($path) && @is_dir($path)) {
			throw new SmartWFM_Exception('A directory with the given name already exists', -1);
		}

		*/
		
		
		
		$response = new SmartWFM_Response();
		$response->data = true;		
		return $response;
	}	
}

SmartWFM_CommandManager::register('search', new SearchActions_Search());

?>
