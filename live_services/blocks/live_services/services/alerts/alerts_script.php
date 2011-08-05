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
?>

/*
initialization function called when the body is loaded
*/
function initialize() {
    attachKeyHandler();
    YAHOO.util.Dom.get('alertSubject').focus();
}

/*
implements hover mouseover for the "X" button in the upper right corner
*/
function closeButtonMouseOver(img) {
    img.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/close_hover.gif";
}

/*
implements hover mouseout for the "X" button in the upper right corner
*/
function closeButtonMouseOut(img) {
    img.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/close_rest.gif";
}

/*
expands or collapses the Alerts block and toggles the image from + to -
@param imageId - the id of the image to toggle
@param containerId - the id of the container div
returns void
*/
function toggleExpandCollapse(imageId, containerId) {
    var container = document.getElementById(containerId);
    var image = document.getElementById(imageId);
    if(container.style.display=="block") {
        image.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/switch_plus.gif";
        container.style.display = "none";
    }
    else {
        image.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/switch_minus.gif";
        container.style.display = "block";
    }
}

/*
callback handler for successful AJAX call
@param response - the response object returned by the AJAX call
returns void
*/
var handleSuccess = function( response ) {
    var result = YAHOO.lang.JSON.parse(response.responseText);
    if(result.error=='true') {
        YAHOO.util.Dom.get('sendButton').disabled = false;
        alert(result.reason + "\r\n" + result.exceptionmessage);
    }
    else {
        parentGrayOut(false,null);
        hideOverlay();
    }
};

/*
callback handler for failed AJAX call
@param response - the response object returned by the AJAX call
returns void
*/
var handleFailure = function( response ) {
    var messageText;
    if( response.responseText !== undefined ) {
        messageText = new String();
        messageText = "Email message not sent successfully \r\n";
        messageText += "HTTP status: " + response.status + "\r\n";
        messageText += "Status code message: " + response.statusText;
    }
    else {
        messageText = "Alert could not be sent due to unknown failure";
    }
    YAHOO.util.Dom.get('sendButton').disabled = false;
    alert(messageText);
};

var callback = {
    success:handleSuccess,
    failure:handleFailure,
    argument:[]
};

/*
makes AJAX call to send an alert
returns void
*/
function makeRequest() {
    YAHOO.util.Dom.get('sendButton').disabled = true;
    var sUrl = "send_alert.php";
    var alertSubject = document.getElementById( 'alertSubject' );
    var alertBody = document.getElementById( 'alertBody' );
    var courseId = document.getElementById('courseId');
    var postData = "alertSubject=" + encodeURI(alertSubject.value) +
                "&alertBody=" + encodeURI(alertBody.value) +
                "&courseId=" + courseId.value;
    YAHOO.util.Connect.asyncRequest( 'POST', sUrl, callback, postData );
}


/*
attaches function calls to keystrokes 13(Enter) and 27(ESC)
returns void
*/
function attachKeyHandler() {
    document.onkeypress = function(e){
        if(window.event) e = window.event;
        keyCode = e.keyCode?e.keyCode:e.which;
        switch(keyCode) {
            case 13: {
                makeRequest();
            }
            case 27: {
                parentGrayOut(false,null);hideOverlay();
                break;
            }
        }
    }
}
