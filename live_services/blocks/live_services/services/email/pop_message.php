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

    /* #region required files */
    require_once('../../../../config.php');
    require_once( $CFG->dirroot . '/lib/moodlelib.php' );
    require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
    require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
    require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
    /* #endregion required files */
    /* #region extract querystring parameters */
    $to = @optional_param('to', '', PARAM_TEXT);
    $courseId = @optional_param('id',1, PARAM_INT);
    $itemId = @optional_param('itemid', '', PARAM_TEXT);
    $changeKey = @optional_param('ck', '', PARAM_TEXT);
    $action = @optional_param('action', '', PARAM_TEXT);
    /* #endregion extract querystring parameters */
    /* #region setup page variables */
    $showFiles = isset($courseId) && $courseId > 1;
    $newMail = $itemId=='' || $changeKey=='' || $action=='';
    $messageLabel = getLocalizedString('emailMessage');
    $loggedInLiveId = @$_COOKIE['wls_liveId'];
    $errorMessage;
    if(is_null($loggedInLiveId))
    {
        $errorMessage = getLocalizedString('emailLiveIdMissingCookie');
    }
    $owaText = getLocalizedString('owaText');
    $attachmentsText = getLocalizedString('attachmentsText');
    $owaUrl = @$CFG->block_live_services_ewsServiceUrl;
    $pageTitle = getLocalizedString('popupQuickMessageTitle');
    $messageRows = 16;
    if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS)
    {
        $errorMessage = getLocalizedString('emailExchangeNotSetUp');
    }
    else
    {
        // Do we have an Auth Token for EWS?
        if( !$newMail )
        {
            // This username/password needs to be that of the Impersonation Account
            $impersonationLiveId = @$CFG->block_live_services_ewsServiceAccountUserName;
            $impersonationPassword = @$CFG->block_live_services_ewsServiceAccountPassword;
            $ews_auth = new EWSAuthentication($impersonationLiveId, $impersonationPassword);
            $exchangeServiceData = $ews_auth->AuthenticateAgainstEWSEndPoint( false );            
            
            if( isset($exchangeServiceData) )
            {
                $ewsWrapper = new EWSWrapper();
                $itemId = str_replace(" ","+",$itemId);
                $changeKey = str_replace(" ","+",$changeKey);
                $message = $ewsWrapper->GetEmailItem($loggedInLiveId, $exchangeServiceData, $itemId,$changeKey);
                switch($action)
                {
                    case "RE":
                        $messageLabel = getLocalizedString('emailReplyMessage');
                        if(strpos($message['subject'],'RE:')===false)
                        {
                            $message['subject'] = $action.': '.$message['subject'];
                            $pageTitle = $message['subject'];
                            $messageRows = 5;
                        }
                        $to = $message['from'];
                        $originalMessage = isset($message)? '<hr class="refwdSeparator"/>'.html_entity_decode($message['body']):'&nbsp;';
                        break;
                    case "FWD":
                        $messageLabel = getLocalizedString('emailForwardMessage');
                        if(strpos($message['subject'],'FWD:')===false)
                        {
                            $message['subject'] = $action.': '.$message['subject'];
                            $pageTitle = $message['subject'];
                            $messageRows = 5;
                            $originalMessage = isset($message)? '<hr class="refwdSeparator"/>'.html_entity_decode($message['body']):'&nbsp;';
                        }
                        break;
                }
            }
        }
    }
    //$owaText and $attachmentsText contain the replaceable string [[url]]
    $inboxLink = str_replace('[[url]]',$owaUrl,$owaText);
    $attachmentsLink = str_replace('[[url]]',$owaUrl,$attachmentsText);

    /* #endregion setup page variables */

    /* #region fill attachments table */
/**
 * renders a table cell, default is empty cell, center aligned, no class
 * @param <string> $alignment - table cell alignment
 * @param <string> $text - the text to add to the cell
 * @param <string> $class - classname for the cell
 * @return <string> (HTML)
 */
function render_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.'"';
    }
    return '<td align="'.$alignment.'" style="white-space:nowrap "'.$class.'>'.$text.'</td>';
}

/**
 * Renders a table of available attachments
 * @global <array> $CFG - global configuration array
 * @global <array> $USER - global user array
 * @global <array> $COURSE - global course array
 * @global <bool> $showFiles - global variable, true if the user can see files, false if the user can't see files
 * @param <int> $courseId - the id of the course the user is viewing
 * @return <string> (HTML table)
 */
function render_files_table($courseId)
{
    if(!$courseId || (int)$courseId < 2)
    {
        return '&nbsp;';
    }
    global $CFG;
    global $USER;
    global $COURSE;
    global $showFiles;
    $table = '<table border="0" cellpadding="0" cellspacing="0">';
    $resources = get_resources($courseId);
    if($resources!==FALSE && count($resources)>0)
    {
        if($showFiles===true)
        {
            foreach($resources as $resource)
            {
                $table.='<tr>'.render_cell('','<input type="checkbox" id="resource'.$resource->id.'" name="resource" style="border:none;" />','').render_cell('','<span id="resourcename'.$resource->id.'">'.$resource->name.'</span>','').'</tr>';
            }
        }
    }
    else
    {
        if($showFiles===true)
        {
            $table.='<tr><td colspan="2">'.getLocalizedString('emailAddAttachmentNoAttachmentsAvailable').'</td></tr>';
        }
    }
    $table .= '</table>';
    return $table;
}

/**
 * gets an array of available resources (attachments)
 * @global <array> $CFG - global configuration array
 * @param <int> $courseId - the id of the course being viewed
 * @return <array>
 */
function get_resources($courseId)
{
    global $CFG, $DB;
    return $DB->get_records_sql("SELECT r.id, r.name, r.reference
                            FROM {resource} r
                            WHERE r.course=$courseId AND r.type='file' AND r.reference NOT LIKE 'http%' 
                            ORDER BY r.name");
}
/* #endregion fill attachments table */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?php echo(getLocalizedString('popupQuickMessageTitle')); ?></title>   
    <!-- Source File -->
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/fonts/fonts-min.css" />
    <link type="text/css" rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    <!--CSS file (default YUI Sam Skin) -->
    <?php echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles.css">');
    ?>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yuiloader/yuiloader-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/event/event-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/datasource/datasource-min.js"></script>
    <!-- JSON Dependencies -->
    <script src="http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js"></script>
    <!-- JSON Source file -->
    <script type="text/javascript"  src="http://yui.yahooapis.com/2.7.0/build/json/json-min.js"></script>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/shared/popupdialogs.js"></script>') ?>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/pop_message.js"></script>') ?>
    <!-- Autocomplete Source file -->
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/autocomplete/autocomplete-min.js"></script>
    </head>
    <body class="yui-skin-sam" onload="initialize()">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader"><?php echo($pageTitle);?><img id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close_rest.gif'); ?>"  onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer" style="text-align:center;">
        <div id="popupBody" style="height:428px;">
        <form>
            <div id="overflowContainer">
            <table id="emailOptionsTable" class="bodyMainTable" style="width:710px">
                <tr>
                    <td width="100px" class="caption"><label><?php echo(getLocalizedString('emailTo'));?>:</label></td>
                    <td width="600px">
                    <div id="autoCompletePanel" style="z-index:1">
                        <input id="to" type="text" style="width:600px;" tabindex="1" value="<?php echo(isset($to)? $to:'');?>" title="<?php echo(getLocalizedString('emailToTooltip'));?>" />
                        <input type="hidden" id="resolvedNamesHidden" />
                        <div id="autoCompleteResults"></div>
                    </div>
                    </td>
                </tr>
                <tr><td class="caption"><label for="subject"><?php echo(getLocalizedString('emailSubject'));?>:</label></td>
                    <td><input id="subject" type="text" style="width:600px;" tabindex="2" value="<?php echo(isset($message)? $message['subject']:'');?>"/></td>
                </tr>
                <?php if($showFiles) {
                echo('
                <tr id="attachFileLinkRow"><td>&nbsp;</td><td><img src="clip.gif" border="0"/>&nbsp;<a class="actionLink" href="#" tabindex="3" onclick="javascript:showAttachments('.$courseId.');">'.getLocalizedString('emailAttachments').'</a>
                </td></tr>
                <tr id="attachmentsRow" style="">
                    <td>&nbsp;</td>
                    <td>                        
                            <div id="attachmentsContainer">'.render_files_table($courseId).'</div>
                    </td>
                </tr>');}?>
                <tr id="messageRow" style="">
                    <td width="100px" class="caption"><label for="body"><?php echo(getLocalizedString('emailMessage'));?>:</label></td>
                    <td style="vertical-align:top"><textarea id="body" style="width:600px;" rows="<?php echo($messageRows) ?>" tabindex="4" value=""></textarea>
                    </td>
                </tr>
                <?php if(!$newMail) {
                echo('
                <tr id="originalMessageRow">
                    <td colspan="2">
                        <div id="originalMessage">'.$originalMessage.'</div>
                    </td>
                </tr>');}?>
            </table>
            </div>
            <div id="buttons">
            <input id="sendButton" type="button" value="<?php echo(getLocalizedString('emailSend'));?>" onClick="javascript:makeRequest();" tabindex="5" />
            <input id="cancelButton" type="button" value="<?php echo(getLocalizedString('emailCancel'));?>" onblur="javascript:focusTo();" onClick="javascript:parentGrayOut(false, null);hideOverlay();" tabindex="6" />
            </div>
            <input id="action" type="hidden" value="<?php echo($action);?>"/>
            <input id="itemId" type="hidden" value="<?php echo($itemId);?>"/>
            <input id="changeKey" type="hidden" value="<?php echo(isset($changeKey)?$changeKey:'');?>"/>            
        </form>        
        </div>
        </div>
    </div>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/autocomplete.js"></script>') ?>
    </body>
</html>
