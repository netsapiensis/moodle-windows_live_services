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

require_once('../../../../config.php');
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

$itemId=@optional_param('itemid','',PARAM_TEXT);
$changeKey=@optional_param('ck','',PARAM_TEXT);
// Get the LiveId of the logged in user
$loggedInLiveId = @$_COOKIE['wls_liveId'];
if($loggedInLiveId==null)
{
    die(getLocalizedString('emailLiveIdMissingCookie'));
}
$owaText = getLocalizedString('owaText');
$owaUrl = @$CFG->block_live_services_ewsServiceUrl;
if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS)
{
    die(getLocalizedString('emailExchangeNotSetUp'));
}
$inboxLink = str_replace('[[url]]',$owaUrl,$owaText);

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
        $itemId = str_replace(" ","+",$itemId);
        $changeKey = str_replace(" ","+",$changeKey);
        $calendarEvent = $ewsWrapper->GetCalendarItem($loggedInLiveId, $exchangeServiceData, $itemId, $changeKey);
    }
    $exchangeEnabled = true;
}
else
{
    $exchangeEnabled = false;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">

    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/fonts/fonts-min.css" />
    <?php         echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles.css">'); ?>

    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yuiloader/yuiloader-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/event/event-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/datasource/datasource-min.js"></script>
    <!-- JSON Dependencies -->
    <script src="http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js"></script>
    <!-- JSON Source file -->
    <script src="http://yui.yahooapis.com/2.7.0/build/json/json-min.js"></script>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/shared/popupdialogs.js"></script>') ?>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/pop_event_view.js"></script>') ?>
</head>
<body class="yui-skin-sam" onload="javascript:initialize();">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader">Event Details<img id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close_rest.gif'); ?>"  onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer">
        <div id="popupBody" style="height:418px;">
        <form id="popupForm">
        <div id="overflowEventViewContainer">
            <?php
            if(!is_array($calendarEvent))
            {
                $result = json_decode($calendarEvent);
                die($result->reason.'<br/><br/>'.$result->exceptionmessage);
            }
            if(!$exchangeEnabled)
            {
                die(getLocalizedString('emailExchangeNotSetUp'));
            }
            ?>
            <table class="bodyMainTable">
                    <tr><td>Subject:</td><td width="550px"><strong><?php echo(strlen(stripslashes($calendarEvent['Subject']))==0?getLocalizedString('calendarUntitled'):stripslashes($calendarEvent['Subject'])); ?></strong></td></tr>
                    <tr><td>Location:</td><td><?php echo($calendarEvent['Location']); ?></td></tr>
                    <?php
                        if($calendarEvent['IsAllDayEvent']=='false')
                        {
                            echo('<tr><td>Start Time:</td><td>'.$calendarEvent["Start"].'</td></tr><tr><td>End Time:</td><td>'.$calendarEvent["End"]);
                        }
                        if($calendarEvent['IsAllDayEvent']=='true')
                        {
                            echo('<tr><td colspan="2">'.getLocalizedString('calendarAllDayEventMessage').'</td></tr>');
                        }
                    ?>
                    <tr><td>Recurring Meeting?:</td><td><?php echo($calendarEvent['IsRecurring']=='true'?'Yes':'No'); ?></td></tr>
                    <tr><td colspan="2" class="eventBody"><div class="overflow"><?php echo(stripslashes($calendarEvent['Body']))?></div></td></tr>
                </table>
                </div>
        </form>
        <div id="eventViewFooter">
            <?php echo($inboxLink);?>
        </div>
        </div>
        </div>
    </div>
</body>
</html>

