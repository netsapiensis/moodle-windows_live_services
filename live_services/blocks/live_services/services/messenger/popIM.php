<?php

/*******************************************************************************
Copyright (C) 2009  Microsoft Corporation. All rights reserved.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as
published by the Free Software Foundation.

Copyright (C) 2011 NetSapiensis AB. All rights reserved.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*******************************************************************************/

require_once( '../../../../config.php');
require_once( $CFG->dirroot . '/lib/moodlelib.php' );

$invitee = @optional_param( 'invitee', '', PARAM_TEXT);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>Windows Live Messenger - Web Client</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <iframe src="http://settings.messenger.live.com/Conversation/IMMe.aspx?invitee=<?php echo $invitee ?>&mkt=en-US"
            width="100%" height="100%" style="border: solid 1px gray; width: 100%; height: 100%;" frameborder="0" scrolling="off"></iframe>
    </body>
</html>
