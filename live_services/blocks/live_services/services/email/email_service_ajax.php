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

header("Content-type: text/javascript"); ?>
<?php require_once('../../../../config.php');
GLOBAL $CFG;
?>
/* we want the first refresh to happen soon after the page has loaded */
var intervalId = -1;
setTimeout("refreshOutlookItems()",500);
/*
 * checks for new emails and events when OWA is enabled and configured
*/
function refreshOutlookItems()
{
    var xmlHttp;
    try
    {
        // Firefox, Opera 8.0+, Safari
        xmlHttp = new XMLHttpRequest();
    }
    catch (e)
    {  // Internet Explorer
       try
       {
            xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
       }
        catch (e)
        {
            try
            {
                xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (e)
            {
                return false;
            }
        }
    }
    xmlHttp.onreadystatechange = function()
    {
        if(xmlHttp.readyState==4) //request complete
        {
            //refresh container is the div that contains the emails and events
            var refreshContainer = document.getElementById("refreshContainer");
            if(refreshContainer!==null)
                {
                refreshContainer.innerHTML = xmlHttp.responseText;
                addListeners();//defined in popup_dialog.php, we need to rehook these after refresh
                }

        }
    }
    xmlHttp.open("GET","<?php echo($CFG->wwwroot)?>/blocks/live_services/services/email/email_service_refresh.php",true);
    xmlHttp.send(null);

    /* we want once the intial load of e-mail and calendar items is done via the setTimeout 
       we then use a setInterval() to poll for new e-mail and calendar items every 30 seconds */
    if( intervalId == -1) {
       intervalId = setInterval("refreshOutlookItems()",30000);
    }    
    
}
var handleResponse = function(o)
{
	if(document.getElementById("loadingContacts")!==null)
	{
	    document.getElementById("loadingContacts").style.display = "none";
	}
	if(document.getElementById("quickMessageSection")!==null)
	{
	    document.getElementById("quickMessageSection").style.display = "inline";
	}
	if(document.getElementById("quickEventSection")!==null)
	{
	    document.getElementById("quickEventSection").style.display = "inline";
	}
};
var callback = {success:handleResponse, failure:handleResponse,argument:null,timeout:30000};

