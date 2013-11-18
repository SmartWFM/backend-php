<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2010 Philipp Seidel <phibo@oss.dinotools.de>                  #
#               2013 Morris Jobke <morris.jobke@gmail.com>                    #
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
 * provides:
 *  - list available templates
 *  - create file from template
 *  - save file
 */

class NewFile_List extends SmartWFM_Command {
	function process($params) {
		// check params
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'lang' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		$ini = parse_ini_file('config/new_file.cfg', True);
		$data = array();
		foreach($ini as $key => $value) {

			if (array_key_exists($params['lang'] . '.title', $value)) {
				$title = $value[$params['lang'] . '.title'];
			} elseif (array_key_exists('title', $value)) {
				$title = $value['title'];
			} else {
				throw new SmartWFM_Exception('Error', -1);
			}
			array_push(
				$data,
				array(
					'id' 	=> $key,
					'title' => $title
				)
			);
		}
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register('new_file.list', new NewFile_List());

class NewFile_Create extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$base_path = SmartWFM_Registry::get('basepath','/');

		// check params
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'id' => new SmartWFM_Param('string'),
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		// join path
		$root_path = Path::join(
			$base_path,
			$params['path']
		);

		$ini = parse_ini_file('config/new_file.cfg', True);
		if(!array_key_exists($params['id'], $ini)) {
			throw new SmartWFM_Exception('Id not found', -1);
		}

		$filename = Path::join(
			$root_path,
			$params['name'] . '.' . $ini[$params['id']]['extension']
		);

		// validate path
		if(Path::validate($base_path, $root_path) != true ||
			Path::validate($base_path, $filename) != true ||
			strpos($params['name'], '/') !== False ) {
			throw new SmartWFM_Exception('Wrong filename');
		}

		// check some stuff
		if($fs_type == 'afs') {
			$afs = new afs($root_path);
			if(!$afs->allowed(AFS_CREATE)) {
				throw new SmartWFM_Exception('Permission denied', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_writable($root_path)) {
				throw new SmartWFM_Exception('Permission denied', -9);
			}
		}

		if(file_exists($filename)) {
			throw new SmartWFM_Exception('File already exists', -2);
		}

		$tpl_filename = 'config/new_file/' . $ini[$params['id']]['filename'];
		if(!is_file($tpl_filename)) {
			throw new SmartWFM_Exception('Template file doesn\'t exists', -3);
		}

		if(@copy($tpl_filename, $filename) === False) {
			throw new SmartWFM_Exception('Error');
		}
		@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Create file - file: ' . $filename);

		$response = new SmartWFM_Response();
		$response->data = array(
			'name' => $filename,
			'mime-type' => MimeType::get($filename)
		);
		return $response;
	}
}

SmartWFM_CommandManager::register('new_file.create', new NewFile_Create());


class NewFile_Save extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$base_path = SmartWFM_Registry::get('basepath','/');

		// check params
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param('string'),
				'name' => new SmartWFM_Param('string'),
				'content' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate($params);

		// join path
		$root_path = Path::join(
			$base_path,
			$params['path']
		);

		$filename = Path::join(
			$root_path,
			$params['name']
		);

		// validate path
		if(Path::validate($base_path, $root_path) != true || Path::validate($base_path, $filename) != true) {
			throw new SmartWFM_Exception('Wrong filename');
		}

		// check some stuff
		if($fs_type == 'afs') {
			$afs = new afs($root_path);
			if(!$afs->allowed(AFS_INSERT)) {
				throw new SmartWFM_Exception('Permission denied', -9);
			}
		} else if ($fs_type == 'local') {
			if(!is_writable($filename)) {
				throw new SmartWFM_Exception('Permission denied', -9);
			}
		}
		// ToDo: check if file exists

		if(!$handle = fopen($filename, 'w')) {
			throw new SmartWFM_Exception('Couldn\'t open the file', -1);
		}

		if(fwrite($handle, $params['content']) === false) {
			throw new SmartWFM_Exception('Couldn\'t write to file', -2);
		}

		fclose($handle);
		@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Save file - file: ' . $filename);

		$response = new SmartWFM_Response();
		$response->data = True;
		return $response;
	}
}

SmartWFM_CommandManager::register('new_file.save', new NewFile_Save());

?>

