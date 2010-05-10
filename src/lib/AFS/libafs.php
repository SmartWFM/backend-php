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

if( !defined( 'AFS_LIST' ) ) 
	define( 'AFS_LIST', 1 );
if( !defined( 'AFS_CREATE' ) ) 
	define( 'AFS_CREATE', 2 );
if( !defined( 'AFS_DELETE' ) ) 
	define( 'AFS_DELETE', 3 );
if( !defined( 'AFS_READ' ) ) 
	define( 'AFS_READ', 4 );
if( !defined( 'AFS_ADMINISTER' ) ) 
	define( 'AFS_ADMINISTER', 5 );

class afs {
	protected $cmd = array(
		'fs' => 'fs',			//'/usr/bin/fs',
		'fsr' => 'fsr',			//'/usr/bin/fsr', //recursively
		'pts' => 'pts',			//'/usr/bin/pts',
		'errtostd' => '2>&1',
		'todevnull' => '>/dev/null',
	);

	protected $dir;
	protected $username;
	
	protected $rights = array();
	protected $userrights = array(
		'r' => false,			// read
		'l' => false,			// lookup
		'i' => false,			// insert
		'd' => false,			// delete
		'w' => false,			// write
		'k' => false,			// lock
		'a' => false,			// administer
	);
	protected $groupmember = array(
		'system:anyuser',
		'system:authuser',
	);
	protected $quota;
	
	/**
	  * constructor
	  * @param dir
	  */
	public function afs( $dir ) {		
		$this->dir = $dir;
		$this->username = $_SERVER["REMOTE_USER"];			// TODO ev. escapen
	}
	
	/**
	  * reads all rights the user has
	  */
	protected function listAcl() {
		$cmd = $this->cmd['fs'] . ' listacl ' . escapeshellarg( $this->dir );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			for( $i = 2; $i < sizeof($output); $i++ ) {
				$tmp = explode( ' ', trim($output[$i]));
				$this->rights[$tmp[0]] = $tmp[1];
				if( $tmp[0] == $this->username || in_array( $tmp[0], $this->groupmember ) ) {
					for( $j = 0; $j < strlen($tmp[1]); $j++ ) {
						$this->userrights[$tmp[1][$j]] = true;
					}
				}
			}
		}
	}
	
	/**
	  * retrieves groupmemberships of user
	  */
	protected function groupMemberships() {
		$cmd = $this->cmd['pts'] . ' membership ' . escapeshellarg( $this->username );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			for( $i = 1; $i < sizeof($output); $i++ ) {
				$this->groupmember[] = trim( $output[$i] );
			}
		}
	}	
	
	/**
	  * check whether kind of command is allowed
	  * @params cmd constant of kind of command
	  * @return boolean
	  */
	public function allowed( $cmd ) {
		$this->groupMemberships();
		$this->listAcl();
		switch( $cmd ) {
			case AFS_LIST:
				return $this->userrights['l'];
			case AFS_CREATE:
				return $this->userrights['i'];
			case AFS_DELETE:
				return $this->userrights['d'];
			case AFS_READ:
				return $this->userrights['r'];
			case AFS_ADMINISTER:
				return $this->userrights['a'];
			default:
				return false;
		}
	}	
	
	/**
	  * read quota
	  */
	protected function listQuota() {
		$cmd = $this->cmd['fs'] . ' listquota ' . escapeshellarg( $this->dir );
		exec( $cmd, $output, $ret );
		if( $ret || !isset( $output[1] ) ) {
		    $this->quota = false;
		} else {
			$tmp = explode( ' ', preg_replace('/[ ]{2,}/', ' ', $output[1] ) );
			$this->quota = array(
				'total' => $tmp[1],
				'used' => $tmp[2],
				'percent_used' => $tmp[3],
				'percent_partition' => $tmp[4]
			);
		}
	}	
	
	/**
	  * @return array 
	  *		total 				- total amount in KB
	  *		used				- used space in KB
	  *		percent_used		- percentage of used space
	  *		percent_partition 	- percentage of used partition space
	  */
	public function getQuota() {
		$this->listQuota();
		return $this->quota;
	}
	
	/**
	  * @return array:
	  *		key 				- user/group name
	  *		value				- rights [rlidwka]
	  */
	public function getAcl() {
		if( empty( $this->rights ) ) {
			$this->listAcl();
		}
		return $this->rights;
	}
	
	/**
	  * set acl
	  * @params acl - array:
	  *		key 				- user/group name
	  *		value				- rights [rlidwka]
	  * @params recursively - boolean	- set acl recursively
	  */
	public function setAcl( $acl, $recursively = false ) {
		foreach( $acl as $user => $rights ) {
			if( $rights == '' ) {
				$rights = $acl[$user] = 'none';
			}
			if( !$this->isAclString( $rights ) ) {
				return -1;
			}	
			if( !$this->isUserString( $user ) ) {
				return -2;
			}
			if( strpos( $user, ':' ) !== false ) {
				if( !$this->groupExists( $user ) && $rights != 'none' ) {					
					if( $this->createGroup( $user ) != true ) {
						return -3;					
					}
				}
			}
		}		
		$fs = $recursively ? $this->cmd['fsr'] : $this->cmd['fs'];
		$cmd = $fs . ' setacl -dir ' . escapeshellarg( $this->dir );
		foreach( $acl as $user => $rights ) {
			$cmd .= ' -acl ' . $user . ' ' . $rights;
		}
		$cmd .= ' -clear';
		exec( $cmd, $output, $ret );
		if( !$ret || ( $fs == $this->cmd['fsr'] && $ret = 127 ) ) {
			return true; 
		}
		return -4;
	}
	
	/**
	  * check string whether it is a correct acl argument
	  * @params rights string to be checked
	  * @return boolean
	  */
	protected function isAclString( $rights ) {
		if( $rights == 'read' ||
			$rights == 'write' ||
			$rights == 'all' ||
			$rights == 'none' ) {
			return true;
		}
		$tmp = preg_match('![^rlidwka]+!', $rights);
		return empty( $tmp );
	}
	
	/**
	  * check string whether it is a correct username
	  * @params username
	  * @return boolean
	  */
	protected function isUserString( $username ) {
		$tmp = preg_match('![^a-zA-Z0-9-:]+!', $username);
		return empty( $tmp );
	}
	
	/**
	  * check whether group exists
	  * @params groupname
	  * @return boolean
	  */
	public function groupExists( $groupname ) {
		$cmd = $this->cmd['pts'] . ' examine ' . escapeshellarg( $groupname );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			return true; 
		}
		return false;
	}
	
	/**
	  * create group if allowed
	  * @params groupname
	  * @return boolean or error code
	  */
	protected function createGroup( $groupname ) {
		if( !$this->ownGroup( $groupname ) ) {
			return -1;
		}
		$cmd = $this->cmd['pts'] . ' creategroup ' . escapeshellarg( $groupname );
		exec( $cmd, $output, $ret );
		if( !$ret ) {			
			return true; 
		}
		return false;
	}
	
	/**
	  * retrieves groups the user owns
	  * @return array groupnames
	  */
	public function getUsersGroups() {
		$cmd = $this->cmd['pts'] . ' listowned ' . escapeshellarg( $this->username );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			$groups = array();
			unset( $output[0] );
			foreach( $output as $group ) {
				$groups[] = trim( $group );
			}
			return $groups; 
		}
		return false;
	}	
	
	/**
	  * create group
	  * @params groupname
	  * @return boolean or error code
	  */
	public function addGroup( $groupname ) {
		if( strpos( $groupname, ':' ) === false ) {
			$groupname = $this->username . ':' . $groupname;
		}
		if( !$this->groupExists( $groupname ) ) {
			return $this->createGroup( $groupname );
		} else {
			return -2;
		}
	}
	
	/**
	  * delete group
	  * @params groupname
	  * @return boolean or error code
	  */
	public function deleteGroup( $groupname ) {
		if( $this->groupExists( $groupname ) ) {
			if( !$this->ownGroup( $groupname ) ) {
				return -1;
			}
			$cmd = $this->cmd['pts'] . ' delete ' . escapeshellarg( $groupname );
			exec( $cmd, $output, $ret );
			if( !$ret ) {			
				return true; 
			}
			return false;
		} else {
			return -2;
		}
	}
	
	/**
	  * retrieves members of group
	  * @params groupname
	  * @return array groupnames
	  */
	public function getGroupMembers( $groupname ) {
		$cmd = $this->cmd['pts'] . ' membership ' . escapeshellarg( $groupname );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			$members = array();
			unset( $output[0] );
			foreach( $output as $group ) {
				$members[] = trim( $group );
			}
			return $members; 
		}
		return false;
	}	
	
	/**
	  * checks if user is owner of group
	  * @params groupname
	  * @return boolean
	  */
	public function ownGroup( $groupname ) {
		$pattern = '!^' . $this->username . ':!';
		if( !preg_match( $pattern, $groupname ) ) {
			return false;
		}
		return true;
	}	
	
	/**
	  * add user to group
	  * @params groupname
	  * @params user
	  * @return boolean or error code
	  */
	public function addGroupMembers( $groupname, $user ) {	
		$user = preg_replace( '![^a-zA-Z0-9-:]+!', ' ', $user );						
		
		$cmd = $this->cmd['pts'] . ' adduser -user ' . $user . ' -group ' . escapeshellarg( $groupname );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			return true; 
		}
		return false;
	}	
	
	/**
	  * remove user from group
	  * @params groupname
	  * @params user
	  * @return boolean or error code
	  */
	public function removeGroupMembers( $groupname, $user ) {	
		$user = preg_replace( '![^a-zA-Z0-9-:]+!', ' ', $user );						
		
		$cmd = $this->cmd['pts'] . ' removeuser -user ' . $user . ' -group ' . escapeshellarg( $groupname );
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			return true; 
		}
		return false;
	}		
}
?>
