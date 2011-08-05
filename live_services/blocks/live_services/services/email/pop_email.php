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
    require_once( $CFG->dirroot . '/lib/moodlelib.php' );
    require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
    require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
    require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
    //don't need this require_once( $CFG->dirroot . '/blocks/live_services/services/email/config.php' );
    require($CFG->libdir.'/filelib.php');
    require($CFG->libdir.'/adminlib.php');
    
    global $DB;

    $courseid = @optional_param('id', 1, PARAM_INT);
    $invitee = @optional_param('invitee', '', PARAM_TEXT);
    $itemId = @optional_param('itemid', '', PARAM_TEXT);
    // 'ct' BUG?  
    $changeKey = @optional_param('ct', '', PARAM_TEXT);
    $action = @optional_param('action', '', PARAM_TEXT);
    if( $courseid > 1 )
    {
        $showFiles = true;
    }
    else
    {
        $showFiles = false;
    }
    if($itemId!=='' && $changeKey!=='' && $action!=='')
    {

       $newMail = false;
    }
    else
    {
        $newMail = true;
    }

    // Get the LiveId of the logged in user
$loggedInLiveId = $_COOKIE['wls_liveId'];
$owaText = getLocalizedString('owaText');
$attachmentsText = getLocalizedString('attachmentsText');
$owaUrl = 'https://www.outlook.com/owa';
$inboxLink = str_replace('[[url]]',$owaUrl,$owaText);
$attachmentsLink = str_replace('[[url]]',$owaUrl,$attachmentsText);

// Check to see if ExchangeLabs is enabled for this moodle instance.
if( @$CFG->block_live_services_useEWS == 1 )
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
        }
    }
    $exchangeEnabled = true;
}
else
{
    $exchangeEnabled = false;
}

function render_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.'"';
    }
    return '<td align="'.$alignment.'" style="white-space:nowrap "'.$class.'>'.$text.'</td>';
}

function render_files_table($courseid)
{
    global $CFG;
    global $USER;
    global $COURSE;
    global $showFiles;
    $table = '<table border="0" cellpadding="2" cellspacing="0">';
    $resources = get_resources($courseid);
    if($resources!==FALSE && count($resources)>0)
    {
        if($showFiles===true)
        {
            foreach($resources as $resource)
            {
                $table.='<tr>'.render_cell('','<input type="checkbox" id="resource'.$resource->id.'" name="resource" />','').render_cell('',$resource->name,'').'</tr>';
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

function get_resources($courseid)
{
    global $CFG, $DB;
    return $DB->get_records_sql("SELECT r.id, r.name, r.reference
                            FROM {resource} r
                            WHERE r.course=$courseid AND r.type='file'
                            ORDER BY r.name");
}



?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <?php         echo('<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/live_services/shared/popup_dialog_div_styles.css">'); ?>
    <!--CSS file (default YUI Sam Skin) -->
    <link type="text/css" rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/yuiloader/yuiloader-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/event/event-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/datasource/datasource-min.js"></script>
    <!-- JSON Dependencies -->
    <script src="http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js"></script>
    <!-- JSON Source file -->
    <script type="text/javascript"  src="http://yui.yahooapis.com/2.7.0/build/json/json-min.js"></script>
    <?php echo('<script type="text/javascript" src="$CFG->wwwroot/blocks/live_services/shared/popupdialogs.js"></script>') ?>
    <!-- Autocomplete Source file -->
    <script type="text/javascript" src="http://yui.yahooapis.com/2.7.0/build/autocomplete/autocomplete-min.js"></script>
        <script type="text/javascript">
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
    </script>
    <script type="text/javascript">

        var validationMessage;

        var handleSuccess = function( response )
        {
            var result = YAHOO.lang.JSON.parse(response.responseText);
            if(result.error=='true')
            {
                alert(result.reason + "\n" + result.exceptionmessage);
            }
            parentGrayOut(false,null);
            hideOverlay();
        };

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
        };

        var callback =
        {
            success:handleSuccess,
            failure:handleFailure,
            argument:[]
        };

        function trim(val) {
            var stringToTrim = new String(val);
            return stringToTrim.replace(/^\s+|\s+$/g,"");
        }

        function validateRequest()
        {
            validationMessage = "";
            var to = new String(document.getElementById('to').value);
            if(trim(to).length==0)
                {
                    validationMessage += "The 'To' field cannot be empty\n";
                }
            var subject = new String(document.getElementById('subject').value);
            if(trim(subject).length==0)
                {
                    validationMessage += "The 'Subject' field cannot be empty\n";
                }
            var body = new String(document.getElementById('body').value);
            if(trim(body).length==0)
                {
                    validationMessage += "The 'Body' field cannot be empty\n";
                }

            if(validationMessage.length > 0)
                {
                    return false;
                }
            return true;
        }

        function makeRequest()
        {
            if(validateRequest()==false)
            {
                alert(validationMessage);
                return false;
            }
            var sUrl = 'send_ews_item.php';
            var to = document.getElementById( 'to' );
            var subject = document.getElementById( 'subject' );
            var body = document.getElementById( 'body' );
            var postData = "type=email&to=" + encodeURI( to.value ) +
                            "&subject=" + encodeURI( subject.value ) +
                            "&body=" + encodeURI( body.value ) ;
            var resources = document.getElementsByName('resource');
            var attachmentIndex = 1;
            for(var i=0;i < resources.length;i++)
                {
                    if(resources[i].checked)
                    {
                        postData += "&attachment" + attachmentIndex.toString() + "=" + encodeURI(resources[i].id);
                        attachmentIndex += 1;
                    }
                }
            YAHOO.util.Connect.asyncRequest( 'POST', sUrl, callback, postData );
        }

    function parentGrayOut(vis, opt) {
      // Pass true to gray out screen, false to ungray
    // options are optional.  This is a JSON object with the following (optional) properties
    // opacity:0-100
    // Lower number = less grayout higher = more of a blackout
    // zindex: #
    // HTML elements with a higher zindex appear on top of the gray out
    // bgcolor: (#xxxxxx)
    // Standard RGB Hex color code
    // grayOut(true, {'zindex':'50', 'bgcolor':'#0000FF', 'opacity':'70'});
    // Because options is JSON opacity/zindex/bgcolor are all optional and can appear
    // in any order.  Pass only the properties you need to set.
    var options = opt || {};
    var zindex = options.zindex || 50;
    var opacity = options.opacity || 70;
    var opaque = (opacity / 100);
    var bgcolor = options.bgcolor || '#000000';
    var dark=window.parent.document.getElementById('darkenScreenObject');

    if (!dark) {
    // The dark layer doesn't exist, it's never been created.  So we'll
    // create it here and apply some basic styles.
    // If you are getting errors in IE see: http://support.microsoft.com/default.aspx/kb/927917
    var tbody = window.parent.document.getElementsByTagName("body")[0];
    var tnode = window.parent.document.createElement('div');
    // Create the layer.
    tnode.style.position='absolute';
    // Position absolutely
    tnode.style.top='0px';
    // In the top
    tnode.style.left='0px';
    // Left corner of the page
    tnode.style.overflow='hidden';
    // Try to avoid making scroll bars
    tnode.style.display='none';
    // Start out Hidden
    tnode.id='darkenScreenObject';
    // Name it so we can find it later
    tbody.appendChild(tnode);
    // Add it to the web page
    dark=window.parent.document.getElementById('darkenScreenObject');
    // Get the object.
    }
    if (vis) {
    // Calculate the page width and height
    var pageWidth, pageHeight
    if( window.parent.document.body && ( window.parent.document.body.scrollWidth || window.parent.document.body.scrollHeight ) )
    {
         pageWidth = window.parent.document.body.scrollWidth+'px';
         pageHeight = window.parent.document.body.scrollHeight+'px';
    } else if( window.parent.document.body.offsetWidth )
        {      pageWidth = window.parent.document.body.offsetWidth+'px';
              pageHeight = window.parent.document.body.offsetHeight+'px';
        } else {
            pageWidth='100%';
            pageHeight='100%';    }
            //set the shader to cover the entire page and make it visible.
            dark.style.opacity=opaque;
            dark.style.MozOpacity=opaque;
            dark.style.filter='alpha(opacity='+opacity+')';
            dark.style.zIndex=zindex;
            dark.style.backgroundColor=bgcolor;
            dark.style.width= pageWidth;
            dark.style.height= pageHeight;
            dark.style.display='block';
        } else {     dark.style.display='none';  }
    }

        function hideOverlay()
        {
            var iframe = window.parent.document.getElementById('popupIFrame');
            iframe.src = "";
            var overlay = window.parent.document.getElementById('overlay1');
            if(overlay)
                {
                    overlay.style.visibility='hidden';
                }
        }
    </script>
   
</head>
<body class="yui-skin-sam" onload="javascript:attachKeyHandler();document.getElementById('to').focus();">
    <div id="popupContainer">
        <div id="popupHeaderContainer">
            <h3 id="popupHeader"><?php echo(getLocalizedString('popupQuickMessageTitle'));?><img id="closeImage" src="<?php echo($CFG->wwwroot.'/blocks/live_services/shared/images/close.png'); ?>"  onClick="javascript:parentGrayOut(false,null);hideOverlay();"/>
            </h3>
        </div>
        <div id="popupBodyContainer">
        <div id="popupBody">
        <form>
            <table id="emailOptionsTable" class="bodyMainTable" style="width:624px">
                <tr>
                    <td width="60px"><label><?php echo(getLocalizedString('emailTo'));?>:</label></td>
                    <td width="550px" colspan="2">
                    <div id="autoCompletePanel" style="z-index:1">
                        <input id="to" type="text" style="width:550px;" tabindex="1" title="" value="<?php echo( $invitee );?>" />
                        <input type="hidden" id="resolvedNamesHidden" />
                        <div id="autoCompleteResults" style="z-index:20000"></div>
                    </div>
                    </td>
                </tr>
                <tr style="height:8px;"><td colspan="2">&nbsp;</td></tr>
                <tr><td><label for="subject"><?php echo(getLocalizedString('emailSubject'));?>:</label></td>
                    <td width="280"><input id="subject" type="text" style="width:260px;" tabindex="2" value="<?php echo(isset($message)? $message['subject']:'');?>"/></td>
                    <td width="270px" rowspan="3">
                        <fieldset><legend><?php echo(getLocalizedString('emailAddAttachment'));?></legend>
                            <div id="fieldsetContainer">
                                <p id="courseRequiredMessage" style="color:#FF3333;display:<?php if($showFiles){echo('none');}else{echo('block');} ?>;"><?php echo(getLocalizedString('emailAddAttachmentCourseNotSelected'));?></p>
                                <p id="attachmentsList" style="display:<?php if($showFiles) {echo('block');} else{echo('none');} ?>;">
                                    <?php echo render_files_table($courseid); ?>
                                </p>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                <tr><td><label for="body"><?php echo(getLocalizedString('emailBody'));?>:</label></td><td width="260px"><textarea id="body"  style="width:260px;" rows="11" tabindex="3" value="<?php echo(isset($message)? html_entity_decode($message['body']):'');?>"></textarea></td></tr>
                <tr><td>&nbsp;</td><td colspan="2"><input id="sendButton" type="button" value="<?php echo(getLocalizedString('emailSend'));?>" onClick="javascript:makeRequest();" tabindex="4" /></td></tr>
            </table>
        </form>        
        </div>
        </div>
    </div>
    <script type="text/javascript">
    var MicrosoftServices = {};
    MicrosoftServices.QueryMatchContains = function(){
    var ds = new YAHOO.util.XHRDataSource("<?php echo($CFG->wwwroot.'/blocks/live_services/services/email/resolve_names.php');?>");
    ds.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
    ds.responseSchema = {resultsList: "ResultSet.Result",fields:["rn"],metaFields:{query:"ResultSet.query", resolvedNames:"ResultSet.resolvedNames"}};
    // First AutoComplete
    var autoComplete = new YAHOO.widget.AutoComplete("to","autoCompleteResults",ds);
    autoComplete.queryMatchContains = true;
    autoComplete.queryMatchCase = false;
    autoComplete.minQueryLength = 3;
    autoComplete.queryDelay = 0.3;
    autoComplete.useIFrame = false;
    autoComplete.formatResult = function(resultData,query,resultMatch)
    {
        return "<div class=\"result\">" + resultMatch + "</div>";
    };
    autoComplete.doBeforeLoadData = function(query, response,payload)
    {
        YAHOO.util.Dom.get("resolvedNamesHidden").value = response.meta.resolvedNames;
        return true;
    };
    return {
        oDS: ds,
        oAC: autoComplete
    }
}();
    var itemSelectHandler = function(type, args) {
        var oSelItem = args[1];
        var oData = args[2];
        YAHOO.util.Dom.get("to").value = YAHOO.util.Dom.get("resolvedNamesHidden").value + oData[0] + ";";
    };

    MicrosoftServices.QueryMatchContains.oAC.itemSelectEvent.subscribe(itemSelectHandler);
    </script>
</body>
</html>
