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

global $CFG;
$emailScreenshot = "$CFG->wwwroot/blocks/live_services/services/email/screenshot_outlook.png";

?>

<table>
    <tr><td colspan="3"><hr /></td></tr>
    <tr>
        <td colspan="2" style="vertical-align:middle">
            <strong>Email&nbsp;and&nbsp;Calendar</strong><br />
            Give users access to their online email and calendar using Microsoft Office Outlook&reg;&nbsp;Web Access (OWA). The Outlook block allows the user to create a new email or calendar event without leaving the current screen. If more customized options are needed, or if the user wants to see all of the emails or calendar events, the full online application can be launched in a new browser window by clicking the Inbox link.
            <br/><br/>
            If you choose not to use Outlook live, the Email and Calendar block will display links to Hotmail&reg;. To disable all email and calendar options, uncheck all checkboxes in this section.
        </td>
        <td class="screenshot" rowspan="2">
            <img class="screenshot" src="<?php echo $emailScreenshot ?>" title="Email Screenshot" />
            <span class="imageCaption">Figure 2: Outlook block</span>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            