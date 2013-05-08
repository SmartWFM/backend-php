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

if(SmartWFM_Registry::get('filesystem_type') == 'afs') {
	require_once('lib/AFS/libafs.php');
}

class BaseActions_DirCreate extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		// check params
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		// join path
		$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$path = Path::join(
			$root_path,
			$params['name']
		);

		// validate path
		if(Path::validate($BASE_PATH, $root_path) != true || Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -4);
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
			throw new SmartWFM_Exception('A directory with the given name already exists.', -1);
		}

		$response = new SmartWFM_Response();
		if(@mkdir($path)) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception('Can\'t create the folder.', -2);
		}
		return $response;
	}
}

SmartWFM_CommandManager::register('dir.create', new BaseActions_DirCreate());

class BaseActions_DirDelete extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$path = Path::join(
			$root_path,
			$params['name']
		);

		if(Path::validate($BASE_PATH, $root_path) != true || Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -4);
		}

		if($fs_type == 'afs') {
			$afs = new afs($root_path);
			if(!$afs->allowed(AFS_DELETE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_writable($root_path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(!@file_exists($path)) {
			throw new SmartWFM_Exception('Folder doesn\'t exists.', -1);
		}

		if(!@is_dir($path)) {
			throw new SmartWFM_Exception('The folder with the given name is not a folder.', -2);
		}

		$response = new SmartWFM_Response();

		if(@rmdir($path)) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception('Can\'t remove the folder', -3);
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('dir.delete', new BaseActions_DirDelete());

/**
 * List all folders.
 */

class BaseActions_DirList extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$FOLDER_EXCLUDE_REGEX = SmartWFM_Registry::get('folder_exclude_regex','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'showHidden' => new SmartWFM_Param( 'boolean' ),
				'currentPath' => new SmartWFM_Param( 'string' )
			)
		);

		$params = $param_test->validate($params);

		$showHidden = $params['showHidden'];

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -2);
		}

		if(!@file_exists($path) || !@is_dir($path)) {
			throw new SmartWFM_Exception('Folder doesn\'t exist.', -1);
		}

		if($fs_type == 'afs') {
			$afs = new afs($path);

			if(!$afs->allowed(AFS_LIST)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		$data = array();

		if(preg_match($FOLDER_EXCLUDE_REGEX, $params['path'])) {
			if(substr($params['currentPath'], 0, strlen($params['path'])) == $params['path']) {
				$tmpPath = substr($params['currentPath'], strlen($params['path']) + 1);
				$pos = strpos($tmpPath, '/');
				if($pos !== false) {
					// if there is a '/' in the string - cut till first '/'
					// else the whole string is returned as name
					$tmpPath = substr($tmpPath, 0, $pos);
				}
				array_push($data,array(
					'name' => $tmpPath,
					'path' => $params['path'].'/'.$tmpPath,
					'hasSubDirs' => '1'
				));
			} else {
				// todo
				// what shall we do with the drunken sailor ...
				// http://youtu.be/qGyPuey-1Jw
			}
		} else {
			$d = dir($path);
			while (false !== ($name = $d->read())) {
				if($name != '.' && $name != '..') {
					$currentPath = Path::join($path, $name);
					if(@is_dir($currentPath) && (substr($name, 0, 1) != '.' || $showHidden)){
						$hasSubDirs = '0';
						if($d2 = @dir($currentPath)) {
							while (false !== ($name2 = $d2->read())) {
								if($name2 != '.' && $name2 != '..')
									if(@is_dir(Path::join($currentPath, $name2)) && (substr($name, 0, 1) != '.' || $showHidden)) {
										$hasSubDirs = '1';
										break;
									}
							}
						}
						array_push(
							$data,
							array(
								'name' => $name,
								'path' => Path::join($params['path'], $name),
								'hasSubDirs' => $hasSubDirs
							)
						);
					}
				}
			}
		}
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register('dir.list', new BaseActions_DirList());

class BaseActions_Copy extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'source' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'path' => new SmartWFM_Param('string'),
						'name' => new SmartWFM_Param('string')
					)
				),
				'destination' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'path' => new SmartWFM_Param('string'),
						'name' => new SmartWFM_Param('string')
					)
				),
				'overwrite' => new SmartWFM_Param('boolean')
			)
		);

		$params = $param_test->validate($params);

		$root_source = Path::join(
			$BASE_PATH,
			$params['source']['path']
		);

		$source = Path::join(
			$root_source,
			$params['source']['name']
		);

		$root_destination = Path::join(
			$BASE_PATH,
			$params['destination']['path']
		);

		$destination = Path::join(
			$root_destination,
			$params['destination']['name']
		);

		if(Path::validate($BASE_PATH, $root_source) != true || Path::validate($BASE_PATH, $source) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -5);
		}

		if(Path::validate($BASE_PATH, $root_destination) != true || Path::validate($BASE_PATH, $destination) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -5);
		}

		if($fs_type == 'afs') {
			$afs_source = new afs($root_source);
			$afs_destination = new afs($root_destination);
			if(!$afs_source->allowed(AFS_READ) || !$afs_destination->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
		} else if ($fs_type == 'local') {
			if(!is_readable($root_source) || !is_writable($root_destination)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
		}

		if(@is_dir($source)) {
			throw new SmartWFM_Exception( 'Source is directory.', -4 );
		}

		if(!file_exists($source)) {
			throw new SmartWFM_Exception('Source file doesn\'t exists.', -1);
		}

		$response = new SmartWFM_Response();

		if(file_exists($destination) && $params['overwrite'] == false) {
			throw new SmartWFM_Exception('Destination file exists.', -2);
		} else {
			if(copy($source, $destination) === true) {
				$response->data = true;
			} else {
				throw new SmartWFM_Exception('An error occurs.', -3);
			}
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('file.copy', new BaseActions_Copy());

class BaseActions_Delete extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$filename = Path::join(
			$path,
			$params['name']
		);

		if(Path::validate($BASE_PATH, $path) != true || Path::validate($BASE_PATH, $filename) != true) {
			throw new SmartWFM_Exception('Wrong filename.', -3);
		}

		if(!file_exists($path)) {
			throw new SmartWFM_Exception('File doesn\'t exists.', -1);
		}

		if($fs_type == 'afs') {
			$afs = new afs($path);

			if(!$afs->allowed(AFS_DELETE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_writable($path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(!file_exists($filename)) {
			throw new SmartWFM_Exception('File doesn\'t exists.', -1);
		}

		$response = new SmartWFM_Response();

		if(@unlink($filename) === true) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception('Can\'t delete the file', -2);
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('file.delete', new BaseActions_Delete());

/**
 * List all files in a given folder.
 */

class BaseActions_List extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'showHidden' => new SmartWFM_Param('boolean')
			)
		);

		$params = $param_test->validate($params);

		$showHidden = $params['showHidden'];

		$req_path = $params['path'];

		$path = Path::join(
			$BASE_PATH,
			$req_path
		);

		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong path.', -2);
		}

		if($fs_type == 'afs') {
			$afs = new afs($path);

			if( !$afs->allowed(AFS_LIST)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(!@is_dir($path)) {
			throw new SmartWFM_Exception( 'Directory doesn\'t exists', -1 );
		}

		$d = @dir($path);

		if($d) {
			$data = array();
			while (false !== ($name = $d->read())) {
				if($name != '.' && $name != '..') {
					if( substr( $name, 0, 1 ) != '.' || $showHidden ) {
						// filter out wrongly encoded files/folders
						$isValidEncoding = mb_check_encoding($name, 'UTF-8');
						// TODO log this
						$filename = Path::join($path, $name);
						if(is_file($filename)){
							$size = @filesize($filename);
							$mime_type = MimeType::get($filename);
							if($size === False) {
								$size = 0;
							}
							if($mime_type === False) {
								$mime_type = 'unknown';
							}
							$item = array(
								'name' => $isValidEncoding ? $name : 'invalid encoded file',
								'path' => $req_path,
								'size' => $size,
								'mime-type' => $mime_type,
								'isDir' => false,
								'atime' => NULL,
								'ctime' => NULL,
								'mtime' => NULL,
								'perms' => NULL,
							);
							if($time = @fileatime($filename)) {
								$item['atime'] = $time;
							}
							if($time = @filectime($filename)) {
								$item['ctime'] = $time;
							}
							if($time = @filemtime($filename)) {
								$item['mtime'] = $time;
							}
							if($perms = @fileperms($filename)) {
								$item['perms'] = sprintf('%o', $perms);
							}
							array_push(
								$data,
								$item
							);
						} elseif(is_dir($filename)) {
							$item = array(
								'name' => $isValidEncoding ? $name : 'invalid encoded folder',
								'path' => $req_path,
								'size' => 0,
								'mime-type' => '',
								'isDir' => true,
								'atime' => NULL,
								'ctime' => NULL,
								'mtime' => NULL,
								'perms' => NULL,
							);
							if($time = @fileatime($filename)) {
								$item['atime'] = $time;
							}
							if($time = @filectime($filename)) {
								$item['ctime'] = $time;
							}
							if($time = @filemtime($filename)) {
								$item['mtime'] = $time;
							}
							if($perms = @fileperms($filename)) {
								$item['perms'] = sprintf('%o', $perms);
							}
							array_push(
								$data,
								$item
							);
						}
					}
				}
			}
			$response = new SmartWFM_Response();
			$response->data = $data;
			return $response;
		}
		throw new SmartWFM_Exception( 'Can\'t open dir.', -3 );

	}
}

SmartWFM_CommandManager::register('file.list', new BaseActions_List());

class BaseActions_Move extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'source' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'name' => new SmartWFM_Param(
							$type = 'string'
						),
						'path' => new SmartWFM_Param(
							$type = 'string'
						)
					)
				),
				'destination' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'name' => new SmartWFM_Param(
							$type = 'string'
						),
						'path' => new SmartWFM_Param(
							$type = 'string'
						)
					)
				),
				'overwrite' => new SmartWFM_Param(
					$type = 'boolean'
				)
			)
		);

		$params = $param_test->validate($params);

		$root_source = Path::join(
			$BASE_PATH,
			$params['source']['path']
		);

		$source = Path::join(
			$root_source,
			$params['source']['name']
		);

		$root_destination = Path::join(
			$BASE_PATH,
			$params['destination']['path']
		);

		$destination = Path::join(
			$root_destination,
			$params['destination']['name']
		);

		if(Path::validate($BASE_PATH, $root_source) != true || Path::validate($BASE_PATH, $source) != true) {
			throw new SmartWFM_Exception('Wrong filename.', -4);
		}

		if(Path::validate($BASE_PATH, $root_destination) != true || Path::validate($BASE_PATH, $destination) != true) {
			throw new SmartWFM_Exception('Wrong filename.', -4);
		}

		if(!file_exists($source)) {
			throw new SMartWFM_Exception('The source file doesn\'t exists.', -1);
		}

		if($fs_type == 'afs') {
			$afs_source = new afs($root_source);
			$afs_destination = new afs($root_destination);

			if(!$afs_source->allowed(AFS_READ) || !$afs_source->allowed(AFS_DELETE)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
			if(!$afs_destination->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
		} else if($fs_type == 'local') {
			if(!is_readable($root_source) || !is_writeable($root_source)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
			if(!is_writable($root_destination)) {
				throw new SmartWFM_Exception( 'Permission denied.', -9 );
			}
		}

		$response = new SmartWFM_Response();

		if(file_exists($destination) && $params['overwrite'] == false) {
			throw new SmartWFM_Exception('A file with the destination name exists and the overwrite flag is not set.', -2);
		} else {
			if(@rename($source, $destination)) {
				$response->data = true;
			} else {
				throw new SmartWFM_Exception('An error occurs.', -3);
			}
		}

		return $response;

	}
}

SmartWFM_CommandManager::register('file.move', new BaseActions_Move());

class BaseActions_Rename extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string'),
				'name_new' => new SmartWFM_Param('string'),
				'overwrite' => new SmartWFM_Param('boolean')
			)
		);

		$params = $param_test->validate($params);

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$filename = Path::join(
			$path,
			$params['name']
		);

		$filename_new = Path::join(
			$path,
			$params['name_new']
		);

		if(Path::validate($BASE_PATH, $path) != true || Path::validate($BASE_PATH, $filename) != true) {
			throw new SmartWFM_Exception('Wrong filename for source.', -2);
		}

		if(Path::validate($BASE_PATH, $filename_new) != true) {
			throw new SmartWFM_Exception('Wrong filename for destination.', -3);
		}

		if($fs_type == 'afs') {
			$afs = new afs( $path );

			if(!$afs->allowed(AFS_DELETE) || !$afs->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}

		} else if($fs_type == 'local') {
			if(!is_writable($path)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		if(!file_exists($filename)) {
			throw new SmartWFM_Exception('Source file doesn\'t exists.', -1);
		}

		if(file_exists($filename_new) && !$params['overwrite']) {
			throw new SmartWFM_Exception('Destination file exists and I am not allowed to overwrite.', -2);
		}

		$response = new SmartWFM_Response();

		if(@rename($filename, $filename_new)) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception('Error while renaming the file.', -4);
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('file.rename', new BaseActions_Rename());

class BaseActions_DirListFake extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'name' => new SmartWFM_Param( 'string' )
			)
		);

		$params = $param_test->validate($params);

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong directory name.', -2);
		}

		if(!@file_exists($path) || !@is_dir($path)) {
			throw new SmartWFM_Exception('Folder doesn\'t exist.', -1);
		}

		if($fs_type == 'afs') {
			$afs = new afs($path);

			if(!$afs->allowed(AFS_LIST)) {
				throw new SmartWFM_Exception('Permission denied.', -9);
			}
		}

		$data = array();
		array_push(
			$data,
			array(
				'name' => $params['name'],
				'path' => Path::join($params['path'], $params['name']),
				'hasSubDirs' => '1'
			)
		);
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register('dir.list.fake', new BaseActions_DirListFake());

?>
