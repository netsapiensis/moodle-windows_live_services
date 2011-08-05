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
    require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
    require_once($CFG->dirroot.'/blocks/live_services/services/alerts/alerts_helper.php');
    if(!isset($_GET['courseid'])){
        die('This page cannot be accessed without a valid course ID');
    }
    else {
        $courseId = $_GET['courseid'];
        $groupDescription = AlertsHelper::GetGroupDescriptionByCourseId( $courseId );
        $sendAlertMessage = str_replace('[[groupDescription]]',$groupDescription,getLocalizedString('alertsSendAlertMessage'));
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/fonts/fonts-min.css" />
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/calendar/calendar-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yuiloader/yuiloader-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/event/event-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/datasource/datasource-min.js"></script>
    <!-- JSON Dependencies -->
    <script src="http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js"></script>
    <!-- JSON Source file -->
    <script src="http://yui.yahooapis.com/2.7.0/build/json/json-min.js"></script>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/shared/popupdialogs.js"></script>') ?>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/alerts/alerts_script.php"></script>') ?>
    <?php echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles.css">'); ?>
</head>
<body onload="initialize();">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader"><?php echo(getLocalizedString('popupSendAlertTitle'));?><img title="close" id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close_rest.gif'); ?>" onmouseover="javascript:closeButtonMouseOver(this);" onmouseout="javascript:closeButtonMouseOut(this);" onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer">
        <div id="popupBody">
            <form>                
                <table id="alertsTable" class="bodyMainTable" width="720px">
                        <tr id="sendAlertMessageRow"><td colspan="2"><?php echo($sendAlertMessage);?></td></tr>
                        <tr><td class="caption"><label for="alertSubject"><?php echo(getLocalizedString('alertsSubject'));?>:</label></td><td><input id="alertSubject" type="text" class="input" style="width:640px" /></td></tr>
                        <tr><td class="caption"><label for="alertBody"><?php echo(getLocalizedString('alertsMessage'));?>:</label></td><td class="input"><textarea id="alertBody" class="input" style="min-height:80px;height:160px;width:640px"></textarea></td></tr>
                        <tr id="sendAlertActionsRow"><td>&nbsp;</td>
                            <td id="send">
                            <input type="button" id="sendButton" value="<?php echo(getLocalizedString('alertsSendButtonText'));?>" onClick="javascript:makeRequest();"/>
                            <input id="cancelButton" type="button" value="<?php echo(getLocalizedString('emailCancel'));?>" onClick="javascript:parentGrayOut(false, null);hideOverlay();" tabindex="6" />
                            </td>
                       </tr>
                </table>
                <input id="courseId" type="hidden" value="<?php echo($courseId); ?>" />
            </form>
        </div>
        </div>
    </div>
    </body>
</html>