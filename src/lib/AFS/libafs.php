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

class afs {
	protected $cmd = array(
		'fs' => 'fs',			//'/usr/bin/fs',
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
	
	/**
	  * constructor
	  */
	public function afs( $dir ) {		
		$this->dir = $dir;
		$this->username = $_SERVER["REMOTE_USER"];			// TODO ev. escapen
		$this->groupmemberships();
		$this->listacl();
	}
	
	/**
	  * read rights
	  */
	protected function listacl() {
		$cmd = $this->cmd['fs'].' listacl "'.$this->dir.'"';
		exec( $cmd, $output, $ret );
		if( !$ret ) {
			for( $i = 2; $i < sizeof($output); $i++ ) {
				$tmp = explode( ' ', trim($output[$i]));
				array_push( $this->rights, array(
						'user' => 		$tmp[0],
						'rights' => 	$tmp[1]
					)				
				);
				if( $tmp[0] == $this->username || in_array( $tmp[0], $this->groupmember ) ) {
					for( $j = 0; $j < strlen($tmp[1]); $j++ ) {
						$this->userrights[$tmp[1][$j]] = true;
					}
				}
			}
		}
	}
	
	/**
	  * get groupmemberships
	  */
	protected function groupmemberships() {
		$cmd = $this->cmd['pts'].' membership '.$this->username.' '.$this->cmd['errtostd'];
		exec( escapeshellcmd($cmd), $output, $ret );
		if( !$ret ) {
			for( $i = 1; $i < sizeof($output); $i++ ) {
				array_push( $this->groupmember, trim($output[$i]));
			}
		}
	}	
	
	/**
	  * command allowed
	  */
	public function allowed( $cmd = NULL ) {
		switch( $cmd ) {
			case AFS_LIST:
				return $this->userrights['l'];
			case AFS_CREATE:
				return $this->userrights['i'];
			case AFS_DELETE:
				return $this->userrights['d'];
			case AFS_READ:
				return $this->userrights['r'];
			default:
				return false;
		}
	}	
}

?>
