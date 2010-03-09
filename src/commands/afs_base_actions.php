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

/**
 * List all folders.
 */

class AFSBaseActions_DirList extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params) ;
		
		$path = Path::join(
			$BASE_PATH,
			$params
		); 
		
		if( !is_dir( $path ) ) {
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
				if( is_dir( Path::join( $path, $name ) ) ){
					$hasSubDirs = '0';
					$d2 = dir( Path::join( $path, $name ) );
					while ( false !== ( $name2 = $d2->read() ) ) {
						if( $name2 != '.' && $name2 != '..' )
							if(is_dir(Path::join( $path, $name, $name2 ) ) )
								$hasSubDirs = '1';
					}
					array_push( 
						$data, 
						array(
							'name' => $name,
							'path' => $params,
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

		$params = $param_test->validate( $params );
		
		$path = Path::join(
			$BASE_PATH,
			$params
		);		

		if( !is_dir( $path ) ) {
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
				$filename = Path::join( $path, $name );
				if( is_file( $filename ) ){
					$size = @filesize( $filename );
					//$mime_type = mime_content_type( $filename );		//TODO not supported
					$mime_type = 'unknown';
					if( $size === False ) {
						$size = 0;
					}
					array_push(
						$data,
						array(
							'type' => 'file',
							'name' => $name,
							'path' => $params,
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
							'path' => $params,
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

SmartWFM_CommandManager::register( 'file.list', new AFSBaseActions_List() );

/**
 * Create a directory
 */
class AFSBaseActions_DirCreate extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'name' => new SmartWFM_Param( 'string' )
			)
		);

		$params = $param_test->validate( $params );
		
		$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$afs = new afs( $root_path );	
		
		if( !$afs->allowed( AFS_CREATE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}
		
		if( preg_match( '!/!', $params['name'] ) ) {
			throw new SmartWFM_Exception( 'Can\'t create folder recursively.', -3 );
		}
				
		$path = Path::join(
			$root_path,
			$params['name']
		);

		if( file_exists( $path ) && is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'A directory with the given name already exists', -1 );
		}

		$response = new SmartWFM_Response();
		if( @mkdir( $path ) ) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception( 'Can\'t create the folder', -2 );
		}
		return $response;
	}	
}

SmartWFM_CommandManager::register( 'dir.create', new AFSBaseActions_DirCreate() );

/**
 * Delete a directory
 */
class AFSBaseActions_DirDelete extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath' , '/' );
		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'name' => new SmartWFM_Param( 'string' )
			)
		);

		$params = $param_test->validate( $params );
		
		$root_path = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$afs = new afs( $root_path );	
		
		if( !$afs->allowed( AFS_DELETE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}

		$path = Path::join(
			$root_path,
			$params['name']
		);

		$response = new SmartWFM_Response();
		
		if( !file_exists( $path ) ) {
			throw new SmartWFM_Exception( 'Folder doesn\'t exist.', -1 );
		}
		
		if( !is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'The folder with the given name is not a folder', -2 );
		}
		
		if( @rmdir( $path ) ) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception( 'Can\'t remove the folder', -3 );
		}
		
		return $response;
	}	
}

SmartWFM_CommandManager::register( 'dir.delete', new AFSBaseActions_DirDelete() );

/**
 * Copy a file
 */
class AFSBaseActions_Copy extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'source' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'path' => new SmartWFM_Param( 'string' ),
						'name' => new SmartWFM_Param( 'string' )
					)
				),
				'destination' => new SmartWFM_Param(
					$type = 'object',
					$items = array(
						'path' => new SmartWFM_Param( 'string' ),
						'name' => new SmartWFM_Param( 'string' )
					)
				),
				'overwrite' => new SmartWFM_Param( 'boolean' )
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
		
		$response = new SmartWFM_Response();
		
		if( !file_exists( $source ) ) {
			throw new SmartWFM_Exception( 'Source file doesn\'t exist', -1 );
		}
		
		$afs_source = new afs( $root_source );	
		$afs_destination = new afs( $root_destination );	
		
		if( !$afs_source->allowed( AFS_READ ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}
		
		if( !$afs_destination->allowed( AFS_CREATE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}
		
		if( is_dir( $source ) ) { 
			throw new SmartWFM_Exception( 'Source is directory.', -4 );
		}
		
		if( file_exists( $destination ) && $params['overwrite'] == false ) {
			throw new SmartWFM_Exception( 'Destination file exists', -2 );
		} else {
			if( copy( $source, $destination ) === true ) {
				$response->data = true;
			} else {
				throw new SmartWFM_Exception( 'An error occurs', -3 );
			} 
		}
		
		return $response;
	}	
}

SmartWFM_CommandManager::register( 'file.copy', new AFSBaseActions_Copy() );

/**
 * Delete a file
 */
class AFSBaseActions_Delete extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param( 'string' ),
				'name' => new SmartWFM_Param( 'string' )
			)
		);

		$params = $param_test->validate( $params );
		
		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);
		
		$afs = new afs( $path );	
		
		if( !$afs->allowed( AFS_DELETE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}
		
		$filename = Path::join(
			$path,
			$params['name']
		);

		if( !file_exists( $filename ) ) {
			throw new SmartWFM_Exception( 'File doesn\'t exist', -1	);
		}

		$response = new SmartWFM_Response();
		
		if( @unlink( $filename ) === true ) {
			$response->data = true;
		} else {
			throw new SmartWFM_Exception( 'Can\'t delete the file', -3 );
		}
		
		return $response;
	}	
}

SmartWFM_CommandManager::register( 'file.delete', new AFSBaseActions_Delete() );

/**
 * Move a file
 */
class AFSBaseActions_Move extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
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
		$params = $param_test->validate( $params );
		
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

		if( !file_exists( $source ) ) {
			throw new SMartWFM_Exception( 'The source file doesn\'t exist', -1 );
		}
		
		$afs_source = new afs( $root_source );	
		$afs_destination = new afs( $root_destination );	

		if( !$afs_source->allowed( AFS_READ ) || !$afs_source->allowed( AFS_DELETE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}
		
		if( !$afs_destination->allowed( AFS_CREATE ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -9 );
		}
		
		$response = new SmartWFM_Response();

		if( file_exists( $destination ) && $params['overwrite'] == false) {
			throw new SmartWFM_Exception( 'Destination file exists', -2);
		} else {
			if( @rename( $source, $destination ) ) {
				$response->data = true;
			} else {
				throw new SmartWFM_Exception( 'An error occurs', -3 );
			}
		}
		
		return $response;

	}	
}

SmartWFM_CommandManager::register( 'file.move', new AFSBaseActions_Move() );

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
