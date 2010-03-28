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

require_once('lib/AFS/libafs.php');
if( file_exists('lib/AFS/mimetype.php') ) {
	require_once('lib/AFS/mimetype.php');
}

/**
  * uploads a file
  */
class AFSBaseDirectCommand_Upload extends SmartWFM_Command {
	function process($params) {
		$BASE_PATH = SmartWFM_Registry::get('basepath','/');
		
		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$afs = new afs( $path );	
		
		if( !$afs->allowed( AFS_INSERT ) ) { 
			print 'Permission denied.';
			return;
		}
		
		$file = Path::join(
			$path,
			$_FILES['file']['name']
		);

		move_uploaded_file($_FILES['file']['tmp_name'], $file);

	}	
}

SmartWFM_DirectCommandManager::register('upload', new AFSBaseDirectCommand_Upload());


?>
