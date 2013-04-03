<?php
###############################################################################
# This file is a part of the SmartWFM PHP-Backend                             #
# Copyright (C) 2013 Morris Jobke <kabum@users.sourceforge.net>               #
#                                                                             #
# SmartWFM PHP-Backend is free software; you can redestribute it and/or modify#
# it under terms of GNU General Public License by Free Software Foundation.   #
#                                                                             #
# This program is distributed in the hope that it will be useful, but         #
# WITHOUT ANY WARRANTY. See GPLv3 for more details.                           #
###############################################################################

class Archive{
	protected $archive;
	protected $useAbsolutePaths = FALSE;
	protected $basePath = NULL;

	/**
	  * constructor
	  * @param name name of the new archive
	  * @param basePath the base path
	  * @param flag ZipArchive:: flag
	  */
	public function Archive($name, $basePath, $flag = ZipArchive::CREATE) {
		$this->archive = new ZipArchive();
		$this->basePath = $basePath;
		if(!$this->archive->open($name, $flag))
			throw new Exception('Couldn\'t create archive.', -1);
	}

	/**
	  * close zip file
	  */
	public function close() {
		if(!$this->archive->close())
			throw new Exception('Couldn\'t close archive.', -2);
	}

	/**
	  * set useAbsolutePaths
	  * @param useAbsolutePaths
	  */
	public function setUseAbsolutePaths($useAbsolutePaths) {
		$this->useAbsolutePaths = $useAbsolutePaths;
	}

	public function addFolderOrFile($f) {
		if(@is_dir($f)){
			$this->addFolder($f);
		} elseif(@is_file($f)) {
			$this->addFile($f);
		}
	}

	protected function addFile($file){
		$shortName = substr($file, strlen($this->basePath) + 1);
		if($this->useAbsolutePaths) {
			if(!$this->archive->addFile($file)){
				throw new SmartWFM_Exception('Couldn\'t add file to archive.', -3);
			}
		} else {
			if(!$this->archive->addFile($file, $shortName)){
				throw new SmartWFM_Exception('Couldn\'t add file to archive.', -4);
			}
		}
	}

	protected function addFolder($folder){
		$shortName = substr($folder, strlen($this->basePath) + 1);
		if($this->useAbsolutePaths) {
			if(!$this->archive->addEmptyDir($folder)){
				throw new SmartWFM_Exception('Couldn\'t add empty folder to archive.', -5);
			}
		} else {
			if(!$this->archive->addEmptyDir($shortName)){
				throw new SmartWFM_Exception('Couldn\'t add empty folder to archive.', -6);
			}
		}
		$d = dir($folder);
		while (false !== ($name = $d->read())) {
			if($name != '.' && $name != '..') {
				$this->addFolderOrFile(Path::join($folder, $name));
			}
		}
	}
}
