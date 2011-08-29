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

if( !defined( 'MOODLE_INTERNAL' ) )
{
    die( 'Direct access to this script is forbidden.' );    ///  It must be included from a Moodle page
}

require_once( $CFG->libdir . '/authlib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
require_once ($CFG->libdir . '/moodlelib.php');

/**
 * The auth_plugin_liveid class extends auth_plugin_base and provides LiveID authentication
 */
class auth_plugin_liveid extends auth_plugin_base
{
    /**
     * class constructor
     */
    function auth_plugin_liveid()
    {
        $this->authtype = 'liveid';
        $this->config = get_config( 'auth/liveid' );
    }
     /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     * @global <array> $CFG - the global configuration array
     * @param <string> $userName - the user name of the current user
     * @param <string> $password - the password of the current user
     * @return <bool> (success/failure)
     */
    function user_login( $userName, $password )
    {
        global $CFG;
        require_once( $CFG->dirroot . '/auth/liveid/windowslivelogin_factory.php' );
        require_once( $CFG->dirroot . '/auth/liveid/settings.php' );

        // Initialize the WindowsLiveLogin module.
        $wll = WindowsLiveLogin_Factory::initFromMoodleConfig( $CFG );
        $consentToken = @$_COOKIE[ $COOKIE_ACCESS_TOKEN ];
        if( $consentToken )
        {
            // TODO remove this
            $_SESSION[ 'wls_UAT_authorization' ] = $userName;
            return true;
        }
        return false;
    }

    /**
     * We are not implementing the ability to update the password. Password updates
     * are controlled by Windows Live
     * @return <bool> - always false
     */
    function user_update_password( $user, $newpassword )
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal', false if 'external'
     * Since Windows Live is external, we return false.
     * @return <bool> - always false
     */
    function is_internal()
    {
        return false;
    }

    /**
     * eturns true if this authentication plugin can change the user's password.
     * @return <bool> - always false
     */
    function can_change_password()
    {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can be used.
     * @return <string> - always empty
     */
    function change_password_url()
    {
        return '';
    }
    
    /**
     * Returns true if plugin allows resetting of internal password.
     * @return <bool> - always false
     */
    function can_reset_password()
    {
        return false;
    }

    /**
     * function that is called before logging out of Moodle
     * redirect to liveid logout if the webauthtoken is not empty
     * @global <array> $CFG - the global configuration array
     */
    function prelogout_hook()
    {
        global $CFG; 
        $accessToken = @$_COOKIE[ 'wls_AccessToken' ];       
        if( strlen( $accessToken ) > 0 )
        {   
            setcookie('MOODLEID_', '', time()- 3600, '/' );
            setcookie('wls_liveId','',0,'/');
            setcookie('wls_AccessToken','',0,'/');
            setcookie('wls_consentToken_cid','',0,'/');
            setcookie('wls_RefreshToken','',0,'/');
            setcookie('MoodleSession','',0,'/');
            setcookie('SESSION','',0,'/');
            setcookie('MoodleSessionTest','',0,'/');
            
            
            if(isset($_SESSION['msm_contacts']))
            {
                unset($_SESSION['msm_contacts']);
            }
            
            if(isset($_SESSION['wls_EWSAuthEndPoint']))
            {
                unset($_SESSION['wls_EWSAuthEndPoint']);
            }
            if(isset($_SESSION['wls_UAT_authorization']))
            {
                unset($_SESSION['wls_UAT_authorization']);
            }
            
            if(isset($_SESSION['emailContent']))
            {
                unset($_SESSION['emailContent']);
            }
            if(isset($_SESSION['calendarContent']))
            {
                unset($_SESSION['calendarContent']);
            }
            
            if(isset($_SESSION['msm_userIsOnExchange'])) 
            { 
                unset($_SESSION['msm_userIsOnExchange']); 
            }
            $GLOBALS[ 'redirect' ] = "$CFG->wwwroot/auth/liveid/logout.php";
        }
    }
}

?>
