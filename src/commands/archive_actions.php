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

require_once('lib/archives/archive.php');
require_once('lib/archives/archiveHelpers.php');

/**
 * provides:
 *  - create, list, extract archives
 */

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

		switch($params['archiveType']) {
			case 'zip':
				if(strlen($path) >= 4 && substr($path, -4) != '.zip') {
					$path .= '.zip';
				}
				break;
			case 'tarbz2':
				if(strlen($path) >= 8 && substr($path, -8) != '.tar.bz2') {
					$path .= '.tar.bz2';
				}
				break;
			case 'targz':
				if(strlen($path) >= 7 && substr($path, -7) != '.tar.gz') {
					$path .= '.tar.gz';
				}
				break;
			default:
				break;
		}

		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -1);
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
			throw new SmartWFM_Exception('A file with the given name already exists.', -2);
		}

		switch($params['archiveType']) {
			case 'zip':
				$a = new Archive($path, $rootPath);
				$a->setUseAbsolutePaths($params['fullPath']);
				foreach($params['files'] as $p) {
					$a->addFolderOrFile(Path::join(
						$rootPath,
						$p
					));
				}
				$a->close();
				$response = new SmartWFM_Response();
				$response->data = true;
				@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Archive - create ZIP - file: ' . $path);
				return $response;
				break;
			case 'tarbz2':
				$AT = 'j';
			case 'targz':
				if(!isset($AT))
					$AT = 'z';
				$cmd = '';
				if(!$params['fullPath']){
					$cmd .= 'cd '.escapeshellarg($rootPath).' && ';
					$path = substr($path, strlen($rootPath)+1);
				}
				$cmd .= 'tar -c'.$AT.'f '.escapeshellarg($path);

				foreach($params['files'] as $p) {
					$tmpPath = Path::join(
						$rootPath,
						$p
					);
					if(Path::validate($BASE_PATH, $tmpPath) != true) {
						throw new SmartWFM_Exception('Wrong directory name.', -3);
					}

					if(!@file_exists($tmpPath)) {
						throw new SmartWFM_Exception('A file with the given name doesn\'t exists.', -4);
					}

					if(!$params['fullPath']){
						$tmpPath = $p;
					}
					$cmd .= ' '.escapeshellarg($tmpPath);
				}

				exec( $cmd, $output, $ret );
				@syslog($ret ? LOG_ERR : LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Archive - create - cmd: ' . $cmd);
				if( !$ret ) {
					$response = new SmartWFM_Response();
					$response->data = true;
					return $response;
				}else{
					throw new SmartWFM_Exception('Couldn\'t create archive.', -10);
				}
				break;
			default:
				throw new SmartWFM_Exception('Wrong archive type.', -8);
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
			throw new SmartWFM_Exception('Wrong directory name.', -1);
		}

		if(! @file_exists($path)) {
			throw new SmartWFM_Exception('A file with the given name doesn\'t exists.', -2);
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

		/* archive mimetypes:
			application/x-zip		zip
			application/x-gzip		tar.gz, tgz
			application/x-bzip2		tar.bz2
		*/
		$tarOption = NULL;
		switch(MIMETYPE::get($path)) {
			case 'application/zip':
			case 'application/x-zip':
				$a = new ZipArchive;
				if( $a->open($path) ) {
					$files = array();
					for($i = 0; $i < $a->numFiles; $i++) {
						$files[] = $a->getNameIndex($i);
					}
				} else {
					throw new SmartWFM_Exception('Couldn\'t open archive.', -3);
				}
				$response = new SmartWFM_Response();
				$response->data = ArchiveHelpers::fileNamesToTreeStruct($files);
				return $response;
				break;
			case 'application/x-gzip':
			case 'application/x-gtar':
				$tarOption = 'z';
			case 'application/x-bzip2':
				if($tarOption == NULL)
					$tarOption = 'j';
				$cmd = 'tar -t'.$tarOption.'f '.escapeshellarg($path);
				exec( $cmd, $output, $ret );
				if(!$ret){
					$response = new SmartWFM_Response();
					$response->data = ArchiveHelpers::fileNamesToTreeStruct($output);
					return $response;
				}else{
					throw new SmartWFM_Exception('Couldn\'t open archive.', -3);
				}
				break;
			default:
				throw new SmartWFM_Exception('Unreadable archive type.', -8);
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
			throw new SmartWFM_Exception('Wrong directory name.', -1);
		}

		$archivePath = Path::join(
			$BASE_PATH,
			$params['archive']
		);

		if(Path::validate($BASE_PATH, $archivePath) != true) {
			throw new SmartWFM_Exception('Wrong archive path.', -2);
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
				throw new SmartWFM_Exception('A file with the given name already exists.', -3);
			}
			$params['files'][$k] = ltrim($f, './');
		}

		/* archive mimetypes:
			application/x-zip		zip
			application/x-gzip		tar.gz, tgz
			application/x-bzip2		tar.bz2
		*/
		$tarOption = NULL;
		switch(MIMETYPE::get($archivePath)) {
			case 'application/zip':
			case 'application/x-zip':
				$a = new ZipArchive;
				if( $a->open($archivePath) ) {
					if( $params['files'] ){
						$files = array();
						for($i = 0; $i < $a->numFiles; $i++) {
							$tmp = $a->getNameIndex($i);
							foreach($params['files'] as $k => $f){
								if( strstr($tmp, $f) ){
									$files[] = $tmp;
									unset($params['files'][$k]);
								}
							}
						}
						if( !$a->extractTo($extractPath, $files) ) {
							throw new SmartWFM_Exception('Couldn\'t extract archive.', -5);
						}
					} else {
						if( !$a->extractTo($extractPath) ) {
							throw new SmartWFM_Exception('Couldn\'t extract archive.', -6);
						}
					}
				} else {
					throw new SmartWFM_Exception('Couldn\'t open archive.', -7);
				}
				@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Archive - extract ZIP - file: ' . $archivePath);
				$response = new SmartWFM_Response();
				$response->data = True;
				return $response;
				break;
			case 'application/x-gzip':
			case 'application/x-gtar':
				$tarOption = 'z';
			case 'application/x-bzip2':
				if($tarOption == NULL)
					$tarOption = 'j';
				$cmd = 'tar -x'.$tarOption.'f '.escapeshellarg($archivePath).
				' -C '.escapeshellarg($extractPath);
				foreach($params['files'] as $f){
					$cmd .= ' '.escapeshellarg($f);
				}
				exec( $cmd, $output, $ret );
				@syslog($ret ? LOG_ERR : LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Archive - extract - cmd: ' . $cmd);
				if(!$ret){
					$response = new SmartWFM_Response();
					$response->data = true;
					return $response;
				}else{
					throw new SmartWFM_Exception('Couldn\'t open and extract archive.', -4);
				}
			default:
				throw new SmartWFM_Exception('Unreadable archive type.', -8);
		}
	}
}
SmartWFM_CommandManager::register('archive.extract', new BaseArchiveActions_Extract());
