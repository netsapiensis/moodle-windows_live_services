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

require_once('../../../../config.php');
require_once($CFG->dirroot.'/blocks/live_services/services/alerts/message.class.php');
require_once($CFG->dirroot.'/blocks/live_services/services/alerts/recservicesgroupmessage.class.php');
require_once($CFG->dirroot.'/blocks/live_services/services/alerts/recservicescontact.class.php');
require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
try {
    $subject = @$_POST[ 'alertSubject' ]==null?getLocalizedString('alertsUntitled'):@$_POST[ 'alertSubject' ];
    $body = @$_POST['alertBody']==null?' ':@$_POST['alertBody'];
    if(get_magic_quotes_gpc()) {
        $subject = htmlentities(stripslashes($subject));
        $body = htmlentities(stripslashes($body));
    }
    else {
        $subject = htmlentities($subject);
        $body = htmlentities($body);
    }
    $courseId = @$_POST['courseId'];
    $result = '';
    $errorMessage = '';
    if(is_null($courseId) || $courseId==1) {
        $result = getJsonResultString('-1', 'Alerts can only be sent from a course module','true', 'Alerts can only be sent from a course module\n');
    }
    else {
        if(is_null($body) ||  strlen( $body ) == 0 ) {
            $body = " ";
        }
        else {
            $message = new message();
            if(isset($_COOKIE['wls_liveId'])) {
                $fromContact = $_COOKIE['wls_liveId'];
            }
            else {
                $fromContact = $USER->msn;
            }
            $contact = new RecServicesContact($fromContact, null);
            $contacts = array($contact);
            $groupName = AlertsHelper::GetGroupNameByCourseId($courseId);
            $groupMessage = new RecServicesGroupMessage('',$subject,$body, $contacts,$groupName,$body,$body,$body);
            $result = $message->groupDeliver($groupMessage);
        }
    }
    echo($result);
}
catch(Exception $exc)
{
   error_log( "Live Alerts Error: " . $exc->getMessage() );   	
   echo(getJsonResultString('-1', $exc->getMessage(),'true', $exc->getMessage()));
}
?>
