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

/**
 * provides:
 *  - read and write for bookmarks
 */

class BookmarksActions_Load extends SmartWFM_Command {
	function process($params) {
		$filename = SmartWFM_Registry::get('bookmarks_filename', NULL);

		$response = new SmartWFM_Response();
		$response->data = array();

		if($filename != NULL && @file_exists($filename) && !@is_dir($filename)) {
			$data = @parse_ini_file($filename);
			if($data !== False) {
				foreach($data as $value)
					$response->data[] = $value;
			}
		} else {
			// add initial default bookmarks
			$defaults = SmartWFM_Registry::get('default_bookmarks', array());
			foreach($defaults as $bookmark)
				$response->data[] = $bookmark;
		}
		return $response;
	}
}

SmartWFM_CommandManager::register('bookmarks.load', new BookmarksActions_Load());

class BookmarksActions_Save extends SmartWFM_Command {
	function process($params) {
		$filename = SmartWFM_Registry::get('bookmarks_filename', NULL);

		$fp = fopen($filename, 'w');

		if($fp === False) {
			throw new SmartWFM_Exception('Can\'t save bookmarks.', -1);
		} else {
			$response = new SmartWFM_Response();
			fwrite($fp, "[SmartWFM Bookmarks]\r\n");

			$i = 0;
			foreach($params as $value) {
				fwrite($fp, 'file' . $i . '[] = ' . escapeshellarg($value[0]) . "\r\n");
				fwrite($fp, 'file' . $i . '[] = ' . escapeshellarg($value[1]) . "\r\n");
				$i++;
			}

			$response->data = true;
		}

		return $response;
	}
}

SmartWFM_CommandManager::register('bookmarks.save', new BookmarksActions_Save());