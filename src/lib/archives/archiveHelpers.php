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

class ArchiveHelpers{
	public static function fileNamesToTreeStruct($files){
		$tree = array();
		foreach($files as $f) {
			if(substr($f, -1) == '/')
				continue;
			$f = ltrim($f, '/');
			$tree = ArchiveHelpers::fileNameToArray($f, $tree);
		}
		return $tree;
	}

	public static function fileNameToArray($file, $array){
		$s = explode('/',$file);
		if(count($s) > 1) {
			if(!array_key_exists($s[0], $array)) {
				$array[$s[0]] = ArchiveHelpers::fileNameToArray(substr(strstr($file,'/'),1), array());
			} else {
				$array[$s[0]] = ArchiveHelpers::fileNameToArray(substr(strstr($file,'/'),1), $array[$s[0]]);
			}
		} else {
			$array[$s[0]] = False;
		}
		return $array;
	}
}
