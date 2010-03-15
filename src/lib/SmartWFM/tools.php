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

class Path {
	static function join() {
		$args = func_get_args();
		$paths = array();
		
		foreach( $args as $arg ) {
			$path = trim( $arg, '/' );
			if($path != '') {
				array_push($paths, $path);
			}
		}

		if( substr( $args[0], 0, 1 ) == '/' ) {
			$paths[0] = '/' . $paths[0];
			$path = join('/', $paths);
		} else {
			$path = join('/', $paths);
			$path = join(
				'/',
				array(
					trim('/', SmartWFM_Registry::get('basepath','/')),
					$path
				)
			);
			$path = '/'.$path;
		}
		$path .= '/';
		$path = str_replace( '/./', '/', $path );
		$path = str_replace( '//', '/', $path );
		while( preg_match( '!/[^/]+/../!', $path ) ) {
			preg_match_all( '!/[^/]+/../!', $path, $tmp );
			foreach( $tmp as $value ) {
				if( $value != '/../../' ) {
					$path = str_replace( $value, '/', $path );
				}
			}
			if( substr( $path, 0, 3 ) == '/..' ) {
				return false;
			}
		}		
		$path = substr( $path, 0, -1 );
		return $path;
	}
	static function validate($base, $path) {
		$path_rep = str_replace('../','', $path);
		$path = realpath($path_rep);
		// realpath() returns nothing if the path doesn't exist, so we set the old path
		if($path === False) {
			$path = $path_rep;
		}
		return preg_match('/^'.preg_quote($base, '/').'/', $path);
	}
}

?>
