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

require_once( '../../../../config.php' );
require_once( $CFG->dirroot . '/lib/moodlelib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

global $DB;

$type = @optional_param('type', '', PARAM_TEXT);
if(get_magic_quotes_gpc())
{
    // Fixed bug. &Atilde; entity cannot pass validation. Replace htmlentities with htmlspecialchars to avoid converting special latin chars.
    $to = htmlspecialchars(stripslashes($_POST[ 'to' ]));

    $subject = htmlspecialchars(stripslashes($_POST[ 'subject' ]));
    
    if( $type != 'appointment' )
        $body = htmlspecialchars(stripslashes( nl2br ( $_POST[ 'body' ]) ));
    else
        $body = htmlspecialchars(stripslashes( $_POST[ 'body' ] ));
}
else
{
    $to = htmlspecialchars($_POST[ 'to' ]);
    $subject = htmlspecialchars($_POST[ 'subject' ]);
    if( $type != 'appointment' )    
        $body = htmlspecialchars( nl2br ( $_POST[ 'body' ] ));
    else
        $body = htmlspecialchars( $_POST[ 'body' ] );    
}

$startDate = @optional_param('startDate', '', PARAM_TEXT);
$startTime = @optional_param('startTime', '', PARAM_TEXT);
$endDate = @optional_param('endDate', '', PARAM_TEXT);
$endTime = @optional_param('endTime', '', PARAM_TEXT);
$isAllDayEvent = @optional_param('isAllDayEvent', '', PARAM_TEXT);
$attachmentIdArray = array();
$attachmentIndex = 1;
$action = @optional_param('action', '', PARAM_TEXT);
$itemId = @optional_param('itemid', '', PARAM_TEXT);
$itemId = str_replace(' ', '+', $itemId);
$changeKey=@optional_param('ck', '', PARAM_TEXT);
$changeKey=str_replace(' ', '+', $changeKey );

while(@$_POST['attachment'.$attachmentIndex]!==null)
{
    array_push($attachmentIdArray,str_ireplace('resource','',$_POST['attachment'.$attachmentIndex]));
    $attachmentIndex = $attachmentIndex + 1;
}


if( $type == 'appointment' )
{
    if($isAllDayEvent =='on' || $isAllDayEvent=='true')
    {
        $isAllDayEvent = 'true';
    }
    else
    {
        $isAllDayEvent = 'false';
    }

}

$toArrayRaw = explode(';',$to);
$toArray = array();
if(count($toArrayRaw) > 0)
{
    for($i = 0;$i<count($toArrayRaw);$i++)
    {
        $emailAddress = trim(extractEmailAddress($toArrayRaw[$i]));
        if(strlen($emailAddress) > 0)
        array_push($toArray,$emailAddress);
    }
}
else
{
    $emailAddress = extractEmailAddress($to);
    array_push($toArray,$emailAddress);
}

// Get the LiveId of the logged in user
$loggedInLiveId = $_COOKIE['wls_liveId'];

// Check to see if ExchangeLabs is enabled for this moodle instance.
if( @$CFG->block_live_services_useEWS == 1)
{
    // This username/password needs to be that of the Impersonation Account
    $impersonationLiveId = @$CFG->block_live_services_ewsServiceAccountUserName;
    $impersonationPassword = @$CFG->block_live_services_ewsServiceAccountPassword;
    $ews_auth = new EWSAuthentication($impersonationLiveId, $impersonationPassword);
    $exchangeServiceData = $ews_auth->AuthenticateAgainstEWSEndPoint( false );

    // Do we have an Auth Token for EWS?
    if( isset($exchangeServiceData) )
    {
        $ewsWrapper = new EWSWrapper();
        if( $type == 'appointment' )
        {
            if($isAllDayEvent=='true')
            {
                $startDateTime = $startDate . ' 00:00';
                $endDateTime = $endDate . ' 00:00';
            }
            else
            {
                $startDateTime = $startDate . ' ' . $startTime ;
                $endDateTime = $endDate . ' ' . $endTime;
            }
            echo $ewsWrapper->CreateAppointment($loggedInLiveId, $exchangeServiceData, $toArray, $subject, $body, $startDateTime, $endDateTime, $isAllDayEvent);
        }
        else
        {
            if(count($attachmentIdArray)>0)
            {
                $attachments = array();
                foreach($attachmentIdArray as $id)
                {
                	$id = clean_param($id, PARAM_INT);
                    $attachmentRecord = $DB->get_record_sql("SELECT r.course, r.name, r.reference
                                FROM {resource} r
                                WHERE r.id=$id");
                    $attachments[] = array('id'=>$id, 'course'=>$attachmentRecord->course,'name'=>extractFileNameFromReference($attachmentRecord->reference),'content'=>getBase64($attachmentRecord->reference,$attachmentRecord->course));
                }
                echo($ewsWrapper->SendMailWithAttachments($loggedInLiveId, $exchangeServiceData, $toArray, $subject, $body, $attachments));
            }
            else
            {
                if($action=="RE" && $itemId!=='' && $changeKey!=='' )
                {
                   echo $ewsWrapper->SendMailReply($loggedInLiveId, $exchangeServiceData, $itemId, $changeKey, $toArray, $subject, $body);
                }
                else
                {
		    if($action=="FWD" && $itemId!=='' && $changeKey!=='' )
                    {
                       echo $ewsWrapper->SendMailForward($loggedInLiveId, $exchangeServiceData, $itemId, $changeKey, $toArray, $subject, $body);
                    }
                    else
                    {
                        echo $ewsWrapper->SendMail($loggedInLiveId, $exchangeServiceData, $toArray, $subject, $body);
                    }
                }
            }
        }
    }
    else
    {
        echo getJsonResultString('-1','Exchange Service Data Not Set','true','Exchange Service data not set. Please contact your system administrator if this problem persists.');
    }
}
else
{
    echo getJsonResultString('-1','Exchange not Enabled','true','Exchange is not enabled for this Moodle Instance. Please contact your system administrator if this problem persists.');
}
/**
 * This method will extract the email address out of a resolved name format.
 * @param <string> $contact -   a contact formatted in EWS.php. The expected format is: Student One [student.one@domain.com]
 *                              if the input is not contained in square brackets, then the input is returned as output.
 * @return <string>
 */
function extractEmailAddress($contact)
{
    //if the email address is contained in square brackets
    if(strpos($contact,'[')!==FALSE && strpos($contact,']')!==FALSE)
    {
        $leftBracketPos = strpos($contact,'[');
        $rightBracketPos = strpos($contact,']');
        return substr($contact,$leftBracketPos + 1,($rightBracketPos - $leftBracketPos) -1);
    }
    else
    {
        return $contact;
    }
}
/**
 * extracts the file name from a relative path file reference
 * @param <string> $reference - a relative path file reference
 * @return <string> (file name)
 */
function extractFileNameFromReference($reference)
{
    while(strpos($reference,'\\')!==FALSE)
        {
            $reference = substr($reference,0,strpos($reference,'\\')+1);
        }
     return $reference;
}
/**
 * encodes the file contents of a file to base 64
 * @global <array> $CFG - The global configuration array
 * @param <string> $reference - a relative path file reference
 * @param <int> $course - a courseid
 * @return <string>
 */
function getBase64($reference, $course)
{
    global $CFG;
    $filepath = $CFG->dataroot.'/'.$course.'/'.$reference;
    $filecontents = file_get_contents($filepath,FILE_TEXT);
    if($filecontents!==null)
    {
        return base64_encode($filecontents);
    }
}

?>
