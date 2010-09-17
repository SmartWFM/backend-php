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

$c->parse($_GET);
// DEBUG
$r = $c->generate();
if(!$r['error'])
	echo '<pre>'.$r['result'].'</pre>';
else
	echo '<pre>'.print_r($r['result'],1).'</pre>';
// DEBUG END

?>
