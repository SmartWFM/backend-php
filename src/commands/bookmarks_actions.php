<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C)      2011 Morris Jobke <kabum@users.sourceforge.net>          #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class BookmarksActions_Load extends SmartWFM_Command {
	function process($params) {
		$fs_type = SmartWFM_Registry::get('filesystem_type');

		$BASE_PATH = SmartWFM_Registry::get('basepath','/');

		$filename = SmartWFM_Registry::get('bookmarks_filename', NULL);

		$response = new SmartWFM_Response();
		$response->data = array();

		if($filename != NULL && @file_exists($filename) && !@is_dir($filename)) {
			$data = @parse_ini_file($filename);
			if($data !== False) {
				$response->data = $data;
			}
		}
		return $response;
	}
}

SmartWFM_CommandManager::register('bookmarks.load', new BookmarksActions_Load());