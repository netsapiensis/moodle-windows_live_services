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
require_once($CFG->dirroot.'/blocks/live_services/services/alerts/subscription.class.php');

global $DB;
//create a new instance of the alert class
$subscription = new Subscription();

//site short name will be part of the group name
$site = $DB->get_record( "course", array ("format" => "site") );

//get the user so we can obtain his MSN address as the partnerUID
if(isset($_COOKIE['wls_liveId'])) {
    $partnerUid = $_COOKIE['wls_liveId'];
}
else {
    if($USER) {
        $partnerUid = $USER->msn;
    }
    else {
        error("Cannot identify the currently logged in user.");
    }
}

//the course short name and full name will be used in the groupName and groupDescription, respectively
if(isset($_GET['courseid'])) {
    $courseid = $_GET['courseid'];
}
else {
    if(isset($_POST['courseid'])) {
        $courseid = $_POST['courseid'];
    }
}
$course = $DB->get_record( "course", array("id" => $courseid) );
if(!$course ) {
    error( "Course is misconfigured" );
}

$groupName = $site->shortname.'_'.$course->shortname;
$groupDescription = $course->fullname;

$action = 'subscribe_pop.php?courseid='.$courseid;

$status = '';
$confirmationMessage = '';

//when returning from Windows Live initial registration, register the group with the new Subscriber.
$initiateSignupComplete = false;

if(isset($_GET['addgroup'])) {
    $addGroup = $_GET['addgroup'];
    if($addGroup==true){
        $resultString = $subscription->addGroup($partnerUid, $groupName, $groupDescription);
        $result = json_decode($resultString);
        if($result->error=='false') {
            $initiateSignupComplete = true;
            $confirmationMessage = getLocalizedString('alertsSubscribeConfirmation1');
            $confirmationMessage = str_replace('[[groupDescription]]',$groupDescription,$confirmationMessage);            
        }
        else {
            $initiateSignupComplete = false;
            $confirmationMessage = $result->reason;
        }        
    }
}

else {
    //builds messaging for the page based on alert status and which button was clicked.

    if(isset($_POST['statusHidden'])) {
        $status = $_POST['statusHidden'];
        
        if(isset($_POST['buttonClickedHidden'])) {
            $buttonClicked = $_POST['buttonClickedHidden'];
        }
        switch($status) {
            case 'NOT_SUBSCRIBED': {
                    //subscribe the user to alerts and register him for this group
                    $resultString = $subscription->initiateSignup($partnerUid, $courseid);
                    break;
                }
            case 'SUBSCRIBED_FOR_NO_COURSES':
            case 'SUBSCRIBED_FOR_OTHER_COURSES': {
                    $result = json_decode($subscription->addGroup($partnerUid, $groupName, $groupDescription));
                    if($result->error=='false') {
                        $confirmationMessage = getLocalizedString('alertsSubscribeConfirmation2');
                        $confirmationMessage = str_replace('[[groupDescription]]',$groupDescription,$confirmationMessage);
                    }
                    else {
                        $confirmationMessage = $result->reason.'<br/><br/>'.$result->exceptionmessage;
                    }
                    break;
                }
        }
    }
}
/**
 * 
 * @param string $alertsStatus
 * @param string $groupDescription
 * @return string
 */
function getContent($alertsStatus, $groupDescription) {
    $message = '';
    switch($alertsStatus) {
        case "NOT_SUBSCRIBED": {
                $message = getLocalizedString('alertsNotSubscribedMessage');
                $message = str_replace('[[groupDescription]]',$groupDescription,$message);
                break;
            }
        case "SUBSCRIBED_FOR_NO_COURSES": {
                $message = getLocalizedString('alertsSubscribedNoCoursesMessage');
                $message = str_replace('[[groupDescription]]',$groupDescription,$message);
                break;
            }
        case "SUBSCRIBED_FOR_OTHER_COURSES": {
                $message = getLocalizedString('alertsSubscribedOtherCoursesMessage');
                $message = str_replace('[[groupDescription]]',$groupDescription,$message);
                break;
            }
        case "ERROR": {
                $message = getLocalizedString('alertsErrorMessage');
                break;
            }
    }
    return $message;
}

$resultString = $subscription->getAlertsStatus($partnerUid, $groupName);
if(!strstr($resultString,'error')) {
    $status = $resultString;
    $statusMessage = getContent($resultString, $groupDescription);
}
else {
    $status = 'ERROR';
    $resultString = json_decode($resultString);
    $statusMessage = $resultString->reason;
}

print_header(getLocalizedString('alertsSubscribeHeader').' - '.format_string($SITE->fullname));
?>
<form name="theForm" id="theForm" method="POST" action="<?php echo($action) ?>">
<div style="margin:48px 18px 48px 18px;width:700px;">
    <h1>Windows Live Alerts Subscription Management</h1>
    <div id="actionSection" style="<?php if($confirmationMessage=='') echo('display:block;'); else echo('display:none'); ?>">
        <p>
            <?php echo($statusMessage); ?>
        </p>
        <input name="courseid" id="courseid" type="hidden" value="<?php echo($courseid); ?>" />
        <input name="statusHidden" id="statusHidden" type="hidden" value="<?php echo($status); ?>" />
        <input name="buttonClickedHidden" id="buttonClickedHidden" type="hidden" value="Unknown" />
        <div style="margin-top:30px;">
                <div style="display:inline">
                <input name="subscribeButton" id="subscribeButton" type="button" value="<?php echo(getLocalizedString('alertsSubscribeButtonText'));?>" style="margin-right:24px" onclick="buttonClicked(this.value)" />
                <input type="button" name="cancelButton" id="cancelButton" value="<?php echo(getLocalizedString('alertsCancelButtonText'));?>" onclick="javascript:window.close();" />
                </div>
        </div>
    </div>
    <div id="confirmationSection" style="<?php if($confirmationMessage!='') echo('display:block;'); else echo('display:none'); ?>">
        <p><?php echo($confirmationMessage) ?></p>
        <input type="button" name="closeButton" id="closeButton" value="<?php echo(getLocalizedString('alertsCloseButtonText'));?>"  onclick="javascript:refreshParent();"  />
    </div>
</div>
</form>
<script type="text/javascript">
        this.onbeforeunload = function() {
            opener.location.reload(true);
        }
        /*
         * closes self and refreshes the parent window with updated status
         */
        function refreshParent() {
            self.close();
        }
        /*
         * saves the value of the button to a hidden field that can be read from php script
         * @param <string> val - the value of the button clicked
         */
        function buttonClicked(val) {
            document.theForm.buttonClickedHidden.value = val;
            document.theForm.submit();
        }
        /*
         * redirects to the input url
         * @param <string> url - the url to redirect to
         */
        function returnToReferrer(url) {
            location.href = url;
        }
    </script>