<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2009-2010 Philipp Seidel <phibo@oss.dinotools.de>             #
#               2010-2013 Morris Jobke <kabum@users.sourceforge.net>          #
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

/**
 * provides:
 *  - up and download of files
 */

class BaseDirectCommand_Download extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		if(Path::validate($BASE_PATH, $path) != true ) {
			print "error";
			return;
		}

		$files = array();

		foreach ($params['files'] as $file) {
			$filePath = Path::join($path, $file);
			if(Path::validate($BASE_PATH, $filePath) != true){
				print "error";
				return;
			}
			if($fs_type == 'local') {
				if(!is_readable($filePath)) {
					print 'Permission denied.';
					return;
				}
			}
			$files[] = $filePath;
		}

		if($fs_type == 'afs') {
			$afs = new afs($path);

			if( !$afs->allowed( AFS_READ ) ) {
				print 'Permission denied.';
				return;
			}
		}

		if(sizeof($files) == 0) {
			print 'Error - no files specified.';
			return;
		}

		$mime = NULL;

		if(sizeof($files) == 1) {
			$mime = @MimeType::get($files[0]);
			$filename = basename($files[0]);
			$file = $files[0];
			$deleteFileAfterSend = false;
		}

		if(sizeof($files) > 1 || $mime == 'directory') {
			$archiveName = SmartWFM_Registry::get('temp_folder').'/SmartWFM.'.basename($path).'.'.sha1($path).'.zip';

			$a = new Archive($archiveName, $path);
			foreach($files as $file) {
				$a->addFolderOrFile($file);
			}
			$a->close();

			// file restrictions
			chmod($archiveName, 0600);

			$mime = @MimeType::get($archiveName);
			$filename = basename($path).'.zip';
			$file = $archiveName;
			$deleteFileAfterSend = true;
		}

		$fp = fopen($file, 'r');
		if($fp === False) {
			print('Error reading the file');
			return;
		}
		@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Download - file: ' . $file . 'content: ' . join(';', $files));

		header('Content-Type: ' . $mime);
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		if(SmartWFM_Registry::get('use_x_sendfile') === True && !$deleteFileAfterSend) {
			header('X-Sendfile: ' . $file);
		} else {
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();

			while(($content = fread($fp, 4096)) != '') {
				print($content);
			}
			fclose($fp);
			if($deleteFileAfterSend) {
				unlink($file);
			}
		}
		exit();
	}
}

SmartWFM_DirectCommandManager::register('download', new BaseDirectCommand_Download());

class BaseDirectCommand_Upload extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$response = array(
			'success' => true,
			'message' => ''
		);

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		$file = Path::join(
			$path,
			$_FILES['file']['name']
		);

		if(Path::validate($BASE_PATH, $path) != true || Path::validate($BASE_PATH, $file) != true) {
			$response['success'] = false;
			$response['message'] = 'error'; //TODO
		}

		if($response['success'] && $fs_type == 'afs') {
			$afs = new afs($path);

			if(!$afs->allowed(AFS_INSERT)) {
				$response['success'] = false;
				$response['message'] = 'Write permission denied.';
			}
		} else if($response['success'] && $fs_type == 'local') {
			if(!is_writable($path)) {
				$response['success'] = false;
				$response['message'] = 'Write permission denied.';
			}
		}

		//TODO: check if file exists

		if($response['success']) {
			move_uploaded_file($_FILES['file']['tmp_name'], $file);
		}
		@syslog(LOG_INFO, '[' . $_SERVER['REMOTE_USER'] . '] Upload - file: ' . $file);

		echo json_encode($response);
		exit();
	}
}

SmartWFM_DirectCommandManager::register('upload', new BaseDirectCommand_Upload());

?>
