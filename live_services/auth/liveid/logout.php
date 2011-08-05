<?php

/*******************************************************************************
Copyright (C) 2009  Microsoft Corporation. All rights reserved.
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


include 'settings.php';
include 'windowslivelogin_factory.php';

global $CFG;
require_once( '../../config.php' );

$wll = WindowsLiveLogin_Factory::initFromMoodleConfig( $CFG );
?>
<html>
<head>
	<meta http-equiv="refresh" content="5;url=<?php echo $CFG->wwwroot;?>" />
	<title></title>
</head>
<body bgcolor="#F4F4F4">
    <center>
        <table>
            <tr>
                <td width="450" align="center">
                    <h3 style="text-align:center;"><img src="WindowsLive.png"/></h3>
                    <h5 style="text-align: left;">Logout Successful</h5>
                    <hr/>
                    <p>Your account has been successfully logout Windows Live. 
                    Please wait while you are back to Moodle.</p>
                    <p>If you are not automatically redirected, click the 'Back to Moodle' button below.</p>
                    <form method="post" name="dellogout" action="<?php echo $wll->getLogoutUrl();?>" target="iframeLogout"> 
                        <input type="submit" value="Back to Moodle" />
                        <iframe name="iframeLogout" width="0" height="0" scrolling="no">
                        </iframe>
                    </form>
                    <script language="JavaScript">document.dellogout.submit();</script>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>