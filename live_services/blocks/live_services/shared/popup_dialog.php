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

require_once( $CFG->dirroot . '/config.php' );

/**
 * The PopupDialogService renders a lightbox container for sending new alerts, emails, and events
 * in addition to viewing emails and events.
 */
class PopupDialogService
{
    /**
     * Class constructor (currently not implemented)
     */
    function __construct()
    {
    }

    /**
     * Renders the HTML that creates a lightbox
     * A JavaScript function is created that displays the appropriate lightbox based on the ID of the
     * link that is clicked. Listeners are added for each link that is capable of displaying a lightbox
     * @global <array> $CFG - the global configuration array
     * @return <string> (HTML)
     */
    function Render()
    {
        global $CFG;
        // check to see if this is a course page        
        // default to site course (courseid=1)
        $courseId= optional_param('course',1, PARAM_INT);
        if($courseId==1)
        {
			$currentUrl=$_SERVER["REQUEST_URI"];
            $urlSearchText = '/course/';
            if(substr_count($currentUrl, $urlSearchText)>0)
            {
                $courseId= optional_param('id',1, PARAM_INT);
            }
        }
        
/*        
        echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_styles.css">');
        echo('<!--[if IE]><link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles_ie.css"><![endif]-->');
        echo <<<OVERLAY

	<div id="lightboxOverlay" style="position:absolute; visibility:hidden;z-index:500;">
		<iframe id="popupIFrame" src="" scrolling="no" frameborder="0"></iframe>
	        <div id="dropShadow" style="position:absolute;top:5px;left:3px;background-color:#000000;z-index:-1;opacity:0.25;moz-opacity:0.25;filter:alpha(opacity=25);">
		</div>
	</div>
OVERLAY;
*/

        return <<<POPUP_DIALOG
<div id='ms4mPopupDialog'>


    <link rel="stylesheet" type="text/css" href="$CFG->wwwroot/blocks/live_services/shared/popup_dialog_styles.css">
    <!--[if IE]><link rel="stylesheet" type="text/css" href="$CFG->wwwroot/blocks/live_services/shared/popup_dialog_div_styles_ie.css"><![endif]-->
    <div id="lightboxOverlay" style="position:absolute; visibility:hidden;z-index:500;">
        <iframe id="popupIFrame" src="" scrolling="no" frameborder="0"></iframe>
            <div id="dropShadow" style="position:absolute;top:5px;left:3px;background-color:#000000;z-index:-1;opacity:0.25;moz-opacity:0.25;filter:alpha(opacity=25);">
        </div>
    </div>


    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/yuiloader/yuiloader-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/dom/dom-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/event/event-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/container/container-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/element/element-beta-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.6.0/build/button/button-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/json/json-min.js"></script>
    <script type="text/javascript" src="$CFG->wwwroot/blocks/live_services/shared/popupdialogs.js"></script>
    <script type="text/javascript">

        var Event = YAHOO.util.Event,
            Dom = YAHOO.util.Dom,
            lightboxOverlay;

        var showAppointment = Dom.get("showAppointment");
        var showEmail = Dom.get("showEmail");
        var showAlert = Dom.get("showAlert");

    function showDialog(e)
    {
        var iframe = document.getElementById("popupIFrame");
        var dropShadow = document.getElementById("dropShadow");
        var source = this.id;
        var overlayWidth;
        var dropShadowWidth;
        switch(source)
        {
            case "showAlert":
            {
                iframe.src = "$CFG->wwwroot/blocks/live_services/services/alerts/pop_alert.php?courseid=$courseId";
                overlayWidth = "770px";
                overlayHeight = "365px";
                dropShadow.style.width = "770px";
                dropShadow.style.height = "363px";
                break;
            }
            case "showAppointment":
            {
                iframe.src = "$CFG->wwwroot/blocks/live_services/services/email/pop_event.php";
                overlayWidth = "770px";
                overlayHeight = "420px";
                dropShadow.style.width = "770px";
                dropShadow.style.height = "418px";
                break;
            }
            case "showEmail":
            {
                iframe.src = "$CFG->wwwroot/blocks/live_services/services/email/pop_message.php?id=$courseId";
                overlayWidth = "770px";
                overlayHeight = "480px";
                dropShadow.style.width = "770px";
                dropShadow.style.height = "478px";
                break;
            }
            default:
            {
                var itemId;
                var changeKey;
                var sourceString = new String(source);
                var sourceElement = document.getElementById(sourceString);
                var itemId = sourceElement.getAttribute("itemid");
                var changeKey = sourceElement.getAttribute("changekey");
                var index;
                if(source.indexOf("viewemail")>=0)
                {
                    index = parseInt(sourceString.substr(9));
                    iframe.src = "$CFG->wwwroot/blocks/live_services/services/email/pop_email_view.php?index=" + index + "&id=$courseId&itemid="+encodeURI(itemId)+"&ck="+encodeURI(changeKey);
                    overlayWidth = "770px";
                    overlayHeight = "480px";
                    dropShadow.style.width = "770px";
                    dropShadow.style.height = "478px";
                }
                else
                {
                    if(source.indexOf("viewevent")>=0)
                    {
                        index = parseInt(sourceString.substr(9));
                        iframe.src = "$CFG->wwwroot/blocks/live_services/services/email/pop_event_view.php?index=" + index + "&id=$courseId&itemid="+encodeURI(itemId)+"&ck="+encodeURI(changeKey);
                        overlayWidth = "770px";
                        overlayHeight = "480px";
                        dropShadow.style.width = "770px";
                        dropShadow.style.height = "478px";
                    }
                }
                break;
            }
        }
    lightboxOverlay = new YAHOO.widget.Overlay( "lightboxOverlay", { fixedcenter:true, visible:false, width:overlayWidth, height:overlayHeight } );
    lightboxOverlay.className = "modalForeground";
    lightboxOverlay.render(document.body);
    grayOut(true,null);
    lightboxOverlay.show();
    }

    function addListeners()
    {
        if(showAppointment)
            {Event.addListener(showAppointment.id,"click",showDialog);}
        if(showEmail)
            {Event.addListener(showEmail.id,"click",showDialog);}
        if(showAlert)
            {Event.addListener(showAlert.id,"click",showDialog);}
        var anchorTags = document.getElementsByTagName("a");
        //loop through all anchor tags and add listeners based on the id
        for(i=0;i<anchorTags.length;i++)
        {
            if(anchorTags[i].id.indexOf("viewemail")>=0)
            {
                Event.addListener(anchorTags[i].id,"click",showDialog);
            }
            if(anchorTags[i].id.indexOf("viewevent")>=0)
            {
                Event.addListener(anchorTags[i].id,"click",showDialog);
            }
        }
    }
    YAHOO.util.Event.onDOMReady(addListeners);
    </script>
</div>
POPUP_DIALOG;
    }
}
?>
