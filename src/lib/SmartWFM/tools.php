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
		$regex = '!/[^/^(\.\.)]+/\.\./!';
		while( preg_match( $regex, $path ) ) {
			preg_match_all( $regex, $path, $tmp );
			foreach( $tmp[0] as $key => $value ) {
				$path = str_replace( $value, '/', $path );
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

class MimeType {
	protected static $mime_types = NULL;
	static function get($filename) {
		$file_ext = substr(strrchr($filename, '.' ), 1);
		$mode = SmartWFM_Registry::get('mimetype_detection_mode', 'internal');
		if(!function_exists('finfo_open') && !function_exists('finfo_file') && $mode == 'internal') {
			if(!function_exists('mime_content_type')) {
				$mode = 'file';
			} else {
				/* DEPRECATED */
				return @mime_content_type($filename);
			}
		} elseif ($mode == 'internal') {
			/* only PHP >= 5.3.0 */
			return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filename);
		}
		if($mode == 'cmd_file') {
			exec('file --mime-type '. $filename, $output);
			foreach($output as $line) {
				if(preg_match('/^.*:\s+([\w-.\/]+)\s*/', $line, $matches)) {
					return $matches[1];
				}
			}
		} else {

			if(self::$mime_types == NULL) {
				self::$mime_types = array();
				$lines = file('lib/SmartWFM/mime.types');
				foreach($lines as $line) {
					if(preg_match('/^([\w-.\/]+)\s+(\w(\s*\w+)+)/', $line, $matches)) {
						$exts = preg_split('/ +/', $matches[2]);
						foreach($exts as $ext) {
							self::$mime_types[$ext] = $matches[1];
						}
					}
				}
			}
			if(!empty($file_ext) && array_key_exists($file_ext, self::$mime_types)) {
				return self::$mime_types[$file_ext];
			}
			return False;
		}
	}
}

?>
