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

header("Content-type: text/css");
require_once('../../../config.php');
?>

h3.msm_h3
{
    font-size:0.75em;
    
    border-top:1px solid #C9C9C9;
    border-bottom:1px solid #C9C9C9;
    /*
    color:#333333;
    background-image: url( <?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/h3bg.png );
    */
    background-color:#DFF2FE;
    cursor:hand;
    min-height:32px;
    height:32px;
    margin-bottom:8px;

}

h3.msm_h3:hover
{
    /*
    background-image: url( <?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/h3bg_mouseover.png );
    border-left:1px solid #C1E4FF;
    border-right:1px solid #72B4FF;
    */
    cursor:pointer;

}

h3.msm_h3 table
{
    width:100%;
}

h3.msm_h3 td
{
    vertical-align:middle;
}

h3.msm_h3 td.logo
{
    width:27px;
    padding-left:4px;
}

h3.msm_h3 td.caption
{

}

h3.msm_h3 td.expandCollapse
{
    width:10px;
    padding-right:4px;
}

h4.msm_h4
{
    font-size:0.825em;
    color:#000000;
    font-weight:bold;
    border:none;    
    cursor:hand;
    width:100%;
}
h4.msm_h4 img
{
    display:inline;
}



.live_services_clear
{
    clear: both;
}

.live_services_wrapper_frame div.content
{
    background-image: url( <?php echo($CFG->wwwroot);?>/blocks/live_services/services/identity/background.png );
    background-position: top right;
    background-repeat: no-repeat;
    height:1%;
}

.live_services_wrapper_div
{
    margin: 0px;
    padding: 2px 1px 3px 1px;
    border: none;
}

.live_service_div
{
    padding-top:2px;
    padding-bottom: 6px;
    border-top: 1px dotted #666666;
    min-height:28px;
}

.live_service_div_short
{
    padding: 2px 2px 2px 2px;
    min-height:20px;
}

.live_service_header
{
    border: none;
    height:42px;
}

.live_service_icon
{
    float: left;
/*    width: 40px; */
    margin: 0px 3px 0px 0px;
    padding: 0px;
}

.live_service_text, .live_service_identity
{
    font-size: 8pt;
    line-height: 11pt;
    margin: 0px;
    padding: 6px 0px 0px 0px;
}

.live_service_icon_height
{
    min-height:42px;
    margin-top:5px;
    margin-bottom:0;
}

.live_service_list, .live_service_list_email, .live_service_list_calendar
{
    font-size: 8pt;
    line-height: 12pt;
    margin: 0px;
    padding: 0px 0px 0px 6px;
    list-style-type: none;
}

.live_service_list_emailLogo, .live_service_list_calendarLogo
{
    font-size: 8pt;
    line-height: 12pt;
    margin: 0px;
    padding: 0px 0px 0px 0px;
    list-style-type: none;
}

.live_service_identity
{
    padding: 0px 0px 6px 0px;
}

.live_service_list_email li
{
    padding-left: 18px;
    background-image: url( <?php echo($CFG->wwwroot);?>/blocks/live_services/services/email/message.png );
    background-repeat: no-repeat;
    background-position: top left;
}

.live_service_list_calendar li
{
    padding-left: 20px;
    background-image: url( <?php echo($CFG->wwwroot);?>/blocks/live_services/services/email/event.png );
    background-repeat: no-repeat;
    background-position: top left;
    border: 1px solid #CCCCCC;
    margin-bottom: 3px;
}

#searchServiceContainer
{
    min-height:38px;
    padding-bottom:4px;
}

#owaLinksContainer
{
    font-size:0.75em;
    padding-left:4px;
    min-height:20px;
    line-height: 1.0em;
}

#owaLinksContainer #loadingContacts
{
    color:#999;
}






