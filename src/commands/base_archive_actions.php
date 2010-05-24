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

if(SmartWFM_Registry::get('filesystem_type') == 'afs') {
	require_once('lib/AFS/libafs.php');
}

/*
	create an archive
 */

class BaseArchiveActions_Create extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		// check params		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'archiveName' => new SmartWFM_Param( 'string' ),
				'archiveType' => new SmartWFM_Param( 'string' ),
				'files' => new SmartWFM_Param(
					$type = 'array',
					$items = new SmartWFM_Param( 'string' )
				)
			)
		);

		$params = $param_test->validate($params);
		
		$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$path = Path::join(
			$root_path,
			$params['archiveName']
		);
		
		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name', -1);
		}
		
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
		
		if(@file_exists($path)) {
			throw new SmartWFM_Exception('A file with the given name already exists', -2);
		}
		
		$files = array();
		
		foreach($params['files'] as $p) {
			$tmpPath = Path::join(
				$root_path,
				$p
			);
			if(Path::validate($BASE_PATH, $tmpPath) != true) {
				throw new SmartWFM_Exception('Wrong directory name', -3);
			}
			
			if(!@file_exists($tmpPath)) {
				throw new SmartWFM_Exception('A file with the given name doesn\'t exists', -4);
			}
			$files[] = $tmpPath;
		}
		
		switch($params['archiveType']) {
			case 'zip':
				$a = new ZipArchive;
				$r = $a->open($path, ZipArchive::CREATE);
				if( $r === true ) {
					foreach($files as $f) {
						$r = $a->addFile($f);
						if( !$r ) {
							throw new SmartWFM_Exception('Couldn\'t add file to archive', -5);							
						}
					}
					$a->close();
				} else {
					throw new SmartWFM_Exception('Couldn\'t create archive', -6);				
				}				
				$response = new SmartWFM_Response();
				$response->data = true;	
				return $response;
				break;
			default:
				throw new SmartWFM_Exception('Wrong archive type', -8);
		}
	}
}
SmartWFM_CommandManager::register('archive.create', new BaseArchiveActions_Create());
