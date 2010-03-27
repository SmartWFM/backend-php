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

require_once('lib/AFS/libafs.php');
if( file_exists('lib/AFS/mimetype.php') ) {
	require_once('lib/AFS/mimetype.php');
}

/**
 * List all folders.
 */

class AFSBaseActions_DirList extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param( 'string' );
				
		/*		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'showHidden' => new SmartWFM_Param( 'boolean' )
			)
		);		
		*/

		$params = $param_test->validate( $params);
		
		$showHidden = false;
		//$showHidden = $params['showHidden'];
		
		$path = Path::join(
			$BASE_PATH,
			$params
			//$params['path']
		); 
		
		if( !@is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Dir doesn\'t exist.', -1 );
		}
		
		$afs = new afs( $path );	
		
		if( !$afs->allowed( AFS_LIST ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}
		
		$data = array();
		$d = dir( $path );
		while ( false !== ( $name = $d->read()  )) {
			if( $name != '.' && $name != '..' ) {				
				if( @is_dir( Path::join( $path, $name ) ) && ( substr( $name, 0, 1 ) != '.' || $showHidden ) ){
					$hasSubDirs = '0';
					$d2 = dir( Path::join( $path, $name ) );
					while ( false !== ( $name2 = $d2->read() ) ) {
						if( $name2 != '.' && $name2 != '..' )
							if( @is_dir( Path::join( $path, $name, $name2 ) ) && ( substr( $name, 0, 1 ) != '.' || $showHidden ) )
								$hasSubDirs = '1';
					}
					array_push( 
						$data, 
						array(
							'name' => $name,
							'path' => $params,
							//'path' => $params['path'],
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

SmartWFM_CommandManager::register( 'dir.list', new AFSBaseActions_DirList() );

/**
 * List all files in a given folder.
 */

class AFSBaseActions_List extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param(
			$type = 'string'
		);
				
		/*		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'showHidden' => new SmartWFM_Param( 'boolean' )
			)
		);		
		*/

		$params = $param_test->validate( $params );
		
		$showHidden = false;
		//$showHidden = $params['showHidden'];
		
		$req_path = $params;
		//$req_path = $params['path'];
		
		$path = Path::join(
			$BASE_PATH,
			$req_path
		);		

		if( !@is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Dir doesn\'t exist.', -1 );
		}
				
		$afs = new afs( $path );	
		
		if( !$afs->allowed( AFS_LIST ) ) {
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}
		
		$d = dir( $path );
		$data = array();
		while ( false !== ( $name = $d->read() ) ) {
			if( $name != '.' && $name != '..' ) {
				if( substr( $name, 0, 1 ) != '.' || $showHidden ) {
					$filename = Path::join( $path, $name );
					if( @is_file( $filename ) ){
						$size = @filesize( $filename );
						$mime_type = @mime_content_type( $filename );		
						if( $size === False ) {
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
		}
		$response = new SmartWFM_Response();
		$response->data = $data;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'file.list', new AFSBaseActions_List() );

/**
 * Rename a file/directory
 */
class AFSBaseActions_Rename extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'name' => new SmartWFM_Param( 'string' ),
				'name_new' => new SmartWFM_Param( 'string' ),
				'overwrite' => new SmartWFM_Param( 'boolean' )
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

		if( !file_exists($filename) ) {
			throw new SmartWFM_Exception( 'Source file doesn\t exist.', -1 );
		}
		
		$afs = new afs( $path );
				
		if( !$afs->allowed( AFS_DELETE ) || !$afs->allowed( AFS_CREATE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}	
		
		if( file_exists($filename_new) && !$params['overwrite'] ) {
			throw new SmartWFM_Exception( 'Destination file exists.', -2);
		}
		
		$response = new SmartWFM_Response();
		
		if( @rename( $filename, $filename_new ) ) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception( 'Error while renaming the file', -3 );
		}

		return $response;
	}	
}

SmartWFM_CommandManager::register( 'file.rename', new AFSBaseActions_Rename() );
?>
