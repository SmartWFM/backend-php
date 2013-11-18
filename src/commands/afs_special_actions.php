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
 * provides:
 *  - read quota
 *  - read/write AFS ACLs
 *  - CRUD for AFS groups
 *  - CRUD for AFS group memberships
 */

/**
 * Get quota
 */

class AFSSpecialActions_GetQuota extends SmartWFM_Command {
	function process( $params ) {
		$BASE_PATH = SmartWFM_Registry::get( 'basepath', '/' );

		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$path = Path::join(
			$BASE_PATH,
			$params
		);

		if( !is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Directory doesn\'t exists.', -1 );
		}

		$afs = new afs( $path );

		if( !$afs->allowed( AFS_LIST ) ) {
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}

		$response = new SmartWFM_Response();
		$response->data = $afs->getQuota();
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

		$params = $param_test->validate( $params ) ;

		$path = Path::join(
			$BASE_PATH,
			$params
		);

		if( !is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Directory doesn\'t exists.', -1 );
		}

		$afs = new afs( dirname($path) ); // check parent dir for access permission

		if( !$afs->allowed( AFS_ADMINISTER ) ) {
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}

		$afs = new afs( $path );

		$response = new SmartWFM_Response();
		$response->data = $afs->getAcl();
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
							$type = 'stringorinteger'
						),
						'value' => new SmartWFM_Param(
							$type = 'string'
						)
					)
				),
				'subdirs' => new SmartWFM_Param(
					$type = 'boolean'
				),
			)
		);

		$params = $param_test->validate( $params ) ;

		$path = Path::join(
			$BASE_PATH,
			$params['path']
		);

		if( !is_dir( $path ) ) {
			throw new SmartWFM_Exception( 'Directory doesn\'t exists.', -1 );
		}

		$afs = new afs( dirname($path) ); // check parent dir for access permission

		if( !$afs->allowed( AFS_ADMINISTER ) ) {
			throw new SmartWFM_Exception( 'Permission denied.', -2 );
		}

		$afs = new afs( $path );

		$res = $afs->setAcl( $params['acl'], $params['subdirs']  );
		if( $res !== true ){
			switch( $res ) {
				case -1:
					throw new SmartWFM_Exception( 'Incorrect rights.', -3 );
				case -2:
					throw new SmartWFM_Exception( 'Incorrect user or group name.', -4 );
				case -3:
					throw new SmartWFM_Exception( 'New group couldn\'t be created.', -5 );
				case -4:
					throw new SmartWFM_Exception( 'Rights couldn\'t be set.', -6 );
			}
		}
		$response = new SmartWFM_Response();
		$response->data = $res;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'acl.set', new AFSSpecialActions_SetACL() );

/**
 * Get array of groups that current user owns
 */

class AFSSpecialActions_GetGroups extends SmartWFM_Command {
	function process( $params ) {
		$afs = new afs( NULL );

		$response = new SmartWFM_Response();
		$response->data = $afs->getUsersGroups();
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.get', new AFSSpecialActions_GetGroups() );

/**
 * Create new group
 */

class AFSSpecialActions_CreateGroup extends SmartWFM_Command {
	function process( $params ) {
		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$afs = new afs( NULL );

		$res = $afs->addGroup( $params );
		if( $res !== true ) {
			switch( $res ) {
				case -1:
					throw new SmartWFM_Exception( 'New group couldn\'t be created, because you aren\'t own it.', -1 );
				case -2:
					throw new SmartWFM_Exception( 'Group already exists.', -2 );
				case false:
					throw new SmartWFM_Exception( 'New group couldn\'t be created.', -3 );
			}
		}

		$response = new SmartWFM_Response();
		$response->data = $res;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.create', new AFSSpecialActions_CreateGroup() );

/**
 * Delete group
 */

class AFSSpecialActions_DeleteGroup extends SmartWFM_Command {
	function process( $params ) {
		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$afs = new afs( NULL );

		$res = $afs->deleteGroup( $params );
		switch( $res ) {
			case -1:
				throw new SmartWFM_Exception( 'Group couldn\'t be deleted, because you aren\'t own it.', -1 );
			case -2:
				throw new SmartWFM_Exception( 'Group doesn\'t exist.', -2 );
			case false:
				throw new SmartWFM_Exception( 'Group couldn\'t be deleted.', -3 );
		}

		$response = new SmartWFM_Response();
		$response->data = $res;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.delete', new AFSSpecialActions_DeleteGroup() );

/**
 * Get array of members of group
 */

class AFSSpecialActions_GetGroupMembers extends SmartWFM_Command {
	function process( $params ) {
		$param_test = new SmartWFM_Param( 'string' );

		$params = $param_test->validate( $params ) ;

		$afs = new afs( NULL );

		$res = $afs->getGroupMembers( $params );
		if( $res === false ) {
			throw new SmartWFM_Exception( 'Members couldn\'t be determined.', -1 );
		}

		$response = new SmartWFM_Response();
		$response->data = $res;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.members.get', new AFSSpecialActions_GetGroupMembers() );

/**
 * Add user(s) to group
 */

class AFSSpecialActions_AddGroupMembers extends SmartWFM_Command {
	function process( $params ) {
		$param_test = new SmartWFM_Param(
			$type = 'object',
			$items = array(
				'group' => new SmartWFM_Param('string'),
				'user' => new SmartWFM_Param('string')
			)
		);

		$params = $param_test->validate( $params ) ;

		$afs = new afs( NULL );

		if( !$afs->groupExists( $params['group'] ) ) {
			throw new SmartWFM_Exception( 'Group doesn\'t exists.', -1 );
		}

		if( !$afs->ownGroup( $params['group'] ) ) {
			throw new SmartWFM_Exception( 'You aren\'t own this group.', -2 );
		}

		$res = $afs->addGroupMembers( $params['group'], $params['user'] );

		if( $res === false ) {
			throw new SmartWFM_Exception( 'User couldn\'t be added.', -3 );
		}

		$response = new SmartWFM_Response();
		$response->data = $res;
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.members.add', new AFSSpecialActions_AddGroupMembers() );



/**
 * Delete groups or members
 */

class AFSSpecialActions_DeleteGroupsMembers extends SmartWFM_Command {
	function process( $params ) {
		FB::log($params);
		$param_test = new SmartWFM_Param(
			$type = 'array',
			$items = new SmartWFM_Param( 'string' )
		);

		$params = $param_test->validate( $params ) ;

		$afs = new afs( NULL );

		$res = array();
		$fail = false;
		foreach( $params as $value ) {
			if( strpos( $value, '/' ) === false ) {
				$res[$value] = $afs->deleteGroup( $value );
			} else {
				$tmp = explode( '/', $value );
				$res[$value] = $afs->removeGroupMembers( $tmp[0], $tmp[1] );
			}
			if( !$fail && !$res[$value] ) {
				$fail = true;
			}
		}

		$response = new SmartWFM_Response();
		$response->data = array( 'fail' => $fail, 'result' => $res);
		return $response;
	}
}

SmartWFM_CommandManager::register( 'groups.members.delete', new AFSSpecialActions_DeleteGroupsMembers() );

?>
