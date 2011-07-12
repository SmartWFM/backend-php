<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2011 Morris Jobke <kabum@users.sourceforge.net>               #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

if(SmartWFM_Registry::get('filesystem_type') == 'afs') {
	require_once('lib/AFS/libafs.php');
}

/**
 * Get file info
 */

class FileInfoActions_FileInfo extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );

		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$path = Path::join(
			$BASE_PATH,
			$params
		);

		$response = new SmartWFM_Response();
		$response->data = '';
		return $response;
	}
}

SmartWFM_CommandManager::register( 'file.info', new FileInfoActions_FileInfo() );