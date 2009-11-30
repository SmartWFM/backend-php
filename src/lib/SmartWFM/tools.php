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
			$paths = array_merge( $paths, (array)$arg );
		}
		foreach( $paths as &$path ) {
			$path = trim( $path, '/' );
		}
		if( substr( $args[0], 0, 1 ) == '/' ) {
			$paths[0] = '/' . $paths[0];
			return join('/', $paths);
		} else {
			$path = join('/', $paths);
			$path = join(
				'/',
				array(
					trim('/', SmartWFM_Registry::get('basepath','/')),
					$path
				)
			);
			return '/'.$path;


		}
	}
	static function validate($base, $path) {
		$path = str_replace('../','', $path);
		$path = realpath($path);
		return preg_match('/^'.preg_quote($base, '/').'/', $path);
	}
}

?>
