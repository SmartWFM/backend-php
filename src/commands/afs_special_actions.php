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
 * Get quota
 */

class AFSSpecialActions_GetQuota extends SmartWFM_Command {
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
		
		//TODO rights for quota???
		if( !$afs->allowed( AFS_LIST ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}
		
		$response = new SmartWFM_Response();
		$response->data = $afs->getquota();
		return $response;
	}
}

SmartWFM_CommandManager::register( 'quota.get', new AFSSpecialActions_GetQuota() );

/**
 * Get array of rights sorted by user/group
 */

class AFSSpecialActions_GetACL extends SmartWFM_Command {
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
		
		if( !$afs->allowed( AFS_ADMINISTER ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}
		
		$res = $afs->getAcl();
		switch( $res ) {
			case -1:
				throw new SmartWFM_Exception( 'Incorrect rights.', -3 );
			case -2:
				throw new SmartWFM_Exception( 'Incorrect user or group name.', -4 );
			case -3:
				throw new SmartWFM_Exception( 'Can\'t create new group.', -5 );
			case -4:
				throw new SmartWFM_Exception( 'Rights couldn\'t be set.', -6 );
		}	
		$response = new SmartWFM_Response();
		$response->data = $res;	
		return $response;
	}
}

SmartWFM_CommandManager::register( 'acl.get', new AFSSpecialActions_GetACL() );

/**
 * Set acl sorted by user/group
 */

class AFSSpecialActions_SetACL extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );
		
		$param_test = new SmartWFM_Param( 'string' );		
		
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'path' => new SmartWFM_Param(
					$type = 'string'
				),
				'acl' => new SmartWFM_Param(
					$type = 'key_data_object',
					$items = array(
						'key' => new SmartWFM_Param(
							$type = 'string'
						),
						'value' => new SmartWFM_Param(
							$type = 'string'
						)
					)
				)
			)
		);

		$params = $param_test->validate( $params) ;
		
		$path = Path::join(
			$BASE_PATH,
			$params['path']
		); 
		
		if( !is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Dir doesn\'t exist.', -1 );
		}
		
		$afs = new afs( $path );	
		
		if( !$afs->allowed( AFS_ADMINISTER ) ) { 
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}		
		
		$response = new SmartWFM_Response();
		$response->data = $afs->setAcl( $params['acl'] );
		return $response;
	}
}

SmartWFM_CommandManager::register( 'acl.set', new AFSSpecialActions_SetACL() );

?>
