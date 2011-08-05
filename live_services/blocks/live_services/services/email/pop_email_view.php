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
    require_once( $CFG->dirroot . '/lib/moodlelib.php' );

    $courseId = @optional_param('id', 1, PARAM_INT);
    $index = @optional_param('index', 0, PARAM_INT);
?>
<?php

require_once( '../../../../config.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );


// Get the LiveId of the logged in user
$loggedInLiveId = $_COOKIE['wls_liveId'];
$owaText = getLocalizedString('owaText');
$attachmentsText = getLocalizedString('attachmentsText');
$owaUrl = @$CFG->block_live_services_ewsServiceUrl;
if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS)
{
    die(getLocalizedString('emailExchangeNotSetUp'));
}
$inboxLink = str_replace('[[url]]',$owaUrl,$owaText);
$attachmentsLink = str_replace('[[url]]',$owaUrl,$attachmentsText);

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
        $emailCount = 0;
        $currentIndex = (int)$index;
        $current = getEmailIdentifiers($currentIndex);
        $message = $ewsWrapper->GetEmailItem($loggedInLiveId, $exchangeServiceData,$current["itemId"], $current["changeKey"]);
        $prevIndex = (int)$index - 1;
        $prev = getEmailIdentifiers($prevIndex);
        $nextIndex = (int)$index + 1;
        $next = getEmailIdentifiers($nextIndex);
        $unreadMessage = getLocalizedString('emailUnreadMessage');
        if(!is_null($unreadMessage)&& (int)$index + 1 > 0 && (int)$emailCount > 0)
        {
            $unreadMessage = str_replace("[[m]]",(int)$index + 1,$unreadMessage);
            $unreadMessage = str_replace("[[n]]",$emailCount,$unreadMessage);
        }
    }
    $exchangeEnabled = true;
}
else
{
    $exchangeEnabled = false;
}
/**
 * Gets an email identifier (id) for the given key
 * @global <int> $emailCount - the count of emails in the array
 * @param <string> $key - the key for the email to retrieve the identifier for
 * @return <string> - an email id
 */
function getEmailIdentifiers($key)
{
    //itemIds are stored in session so we can easily access them
    $emailItemIdArray = @$_SESSION['emailItemIdArray'];    
    if($emailItemIdArray==null)
    {
        return null;
    }
    if($key < 0)
    {
        return null;
    }
    global $emailCount;
    $emailCount = count($emailItemIdArray);
    if($key >= $emailCount)
    {
        return null;
    }    
    $emailItem = str_replace(' ','+',json_decode($emailItemIdArray[$key],TRUE));
    return $emailItem;
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
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/pop_email_view.js"></script>') ?>
</head>
<body class="yui-skin-sam" onload="javascript:initialize();">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader"><?php echo(strlen(stripslashes($message['subject']))==0?getLocalizedString('emailUntitled'):htmlspecialchars(stripslashes($message['subject']))); ?><img id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close_rest.gif'); ?>"  onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer" style="text-align:center;">
        <div id="popupBody" style="height:420px;">
        <form id="popupForm">
            <?php
            if(!is_array($message))
            {
                $result = json_decode($message);
                die($result->reason.'<br/><br/>'.$result->exceptionmessage);
            }
            if(!$exchangeEnabled)
            {
                die('Microsoft Exchange is not enabled for this Moodle instance. Please contact your administrator');
            }
            ?>
            <table class="bodyMainTable">
                    <tr><td class="caption messageHeader"><?php echo(getLocalizedString('emailFrom'));?>:</td><td class="messageHeaderValue"><strong><?php echo($message['from']);?></strong></td></tr>
                    <tr><td class="caption messageHeader"><?php echo(getLocalizedString('emailSent'));?>:</td><td class="messageHeaderValue"><?php echo($message['sent']);?></td></tr>
                    <?php if(count($message['attachments'])>0)
                        echo('<tr><td></td><td>'.$attachmentsLink.'</td></tr>');
                    ?>
                    <tr><td colspan="2" class="messageBody"><div class="overflow" id="emailBody"><?php echo(stripslashes($message['body']))?></div></td></tr>
                <tr class="messageFooterRow">
                <td style="text-align:left;" colspan="2">
                <div id="actions">
                <a id="replyLink" class="actionLink" href="pop_message.php?id=<?php echo($courseId)?>&itemid=<?php echo(URLEncode($current["itemId"]));?>&ck=<?php echo(URLEncode($current["changeKey"])); ?>&action=RE" target="_self"><img src="reply.gif"/>&nbsp;Reply</a>&nbsp;
                <span class="verticalSeparator">|</span>&nbsp;<a id="forwardLink" class="actionLink" href="pop_message.php?id=<?php echo($courseId)?>&itemid=<?php echo(URLEncode($current["itemId"]));?>&ck=<?php echo(URLEncode($current["changeKey"])); ?>&action=FWD" target="_self"><img src="forward.gif"/>&nbsp;Forward</a>&nbsp;
                <?php if($prev !==null || $next !==null)
                {

                    if($prev !==null)
                    {
                        echo('<span class="verticalSeparator">|</span>&nbsp;<a id="prevEmail" class="actionLink" href="pop_email_view.php?index='.$prevIndex.'&id='.$courseId.'&itemid='.$prev["itemId"].'&ck='.$prev["changeKey"].'" target="_self"><img src="prev.gif" title="previous message"/></a>&nbsp;');
                    }
                    if($next !==null)
                    {
                        echo('<span class="verticalSeparator">|</span>&nbsp;<a id="nextEmail" class="actionLink" href="pop_email_view.php?index='.$nextIndex.'&id='.$courseId.'&itemid='.$next["itemId"].'&ck='.$next["changeKey"].'" target="_self"><img src="next.gif" title="next message"/></a>');
                    }

                }
                ?>
                </div>
                <div id="messageCount">
                    <?php echo($emailCount > 0?$unreadMessage:'&nbsp;'); ?>
                </div>
                <div id="inboxLink">
                    <?php echo($inboxLink);?>
                </div>
                </td>
                </tr>
            </table>
        </form>
        </div>        
        </div>
    </div>
</body>
</html>
