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

class Archives{
	static public function getFilesAndFolders($path){
		$files = array();
		$folders = array();
		$d = dir($path);
		$folders[] = $path;
		while (false !== ($name = $d->read())) {
			if($name != '.' && $name != '..') {
				$p = Path::join($path, $name);
				if(@is_dir($p)){
					$tmp = Archives::getFilesAndFolders($p);
					foreach($tmp[0] as $e) {
						$files[] = $e;
					}
					foreach($tmp[1] as $e) {
						$folders[] = $e;
					}
				} else {
					$files[] = $p;
				}
			}
		}
		$d->close();
		return array($files, $folders);
	}

	static public function fileNamesToTreeStruct($files){
		$tree = array();
		foreach($files as $f) {
			if(substr($f, -1) == '/')
				continue;
			$f = ltrim($f, '/');
			$tree = Archives::fileNameToArray($f, $tree);
		}
		return $tree;
	}

	static public function fileNameToArray($file, $array){
		$s = explode('/',$file);
		if(count($s) > 1) {
			if(!array_key_exists($s[0], $array)) {
				$array[$s[0]] = Archives::fileNameToArray(substr(strstr($file,'/'),1), array());
			} else {
				$array[$s[0]] = Archives::fileNameToArray(substr(strstr($file,'/'),1), $array[$s[0]]);
			}
		} else {
			$array[$s[0]] = False;
		}
		return $array;
	}
}
