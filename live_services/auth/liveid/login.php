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

include 'settings.php';
include 'windowslivelogin_factory.php';


global $CFG;
require_once( '../../config.php' );
require_once( $CFG->dirroot . '/lib/moodlelib.php' );
setcookie('MOODLEID_', '', time()- 3600, '/' );
setcookie( $COOKIE_WLS_LIVEID, '', 0, '/' );
setcookie( $COOKIE_ACCESS_TOKEN, '', 0, '/' );
setcookie( $COOKIE_CONSENT_TOKEN_CID, '', 0, '/' );
setcookie( $COOKIE_Refresh_TOKEN, '', 0, '/' );

if(isset($_SESSION['msm_contacts']))
{
    unset($_SESSION['msm_contacts']);
}
//TODO remove UAT
if(isset($_SESSION['wls_EWSAuthEndPoint']))
{
    unset($_SESSION['wls_EWSAuthEndPoint']);
}
if(isset($_SESSION['wls_UAT_authorization']))
{
    unset($_SESSION['wls_UAT_authorization']);
}

//END TODO
// do not allow login if the "liveid" auth module is missing or disabled
if( is_enabled_auth( "liveid" ) != 1 )
{
    echo "The Windows Live ID authentication module is not installed or is disabled in this Moodle.<br />";
    echo "<a href='$CFG->wwwroot/login/index.php'>return to login page</a><br />";
    $SESSION->wantsurl = $CFG->wwwroot . '/';
}
else
{
    if (get_moodle_cookie() == '') {
        set_moodle_cookie('nobody');   // To help search for cookies
    }
    $wll = WindowsLiveLogin_Factory::initFromMoodleConfig( $CFG );
    $loginUrl = $wll->getLoginUrl();
    header( "Location: $loginUrl" );
}

