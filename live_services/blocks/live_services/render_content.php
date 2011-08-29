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

    global $CFG;
    require_once( $CFG->dirroot . '/lib/moodlelib.php' );
    require_once( $CFG->dirroot . '/blocks/live_services/services/identity/identity_service.php' );
    require_once( $CFG->dirroot . '/blocks/live_services/shared/popup_dialog.php' );
    require_once( $CFG->dirroot . '/auth/liveid/settings.php' );
    
    $content_html = '';
    // do not show the block if the "liveid" auth module is missing or disabled
    if( is_enabled_auth( 'liveid' ) != 1 )
    {        
        return;
    }

    // include the styles for the dashboard
    $stylesheet = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/block_styles.php"/>';
    $content_html .= $stylesheet;
    // wrap the whole block in a div
    $content_html .= "";

    // identity information
    $identity = new IdentityService();

    $liveId = @$_COOKIE[ $COOKIE_WLS_LIVEID ];
    $identity->ValidateSignInAndConsent( $liveId );

    // load the account data
    $cid = @$_COOKIE[ $COOKIE_CONSENT_TOKEN_CID ];
    $delegationToken = @$_COOKIE[ $COOKIE_CONSENT_TOKEN_DELEGATION_TOKEN ];
    //$identity->LoadAccountData( $cid, $delegationToken );

    // render the identity block
    if( true || !empty( $CFG->block_live_services_showIdentity ) )
    {
        $content_html .= $identity->Render( $courseId, 2 );
    }

    // get all the services that exist in the 'services' folder
    $servicesPath = $CFG->dirroot . '/blocks/live_services/services';
    $servicesRootDirectory = opendir( $servicesPath );
    if( $servicesRootDirectory )
    {
        $serviceDirectories = glob( $servicesPath . '/*', GLOB_ONLYDIR );
        for( $i = 0; $i < count( $serviceDirectories ); $i++ )
        {
            $pathArray = explode( '/', $serviceDirectories[ $i ] );
            $serviceName = $pathArray[ count( $pathArray ) - 1 ];
            $serviceFilename = dirname( __FILE__ ) . '/services/' . $serviceName . '/' . $serviceName . '_service.php';
            if( file_exists ( $serviceFilename ) )
            {
                require_once( $serviceFilename );
            }
        }
    }

        // search ( no sign in needed )
    if( class_exists( 'SearchService' ) && !empty( $CFG->block_live_services_showSearch ) )
    {
        $searchServiceObject = new SearchService();
        $content_html .= $searchServiceObject->Render();
    }

    if( $identity->IsSignedIn() )
    {
        // email
        if( class_exists( 'EmailService' )  )
        {
            $emailServiceObject = new EmailService();
            $content_html .= $emailServiceObject->Render(false);
        }

        // messenger
        if( class_exists( 'MessengerService' ) && !empty( $CFG->block_live_services_showMessenger ) )
        {
            $messengerServiceObject = new MessengerService();
            $content_html .= $messengerServiceObject->Render( $courseId, $identity->Contacts(), $identity->IsTeacher($courseId) );
        }
    }


    // shared popup dialog
    $popupDialogServiceObject = new PopupDialogService();
    $content_html .= $popupDialogServiceObject->Render();

    // close the wrap on the whole block
   $content_html .= '<div style="clear:both"></div>';
    //}
       
?>
