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
require_once('lib/FileInfo/libfileinfo.php');

/**
 * Get file info
 */

class FileInfoActions_FileInfo extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		$fsType = SmartWFM_Registry::get('filesystem_type');

		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$path = Path::join(
			$BASE_PATH,
			$params
		);

		if(!file_exists($path)) {
			throw new SmartWFM_Exception('File doesn\'t exists.', -1 );
		}

		if(is_dir($path)) {
			throw new SmartWFM_Exception('Given path isn\'t a file.', -2 );
		}

		if($fsType == 'afs') {
			$afs = new afs($path);
			if(!$afs->allowed(AFS_READ)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fsType == 'local') {
			if(!is_readable($path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		$file = array();

		// getting infos:
		$dateTimeFormat = 'Y-m-d H:i:s';
		// file name
		array_push($file, array('filename', basename($path)));
		array_push($file, array('full path', $path));

		// file size
		$size = filesize($path);
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		for($i = 0; $size >= 1024 && $i < 4; $i++) {
			$size /= 1024;
		}
		$size = round($size, 2).' '.$units[$i];
		array_push($file, array('file size', $size));

		// permissions
		$perms = fileperms($path);
		//array_push($file, array('file permissions', $perms));
		array_push($file, array('file permissions', FileInfo::getPermissionString($perms)));

		// user info
		$owner_id = fileowner($path);
		$owner_array = posix_getpwuid($owner_id);
		array_push($file, array('owner id', $owner_id));
		array_push($file, array('owner name', $owner_array['name']));

		// group info
		$group_id = filegroup($path);
		$group_array = posix_getgrgid($group_id);
		array_push($file, array('group id', $group_id));
		array_push($file, array('group name', $group_array['name']));

		// time stamps
		array_push($file, array('access time', date($dateTimeFormat, fileatime($path))));
		array_push($file, array('change time', date($dateTimeFormat, filectime($path))));
		array_push($file, array('modification time', date($dateTimeFormat, filemtime($path))));

		$response = new SmartWFM_Response();
		$response->data = $file;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'file.info', new FileInfoActions_FileInfo() );