
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

/*
 * initialization function called when the body loads
 * @return void
 */
function initialize()
{
    attachKeyHandler();
    setDisplayDefaults();
    YAHOO.util.Dom.get('to').focus();
}

/*
 * attaches handler for keystroke 27 (ESC)
 * @return void
 */
function attachKeyHandler()
{
document.onkeypress = function(e){
    if(window.event) e = window.event;
    keyCode = e.keyCode?e.keyCode:e.which;
    switch(keyCode)
    {
        case 27:
            {
                parentGrayOut(false,null);hideOverlay();
                break;
            }
        }
    }
}

/*
 * sets display defaults. hides attachments and original message (forward and reply)
 * @return void
 */
function setDisplayDefaults()
{
    YAHOO.util.Dom.get('messageRow').style.display='';
    if(YAHOO.util.Dom.get('attachmentsRow'))
    {
        YAHOO.util.Dom.get('attachmentsRow').style.display='none';
    }
    if(YAHOO.util.Dom.get('originalMessageSection'))
    {
        YAHOO.util.Dom.get('originalMessageSection').style.display='';
    }
}

var validationMessage;

/**
 * success handler for AJAX call
 */
var handleSuccess = function( response )
{
    var result = YAHOO.lang.JSON.parse(response.responseText);
    if(result.error=='true')
    {
        alert(result.reason + "\n" + result.exceptionmessage);
        YAHOO.util.Dom.get('to').focus();
        YAHOO.util.Dom.get('sendButton').disabled = false;
        return;
    }
    parentGrayOut(false,null);
    hideOverlay();
};

/**
 * failure handler for AJAX call
 */
var handleFailure = function( response )
{
    var messageText;
    if( response.responseText !== undefined )
    {
        messageText = new String();
        messageText = "Email message not sent successfully \n";
        messageText += "HTTP status: " + response.status + "\n";
        messageText += "Status code message: " + response.statusText;

    }
    else
    {
        messageText = "Message could not be sent due to unknown failure";
    }
   alert(messageText);
   YAHOO.util.Dom.get('sendButton').disabled = false;
};

var callback =
{
    success:handleSuccess,
    failure:handleFailure,
    argument:[]
};

/**
 * trims spaces from the beginning and end of a string
 * @return string
 */
function trim(val) {
    var stringToTrim = new String(val);
    return stringToTrim.replace(/^\s+|\s+$/g,"");
}

/**
 * UI control validation
 * @return bool (true if valid, false if invalid)
 */
function validateRequest()
{
    validationMessage = "";
    var to = new String(document.getElementById('to').value);
    if(trim(to).length==0)
        {
            validationMessage += "The 'To' field cannot be empty\n";
        }
    if(validationMessage.length > 0)
        {
            return false;
        }
    return true;
}

/**
 * makes an AJAX call to send_ews_item.php
 * @return void
 */
function makeRequest()
{
    if(validateRequest()==false)
    {
        alert(validationMessage);
        return false;
    }
    YAHOO.util.Dom.get('sendButton').disabled = true;
    var sUrl = 'send_ews_item.php';
    var to = YAHOO.util.Dom.get( 'to' ).value;
    var subject = YAHOO.util.Dom.get( 'subject' ).value;
    var body = YAHOO.util.Dom.get( 'body' ).value;
    var itemId = YAHOO.util.Dom.get('itemId').value;
    var changeKey = YAHOO.util.Dom.get('changeKey').value;
    var action = YAHOO.util.Dom.get('action').value;
    var postData = "type=email&to=" +  encodeURI(to) +
                    "&subject=" + encodeURIComponent(subject) +
                    "&body=" + encodeURIComponent(body) +
                    "&action=" + action + 
                    "&itemId=" + encodeURI(itemId) +
                    "&changeKey=" + encodeURI(changeKey);
    var resources = document.getElementsByName('resource');
    var attachmentIndex = 1;
    for(var i=0;i < resources.length;i++)
        {
            if(resources[i].checked)
            {
                postData += "&attachment" + attachmentIndex.toString() + "=" + encodeURIComponent(resources[i].id);
                attachmentIndex += 1;
            }
        }
    YAHOO.util.Connect.asyncRequest( 'POST', sUrl, callback, postData );
}
/**
 * shows the available attachments for the given course
 * @param <int> courseId - the id of the course being viewed
 * @return void
 */
function showAttachments(courseId)
{
    if(courseId > 1)
    {
        YAHOO.util.Dom.get('attachmentsRow').style.display='';
        YAHOO.util.Dom.get('body').rows = 9;
    }
}
/*
 * evaluates checkboxes in the list of attachments to see which ones are checked.
 * @return void
 */
function handleAttachments()
{
    var attachments = new String();
    var resources = document.getElementsByName('resource');
    for(var i=0;i < resources.length;i++)
    {
        if(resources[i].checked)
        {
            var resourceId = resources[i].id.replace("resource","");
            attachments = attachments + document.getElementById("resourcename"+resourceId).innerHTML + ", ";
        }
    }
    if(attachments.length > 1)
    {
        attachments = attachments.substring(0, attachments.length - 2);
    }
    var attachmentsList = YAHOO.util.Dom.get('attachmentsList');
    attachmentsList.innerHTML = attachments;
    YAHOO.util.Dom.get('attachmentsRow').style.display='none';

}
/*
 * sets the page focus to the "To" control
 * @return void
 */
function focusTo()
{
    YAHOO.util.Dom.get('to').focus();
}
