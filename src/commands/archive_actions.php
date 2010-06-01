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

require_once('lib/archives/archives.php');

/*
	create an archive
 */

class BaseArchiveActions_Create extends SmartWFM_Command {
	function process($params) {
		$fsType = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		// check params		
		$paramTest = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'archiveName' => new SmartWFM_Param('string'),
				'archiveType' => new SmartWFM_Param('string'),
				'fullPath' => new SmartWFM_Param('boolean'),
				'files' => new SmartWFM_Param(
					$type = 'array',
					$items = new SmartWFM_Param('string')
				)
			)
		);

		$params = $paramTest->validate($params);
		
		$rootPath = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$path = Path::join(
			$rootPath,
			$params['archiveName']
		);
		
		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name', -1);
		}
		
		if($fsType == 'afs') {
			$afs = new afs($rootPath);
			if(!$afs->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fsType == 'local') {
			if(!is_writable($rootPath)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(@file_exists($path)) {
			throw new SmartWFM_Exception('A file with the given name already exists', -2);
		}
		
		$files = array();
		
		foreach($params['files'] as $p) {
			$tmpPath = Path::join(
				$rootPath,
				$p
			);
			if(Path::validate($BASE_PATH, $tmpPath) != true) {
				throw new SmartWFM_Exception('Wrong directory name', -3);
			}
			
			if(!@file_exists($tmpPath)) {
				throw new SmartWFM_Exception('A file with the given name doesn\'t exists', -4);
			}
					
			if(@is_dir($tmpPath)) {
				foreach(Archives::getFiles($tmpPath) as $e) {
					$files[] = $e;
				}
			} else {			
				$files[] = $tmpPath;
			}
		}
		
		switch($params['archiveType']) {
			case 'zip':
				$a = new ZipArchive;
				if( $a->open($path, ZipArchive::CREATE) ) {
					foreach($files as $f) {
						if($params['fullPath']){
							if( !$a->addFile($f) ) {
								throw new SmartWFM_Exception('Couldn\'t add file to archive', -5);							
							}
						} else {
							if( !$a->addFile($f, str_replace($rootPath.'/','',$f)) ) {
								throw new SmartWFM_Exception('Couldn\'t add file to archive', -5);							
							}
						}
					}
					if(!$a->close()) {
						throw new SmartWFM_Exception('Couldn\'t create archive', -6);
					}
					$response = new SmartWFM_Response();
					$response->data = true;	
					return $response;
				} else {
					throw new SmartWFM_Exception('Couldn\'t create archive', -7);				
				}	
				break;
			default:
				throw new SmartWFM_Exception('Wrong archive type', -8);
		}
	}
}
SmartWFM_CommandManager::register('archive.create', new BaseArchiveActions_Create());

class BaseArchiveActions_List extends SmartWFM_Command {
	function process($params) {
		$fsType = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$paramTest = new SmartWFM_Param('string');

		$params = $paramTest->validate($params);
		
		$path = Path::join(
			$BASE_PATH,
			$params
		);
		
		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name', -1);
		}
		
		if(! @file_exists($path)) {
			throw new SmartWFM_Exception('A file with the given name doesn\'t exists', -2);
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
		
		switch(MIMETYPE::get($path)) {
			case 'application/zip':
				$a = new ZipArchive;
				if( $a->open($path) ) {
					$files = array();
					for($i = 0; $i < $a->numFiles; $i++) {
						$files[] = $a->getNameIndex($i);
					}
				} else {
					throw new SmartWFM_Exception('Couldn\'t open archive', -6);				
				}		
				$response = new SmartWFM_Response();
				$response->data = Archives::fileNamesToTreeStruct($files);	
				return $response;
				break;
			default:
				throw new SmartWFM_Exception('Unreadable archive type', -8);
		}
	}
}
SmartWFM_CommandManager::register('archive.list', new BaseArchiveActions_List());

class BaseArchiveActions_Extract extends SmartWFM_Command {
	function process($params) {
		$fsType = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		// check params		
		$paramTest = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'archive' => new SmartWFM_Param('string'),
				'files' => new SmartWFM_Param(
					$type = 'array',
					$items = new SmartWFM_Param('string')
				)
			)
		);

		$params = $paramTest->validate($params);
		
		$extractPath = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		if(Path::validate($BASE_PATH, $extractPath) != true) {
			throw new SmartWFM_Exception('Wrong directory name', -1);
		}
		
		$archivePath = Path::join(
			$BASE_PATH,
			$params['archive']
		);
		
		if(Path::validate($BASE_PATH, $archivePath) != true) {
			throw new SmartWFM_Exception('Wrong archive path', -1);
		}
		
		if($fsType == 'afs') {
			$afsExtract = new afs($extractPath);
			if(!$afsExtract->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
			$afsArchive = new afs(substr($archivePath, 0, strrpos($archivePath, '/')));
			if(!$afsExtract->allowed(AFS_READ)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}			
		} else if ($fsType == 'local') {
			if(!is_writable($extractPath)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
			if(!is_readable($archivePath)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}
		
		foreach($params['files'] as $k => $f){
			$tmpPath = Path::join(
				$extractPath,
				$f
			);			
			if(@file_exists($tmpPath)) {
				throw new SmartWFM_Exception('A file with the given name already exists', -4);
			}	
			$params['files'][$k] = ltrim($f, './');	
		}
		
		switch(MIMETYPE::get($archivePath)) {
			case 'application/zip':
				$a = new ZipArchive;
				if( $a->open($archivePath) ) {
					if( $params['files'] ){
						if( $a->extractTo($extractPath, $params['files']) ) {
							$response = new SmartWFM_Response();
							$response->data = True;	
							return $response;
						} else {
							/*foreach($params['files'] as $k => $f){
								$params['files'][$k] = ltrim($f, './');	
							}*/
							//for($i = 0; $i < $a->numFiles; $i++) {
								//echo $a->getNameIndex($i);
							//}
							//echo $a->extractTo($extractPath, array('/afs'));
							//print_r($params['files']);
							throw new SmartWFM_Exception('Couldn\'t extract archive', -7);	
						}
					} else {
						if( $a->extractTo($extractPath) ) {
							$response = new SmartWFM_Response();
							$response->data = True;	
							return $response;
						} else {
							throw new SmartWFM_Exception('Couldn\'t exctract archive', -7);	
						}				
					}
				} else {
					throw new SmartWFM_Exception('Couldn\'t open archive', -6);				
				}		
				$response = new SmartWFM_Response();
				$response->data = True;	
				return $response;
				break;
			default:
				throw new SmartWFM_Exception('Unreadable archive type', -8);
		}
	}
}
SmartWFM_CommandManager::register('archive.extract', new BaseArchiveActions_Extract());
