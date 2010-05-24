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
	static public function getFiles($path){
		$files = array();
		$d = dir($path);
		while (false !== ($name = $d->read())) {
			if($name != '.' && $name != '..') {
				$p = Path::join($path, $name);
				if(@is_dir($p)){
					foreach(Archives::getFiles($p) as $e) {
						$files[] = $e;
					}					
				} else {
					$files[] = $p;
				}				
			}
		}
		return $files;
	}
}
