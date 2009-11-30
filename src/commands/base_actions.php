<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2009 Philipp Seidel <phibo@oss.dinotools.de>                  #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class BaseActions_Delete extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
				
		if(!array_key_exists('path', $params)) {
			throw new SmartWFM_Excaption('"path"-param is required');
		}
		
		if(!array_key_exists('name', $params)) {
			throw new SmartWFM_Excaption('"name"-param is required');
		}
		
		$filename = $params['path'] . '/' . $params['name'];
		
		$file = $BASE_PATH.$filename;
		
		$response = new SmartWFM_Response();
		
		if(@unlink($file) == true) {
			$response->data = true;
		} else {
			$response->error_code = -1;
			$response->error_message = "Can't delete the file";
		
		}
		
		return $response;
	}	
}

SmartWFM_CommandManager::register('file.delete', new BaseActions_Delete());

class BaseActions_Rename extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		
		if(!array_key_exists('path', $params)) {
			throw new SmartWFM_Excaption('"path"-param is required');
		}
		
		if(!array_key_exists('name', $params)) {
			throw new SmartWFM_Excaption('"name"-param is required');
		}
		if(!array_key_exists('name_new', $params)) {
			throw new SmartWFM_Excaption('"name_new"-param is required');
		}
		
		$src = $BASE_PATH.$params['path'].'/'.$params['name'];
		$dst = $BASE_PATH.$params['path'].'/'.$params['name_new'];
		
		$response = new SmartWFM_Response();
		
		if(@rename($src, $dst)) {
			$response->data = true;
		} else {
			$response->error_code = -1;
			$response->error_message = "Can't rename the file";
		}

		return $response;
	}	
}

SmartWFM_CommandManager::register('file.rename', new BaseActions_Rename());

class BaseActions_Move extends SmartWFM_Command {
	function process($params) {
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

		$source = $params['source'];
		$destination = $params['destination'];
		$overwrite = $params['overwrite'];

		//ToDo: check for path an name in source and destination

		$src = $BASE_PATH.$source['path'] . '/'. $source['name'];
		$dst = $BASE_PATH.$destination['path'] . '/'. $destination['name'];

		$response = new SmartWFM_Response();
		
		if(file_exists($dst) && $overwrite == false) {
			$response->error_data = array(
				'source' => $source,
				'destination' => $destination,
			);
			$response->error_code = -1;
			return $response;
		} else {
			if(@rename($src, $dst)) {
				$response->data = true;
			} else {
				$response->error_data = array(
					'source' => $source,
					'destination' => $destination,
				);
				$response->error_code = -2;
			}
			return $response;
		}
		$response->error_code = -1;
		
		return $response;
	}	
}

SmartWFM_CommandManager::register('file.move', new BaseActions_Move());

class BaseActions_Copy extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		$source = $params['source'];
		$destination = $params['destination'];

		$src = $BASE_PATH.$source['path'] . '/'. $source['name'];
		$dst = $BASE_PATH.$destination['path'] . '/'. $destination['name'];
		$overwrite = $params['overwrite'];

		$response = new SmartWFM_Response();
		
		if(file_exists($dst) && $overwrite == false) {
			$response->error_data = array(
				'source' => $source,
				'destination' => $destination,
			);
			$response->error_code = -1;
		} else {
			if(@copy($src, $dst)) {
				$response->data = true;
			} else {
				$response->error_code = -2;
				$response->error_message = 'Test';
				$response->error_data = array(
					'source' => $source,
					'destination' => $destination,
				);

			}
		}
		// for debuging	
		sleep(1);
		return $response;
	}	
}

SmartWFM_CommandManager::register('file.copy', new BaseActions_Copy());

class BaseActions_DirDelete extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		//$dir = $params['dir'];
		$path = $params;
		
		$dir = $BASE_PATH.$path;

		$response = new SmartWFM_Response();
		
		try {
			@rmdir($dir);
		} catch(Exception $e) {
		}
		
		$response->data = true;
		return $response;
	}	
}

SmartWFM_CommandManager::register('dir.delete', new BaseActions_DirDelete());

class BaseActions_DirCreate extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		//$dir = $params->dir;
		$path = $params['path'];
		$name = $params['name'];

		$dir = $BASE_PATH.$path.'/'.$name;

		$response = new SmartWFM_Response();
		
		if(file_exists($dir) && is_dir($dir)) {
			$response->error_data = array(
				'path' => $path,
				'name' => $name,
			);
			$response->error_code = -1;
			$response->error_message = 'Dir exists';
		} else {
			if(@mkdir($dir)) {
				$response->data = true;
			} else {
				$response->error_data = array(
					'path' => $path,
					'name' => $name,
				);
				$response->error_code = -2;
				$response->error_message = 'Wrong permissions';
			}
		}
		return $response;
	}	
}

SmartWFM_CommandManager::register('dir.create', new BaseActions_DirCreate());

/**
 * List all files in a given folder.
 */

class BaseActions_List extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		
		$param_test = new SmartWFM_Param(
			$type = 'string'
		);

		$params = $param_test->validate($params);
		
		$req_path = $params;

		$path = Path::join($BASE_PATH,$req_path);
		if(Path::validate($BASE_PATH, $path) != true) {
			throw new SmartWFM_Exception('Wrong path');
		}
		if(!is_dir($path)) {
			$response = new SmartWFM_Response();
			$response->error_code = -1;
			$response->error_message = 'Dir doesn\'t exist';
			throw new SmartWFM_Exception(
				NULL,
				-1,
				$response
			);

		}
		$d = dir($path);

		$data = array();
		while (false !== ($name = $d->read())) {
			if($name != '.' && $name != '..') {
				$filename = Path::join($path,$name);
				if(is_file($filename)){
					$size = @filesize($filename);
					$mime_type = @mime_content_type($filename);
					if($size === False) {
						$size = 0;
					}
					if($mime_type === False) {
						$mime_type = 'unknown';
					}
					array_push(
						$data,
						array(
							'type' => 'file',
							'name' => $name,
							'path' => $req_path,
							'size' => $size,
							'mime-type' => $mime_type,
							'isDir' => false,
						)
					);
				} else {
					array_push(
						$data,
						array(
							'type' => 'file',
							'name' => $name,
							'path' => $req_path,
							'size' => 0,
							'mime-type' => '',
							'isDir' => true,
						)
					);
				}
			}
		}
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register('file.list', new BaseActions_List());

/**
 * List all folders.
 */

class BaseActions_DirList extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		$path = $params;

		$data = array();
		$d = dir($BASE_PATH.$path);
		while (false !== ($name = $d->read())) {
			if($name != '.' && $name != '..') {
				if(is_dir($BASE_PATH.$path.'/'.$name)){
					$hasSubDirs = '0';
					$d2 = dir($BASE_PATH.$path.'/'.$name);
					while (false !== ($name2 = $d2->read())) {
						if($name2 != '.' && $name2 != '..')
							if(is_dir($BASE_PATH.$path.'/'.$name.'/'.$name2))
								$hasSubDirs = '1';
					}
					$path = str_replace($BASE_PATH, '', $path);
					array_push($data, array(
								'name' => $name,
								'path' => $path,
								'hasSubDirs' => $hasSubDirs
							)
					);
				}
			}
		}
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register('dir.list', new BaseActions_DirList());

?>
