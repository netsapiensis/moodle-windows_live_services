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

require_once( $CFG->dirroot . '/config.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * The IdentityService loads the LiveId account and renders the Home and Help links.
 */
class IdentityService
{
    private $_isSignedIn = false;
    private $_liveId = '';
    private $_contactsArray = array();

    /**
     * Sets the LiveId and sets the private isSignedIn property
     * @param <string> $liveId - The LiveID of the currently logged in user
     */
    function ValidateSignInAndConsent( $liveId )
    {
        $this->_liveId = $liveId;
        $this->_isSignedIn = ( strlen( $this->_liveId ) > 0 );
    }

    /**
     * Sets the array of contacts for the currently logged in user
     * @param <string> $cid - the contact id for the currently logged in user
     * @param <string> $delegationToken - the Delegated Auth token
     */
    function LoadAccountData( $cid, $delegationToken )
    {
        if( $this->_isSignedIn && strlen( $cid ) > 0 && strlen( $delegationToken ) > 0 )
        {
            $request = "https://livecontacts.services.live.com/users/@L@$cid/rest/LiveContacts?Filter=LiveContacts(Contact(WindowsLiveID))";
            $curl = new CurlLib();
            $httpHeaders = array( "Authorization: DelegatedToken dt=\"$delegationToken\"" );

            $response = $curl->getRestResponse( $request, $httpHeaders );
            if( strlen( $response ) )
            {
                $xmlDocument = new DOMDocument();
                $xmlDocument->loadXML( $response );
                $xpathContext = new DOMXPath( $xmlDocument );

                $xmlNodeList = $xpathContext->query( "/LiveContacts/Contacts/Contact/WindowsLiveID" );

                $this->_contactsArray = array();
                foreach( $xmlNodeList as $xmlNode )
                {
                    $key = $xmlNode->nodeValue;
                    if( strlen( $key ) > 0 )
                    {
                        array_push( $this->_contactsArray, $key );
                    }
                }
            }
        }
    }
    /**
     * Renders the Home and Help section of the Microsoft Live Services Plug-in for Moodle block
     * @global <array> $CFG - the global configuration array
     * @param <int> $courseId - the id of the course being viewed
     * @param <type> $version - Microsoft Live Services Plug-in for Moodle block version (if defined)
     * @return <string> (HTML)
     */
    function Render( $courseId, $version )
    {
        global $CFG;
        $identityServicePath = "$CFG->wwwroot/blocks/live_services/services/identity";
        $identityLogoAltText = getLocalizedString( 'identityLogoAltText' );
        $identityHelpLink = getLocalizedString('identityHelpLink');
        $identityPrivacyLink = getLocalizedString('identityPrivacyLink');
        $liveServicesHelpFile = "$CFG->wwwroot/help.php?module=live_services&file=live_services_help.php";
        $liveServicesHelpOnClick = 'javascript:void window.open("'.$liveServicesHelpFile.'","liveserviceshelp","menubar=0,location=0,scrollbars,status,resizable,width=800,height=600",0);';
        $liveServicesPrivacyFile = "$CFG->wwwroot/auth/liveid/policy.php";
        $liveServicesPrivacyOnClick = 'javascript:void window.open("'.$liveServicesPrivacyFile.'","liveservicesprivacy","menubar=0,location=0,scrollbars,status,resizable,width=800,height=600",0);';

        if( $this->_isSignedIn )
        {
            $displayName = wrapDisplayName($this->_liveId);
            $identityHomeLink = getLocalizedString( 'identityHomeLink' );
            
            return <<<IDENTITY_SERVICE
            <div>
                <p class='live_service_identity'><!-- version: $version -->$displayName<br />
                <a target='_blank' href='http://my.live.com'>$identityHomeLink</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a
                        href='#' onclick='$liveServicesHelpOnClick'>$identityHelpLink</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a
                        href='#' onclick='$liveServicesPrivacyOnClick'>$identityPrivacyLink</a></p>
            </div>
IDENTITY_SERVICE;
        }
        else
        {
            $identityNotSignedIn = getLocalizedString( 'identityNotSignedIn' );
            $identitySignInText = getLocalizedString('identitySignInText');
            $identitySignInButtonText = getLocalizedString('identitySignInButtonText');
            //1$identitySignInParagraph = getLocalizedString( 'identitySignInParagraph' );
            //$identitySignInParagraph = str_replace( '[[url]]', $CFG->wwwroot . '/auth/liveid/login.php', $identitySignInParagraph );
            $signInButtonSrc = $CFG->wwwroot.'/blocks/live_services/shared/images/glossybutton88.gif';
            $signInUrl = $CFG->wwwroot.'/auth/liveid/login.php';
            return <<<IDENTITY_SERVICE_NOT_LOGGED_IN
            <div>
                <p class='live_service_identity'>$identityNotSignedIn&nbsp;&nbsp;|&nbsp;&nbsp;<a
                        href='#' onclick='$liveServicesHelpOnClick'>$identityHelpLink</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a
                        href='#' onclick='$liveServicesPrivacyOnClick'>$identityPrivacyLink</a></p>
            </div>
            <div class='live_service_div' style="min-height:48px">
                <div style="padding:8px 0px">
                <a href="$signInUrl" style="background:transparent url($signInButtonSrc) no-repeat scroll top right;height: 32px;width:88px;display: block;float:left;margin:0px;text-decoration: none;text-align:center;color: #333333;font-family: 'Segoe UI', Arial, Helvetica, sans-serif;font-size:1.2em;font-weight:normal;">
                <span style="height: 32px;display: block;float:left;margin-right: 0px;text-decoration: none;color: #333333;font-family: 'Segoe UI', Arial, Helvetica, sans-serif;font-size:1.1em;font-weight:normal;line-height:12px;padding:9px 12px 11px 14px;background: transparent url($signInButtonSrc) no-repeat;">$identitySignInButtonText</span></a>
                <span style='float:right;font-size:10px;line-height:17px;width:75px;'>$identitySignInText</span>
                </div>
            </div>
IDENTITY_SERVICE_NOT_LOGGED_IN;
        }
    }

    /**
     * Returns the value of the IsSignedIn property
     * @return <bool>
     */
    function IsSignedIn()
    {
       return $this->_isSignedIn;
    }

    /**
     * Returns the value of the Contacts array
     * @return <array>
     */
    function Contacts()
    {
        return $this->_contactsArray;
    }

    /**
     * Returns a bool value indicating whether the currently signed in user is a teacher of the course being viewed
     * @global <array> $CFG - the global configuration array
     * @param <int> $courseId - the id of the course being viewed. This will be '1' if the user is not viewing a course.
     * @return <bool>
     */
    function IsTeacher($courseId)
    {
        global $CFG, $DB;
        $isTeacher = false;
        if(strlen($this->_liveId) > 0)
        {
            $sql = "SELECT COUNT(u.id) AS isTeacher
            FROM {role_assignments} AS ra
                JOIN {user} AS u ON ra.userid = u.id
                JOIN {context} cx ON ra.contextid = cx.id
            WHERE u.msn = '".$this->_liveId."'
                AND ra.roleid IN (3,4)
                AND cx.contextlevel = '50'
                AND cx.instanceid = ".$courseId;
            try
            {
                $result = $DB->get_record_sql($sql);
                if((int)$result->isTeacher > 0 )
                {
                    $isTeacher = true;
                }
            }
            catch(Exception $exc){
		handleException($exc);				    
            }
        }
        return $isTeacher;
    }
}
?>
