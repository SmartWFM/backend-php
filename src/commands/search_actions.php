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

require_once('lib/search/libsearch.php');

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

		$search = new search( $params );

		// join path
		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		// validate path
		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong path', -1);
		}

		// check some stuff
		if($fs_type == 'afs') {
			$afs = new afs($path);
			if(!$afs->allowed(AFS_READ)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_readable($path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		$tmp = $search->getResult();
		if(gettype($tmp) == 'integer') {
			switch($tmp) {
				case ERROR_PERMISSION_DENIED:
					throw new SmartWFM_Exception('Permission denied.', -8);
					break;
				case ERROR_NO_SUCH_FILE_OR_DIRECTORY:
					throw new SmartWFM_Exception('File or directory doesn\'t exists.', -2);
					break;
				default:
					throw new SmartWFM_Exception('Unknown Error.', -7);
			}
		} else {
			$response = new SmartWFM_Response();
			$response->data = $tmp;
			return $response;
		}
	}
}

SmartWFM_CommandManager::register('search', new SearchActions_Search());

?>
