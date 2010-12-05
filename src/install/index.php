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

include('libinstall.php');

$c = new Config();
$c->addOption( new BasePathOption() );
$c->addOption( new SettingFilenameOption() );
$c->addOption( new MimetypeDetectionModeOption() );
$c->addOption( new UseXSendfileOption() );
$c->addOption( new CommandsPathOption() );
$c->addOption( new CommandsOption() );
$c->addOption( new FilesystemTypeOption() );

$save = False;
if(array_key_exists('submit', $_POST)) {
	if($_POST['submit'] == 'save config')
		$save = True;
	unset($_POST['submit']);
}

$c->parse($_POST);

if($save)
	$c->save();
	
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" 
			content="application/xhtml+xml;charset=utf-8" />
		<title>Configuration SWFM - PHP Backend</title>
		<link rel="stylesheet" media="all" href="style.css" />
	</head>
<body>
	<center>
	<h1>Configuration SWFM - PHP Backend</h1>
	<div id="wrapper">
	  <?php if($c->fileExists()): ?>
		<div class="notice">
			The config file already exists. For security reasons you cannot overwrite 
			it.<!--<br /><br />

			If you see this anyway and you have already set up the backend, 
			just delete the "install" folder inside the backend folder. -->
		</div>
		<?php endif; ?>
		<?php 
			echo $c->buildHTML();			
		?>
	</div>
</body>
</html>
