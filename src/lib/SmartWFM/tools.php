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
	protected static $unknown_type = false; // 'application/octet-stream'; // unknown filetype according to http://www.rfc-editor.org/rfc/rfc2046.txt section 4.5.1
	static function get($filename) {
		$mode = SmartWFM_Registry::get('mimetype_detection_mode', 'internal');

		switch($mode) {
			case 'internal':
				if(function_exists('finfo_open') && function_exists('finfo_file')) {
					/* only PHP >= 5.3.0 */
					return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filename);
				} elseif (function_exists('mime_content_type')) {
					/* DEPRECATED */
					return @mime_content_type($filename);
				} /* else $mode = 'file' ( here isn't any break-statement ;) ) */
			case 'file':
				if(self::$mime_types == NULL) {
					/* initially create array from mime.types and save this in static variable */
					self::$mime_types = array();
					$lines = file('lib/SmartWFM/mime.types');
					foreach($lines as $line) {
						/* parse each line */
						if(preg_match('/^([\w-.\/]+)\s+(\w(\s*\w+)+)/', $line, $matches)) {
							$exts = preg_split('/ +/', $matches[2]);
							foreach($exts as $ext) {
								/* create for each extension - mimetype pair an entry */
								self::$mime_types[$ext] = $matches[1];
							}
						}
					}
				}
				/* extract file-extension */
				$file_ext = substr(strrchr($filename, '.' ), 1);
				if(!empty($file_ext) && array_key_exists($file_ext, self::$mime_types)) {
					/* extension found in array */
					return self::$mime_types[$file_ext];
				}
				return self::$unknown_type;
			case 'cmd_file':
				/* use -i option to support older versions of file */
				exec('file -i '. escapeshellarg($filename), $output, $return_var);
				if($return_var == 0) {
					foreach($output as $line) {
						if(preg_match('/^.*:\s+([\w-.\/]+)[;]{0,1}\s*/', $line, $matches)) {
							return $matches[1];
						}
					}
				}
				return self::$unknown_type;
			default:
				return self::$unknown_type;
				break;
		}
	}
}

?>
