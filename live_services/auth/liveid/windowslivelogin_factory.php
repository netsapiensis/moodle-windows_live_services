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

require_once( 'windowslivelogin.php' );

/**
 * The WindowsLiveLogin_Factory creates and returns an instance of the WindowsLiveLogin class
 */
class WindowsLiveLogin_Factory //
{
    /**
     * Instantiates the WindowsLiveLogin class and sets values from moodleConfig
     * @param <array> $moodleConfig
     * @return <WindowsLiveLogin>
     */
    public static function initFromMoodleConfig( $moodleConfig )
    {
        $o = new WindowsLiveLogin();
        $o->setDebug( false );
        $appId = @$moodleConfig->block_live_services_appId;
        
        $secretKey = @$moodleConfig->block_live_services_secretKey;
        if($appId=='' || $secretKey=='')
        {
            print_error('The Microsoft Live Services Plug-in for Moodle block has not been properly configured. The application ID or key has not been set. Please contact your system administrator.');
            return null;
        }
        $o->setAppId( $moodleConfig->block_live_services_appId ); 
        
        $o->setSecret( $moodleConfig->block_live_services_secretKey ); 
        
        $o->setPolicyUrl( "$moodleConfig->wwwroot/auth/liveid/policy.php" ); 
        
        $o->setReturnUrl( "$moodleConfig->wwwroot/auth/liveid/OAuthWrapCallBack.php" );
        
        $o->setResponseType('code');
        $o->setScope(urlencode('wl.basic,wl.offline_access,wl.emails'));
        
        return $o;
    }
}
?>
