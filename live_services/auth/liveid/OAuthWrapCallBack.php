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

// Load common settings.  For more information, settings.php for details.
include 'settings.php';
include 'windowslivelogin_factory.php';

global $CFG;
require_once( '../../config.php' );
//print_error($_REQUEST);
// Initialize the WindowsLiveLogin module.
$wll = WindowsLiveLogin_Factory::initFromMoodleConfig( $CFG );
$wll->setDebug( $DEBUG );
$params=$_REQUEST;
for($i=0;$i<count($params);$i++)
{
    if($params['return']||$params['refresh'])
    {
        break;
    }
    else
    {
        $wll->ProcessRequest();
        break;
    }
}

$AccessToken=@$_COOKIE['c_accessToken'];
$RefreshToken=@$_COOKIE['c_refreshToken'];
if( $AccessToken )
{
    setcookie( $COOKIE_ACCESS_TOKEN, $AccessToken, 0, '/' );
}
else
{
    setcookie( $COOKIE_ACCESS_TOKEN, '', 0, '/' );
}
if($RefreshToken)
{
    setcookie( $COOKIE_Refresh_TOKEN, $RefreshToken, 0, '/' );  
}
else
{
    setcookie( $COOKIE_Refresh_TOKEN, '', 0, '/' );
}
header( "Location: $LOGIN" );
?>
