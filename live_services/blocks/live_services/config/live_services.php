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

defined('MOODLE_INTERNAL') or die('no direct script access');

global $CFG;
$identityScreenshot = "$CFG->wwwroot/blocks/live_services/services/identity/screenshot.png";

?>
<style type="text/css">
    #live_services_config
    {
        text-align: left;
        margin:0px 40px;
    }

    #live_services_config table
    {
        width:100%;
        padding:2px;
    }

    #live_services_config td
    {
        vertical-align: top;
        text-align: left;
        padding:2px 10px;
    }

    #live_services_config td.inputCaption
    {
        width:160px;
        text-align:left;
    }

    #live_services_config td.input
    {
        width:100%;
        padding-right: 20px;
    }

    #live_services_config td.screenshot
    {
        text-align:center;
        width:208px;
    }

    #live_services_config img.screenshot
    {
        margin-top:18px;
        display:block;
        border:1px solid #CCC;
    }

    #live_services_config span.imageCaption
    {
        font-size:0.7em;
    }


    #live_services_config hr
    {
        border: 1px dotted black;
        height: 1px;
        margin: 10px 0px;
    }

    input.small
    {
        width:20%;
    }

    input.medium
    {
        width:50%;
    }

    input.large
    {
        width:80%;
    }

    .disabled
    {
    	color: #999999;
    }

    .required
    {
    	color: #AA0000;
    }

    .live_service_setting
    {
        vertical-align: baseline;
    }

</style>
<div id="live_services_config">
    <table>
        <tr><td colspan="2"><strong>Microsoft&nbsp;Live Services Plug-in for Moodle Settings</strong><br/>These settings are required. They allow this Moodle to access the Windows Live&trade;&nbsp;Services APIs. Before your
                users can use any Microsoft Live Services Plug-in for Moodle Services from this Moodle, you need to
                <a href="https://lx.azure.microsoft.com/Cloud/Provisioning/Default.aspx">register</a> for an Application ID from Microsoft.
                This Application ID and Secret Key, along with your Moodle's domain name, create a Relying Party Suite (RPS). This
                allows Microsoft to ensure that users are logging into your Moodle site and allows your users to come back
                your site after successfully authenticating with their Windows Live ID credentials. Registration takes less
                than five minutes.</td>
            <td class="screenshot"><img class="screenshot" src="<?php echo $identityScreenshot ?>" title="Microsoft Live Services Plug-in for Moodle screenshot" />
                <span class="imageCaption">Figure 1: Microsoft Live Services Plug-in for Moodle block header</span>
            </td>
       </tr>
    </table>