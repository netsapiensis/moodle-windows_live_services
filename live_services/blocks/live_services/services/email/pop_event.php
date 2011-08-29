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
    require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
    $owaText = getLocalizedString('owaText');
    $owaUrl = @$CFG->block_live_services_ewsServiceUrl;
    if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS)
    {
        die(getLocalizedString('emailExchangeNotSetUp'));
    }
    $inboxLink = str_replace('[[url]]',$owaUrl,$owaText);
 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">    
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/calendar/assets/skins/sam/calendar.css" />
    <!--CSS file (default YUI Sam Skin) -->
    <link type="text/css" rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    <?php         echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles.css">'); ?>
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
    <!-- Autocomplete Source file -->
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/autocomplete/autocomplete-min.js"></script>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/shared/popupdialogs.js"></script>') ?>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/pop_event.js"></script>') ?>
</head>
<body class="yui-skin-sam" onload="initialize()">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader"><?php echo(getLocalizedString('popupQuickEventTitle'));?><img id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close_rest.gif'); ?>"  onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer" style="text-align:center">
        <div id="popupBody" style="text-align:left">
        <form id="popupForm">
            <div id="overflowEventContainer">
            <table id="eventOptionsTable" class="bodyMainTable">
                <tr id="eventInviteeRow"><td width="130px" class="caption"><label for="to"><?php echo(getLocalizedString('emailTo'));?>:</label></td>
                    <td style="width:576px" colspan="2"><div id="autoCompletePanel">
                            <input id="to" type="text" style="width:568px;" tabindex="1" value="<?php echo(isset($_COOKIE['wls_liveId'])?$_COOKIE['wls_liveId']:'');?>"  title="<?php echo(getLocalizedString('emailToTooltip'));?>"  />
                            <input type="hidden" id="resolvedNamesHidden" />
                            <div id="autoCompleteResults"></div>
                        </div>
                    </td></tr>
                <tr><td class="caption"><label for="subject"><?php echo(getLocalizedString('emailSubject'));?>:</label></td><td><input id="subject"  class="input" type="text" style="width:360px;" tabindex="2"/></td>
                <td rowspan="7"><div id="cal1Container"></div></td>
                </tr>
                <tr><td class="caption"><label for="body"><?php echo(getLocalizedString('emailBody'));?>:</label></td><td><textarea id="body" class="input" style="width:360px;" tabindex="3"></textarea></td></tr>
                <tr><td class="caption"><label for="startDate"><?php echo(getLocalizedString('calendarStartDate'));?>:</label></td><td><input id="startDate" class="smallInput" tabindex="4" size="10" type="text" onclick="dateControl=this;" /></td></tr>
                <tr><td class="caption"><label for="startTime"><?php echo(getLocalizedString('calendarStartTime'));?>:</label></td><td>
                    <select id="startTime" class="smallInput" tabindex="5">
                    <option value="-1">--<?php echo(getLocalizedString('calendarSelect'));?>--</option>
                        <?php include($CFG->dirroot.'/blocks/live_services/shared/time_options.php'); ?>
                    </select>
                </td></tr>
                <tr><td class="caption"><label for="endDate"><?php echo(getLocalizedString('calendarEndDate'));?>:</label></td><td><input id="endDate" size="10" type="text" tabindex="6" class="smallInput" onclick="dateControl=this;" value="<?php echo('');?>"/></td></tr>
                <tr><td class="caption"><label for="endTime"><?php echo(getLocalizedString('calendarEndTime'));?>:</label></td><td>
                    <select id="endTime" class="smallInput" tabindex="7">
                    <option value="-1">--<?php echo(getLocalizedString('calendarSelect'));?>--</option>
                        <?php include($CFG->dirroot.'/blocks/live_services/shared/time_options.php'); ?>
                    </select>
                </td></tr>
                <tr><td class="caption"><label for="isAllDayEvent"><?php echo(getLocalizedString('calendarAllDayEvent'));?>:</label></td><td><input id="isAllDayEvent" type="checkbox" tabindex="8" onclick="makeAllDayEvent();" style="border:none" /></td></tr>
                <tr id="eventFooterRow">
                    <td colspan="2" id="optionsMessage"><?php echo(getLocalizedString('calendarNotEnoughOptions').' '.$inboxLink);?></td>
                    <td id="send"><input id="sendButton" type="button" tabindex="9" value="<?php echo(getLocalizedString('calendarSend'));?>" onClick="makeRequest();"/>
                    <input id="cancelButton" type="button" value="<?php echo(getLocalizedString('calendarCancel'));?>" onblur="javascript:focusTo();" onClick="javascript:parentGrayOut(false, null);hideOverlay();" tabindex="10" />
                    </td>
                </tr>
            </table>
            </div>
        </form>
    </div>    
    </div>
    </div>
    <?php echo('<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/autocomplete.js"></script>') ?>
</body>
</html>