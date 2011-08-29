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

require_once( $CFG->dirroot . '/config.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * Class the renders the Email and Calendar portion of the Microsoft Live Services Plug-in for Moodle block
 */
class EmailService {
    private $_eventCount = 0;
    private $exchangeServiceData = null;
    /**
     * Class constructor
     * @global array $CFG - array of global configuration values
     */
    function __construct() {
        global $CFG;

        if( @$CFG->block_live_services_useEWS == 1 ) {
			$owaUrl = @$CFG->block_live_services_ewsServiceUrl;
			// TODO: Add auto discover feature
        	$targetService = "$owaUrl/ews/exchange.asmx";
            $this->exchangeServiceData = array(
                                            'ExchangeURL'           => $owaUrl,
            								'TargetService' => $targetService, 
                                            'AuthenticationData'    =>
                                                    array(  'TargetService' => $targetService )
                                                    );
        }
    }
    /**
     * Main function responsible for rendering the Email and Calendar. This method is called from render_content.php
     * @global array $CFG - array of global configuration values
     * @param bool $refresh - true if this has already been rendered and is being refreshed via AJAX, false if this is being rendered for
     * the first time
     * @return string (of HTML)
     */
    function Render($refresh = false) 
    {
        global $CFG;
        $returnData = '';
        // Get the LiveId of the logged in user
        $loggedInLiveId = @$_COOKIE['wls_liveId'];
        $owaUrl = @$CFG->block_live_services_ewsServiceUrl;

        // If both the showCalendar and showEmail are false then there is nothing to do here
        // simply return an empty string
        if( $CFG->block_live_services_showCalendar == 0 && $CFG->block_live_services_showEmail == 0 ) return "";
	
        // Check to see if ExchangeLabs is enabled for this moodle instance.
        if( @$CFG->block_live_services_useEWS == 1 ) 
        {
            // $refresh is set to true when an ajax call is being made to reload email/calendar items
            if( $refresh ) {
                // always get mail from Exchange Server when refresh is true
                $emailData = $this->GetEmail( $loggedInLiveId, $this->exchangeServiceData );
            }
            else {
                // we are not refreshing via ajax, so this is the initial page load
                // we want the initial page load to be as fast as possible, making calls to EWS can be slows (1-2 seconds)
                // we need to know if the logged in user is on Exchange or not.
                
                if( isset( $_SESSION['msm_userIsOnExchange'] ) ) {
                    // we know the user is an exchange user, set $emailData to a empty result, we will back fill the actual data via an ajax call
                    $emailData = new EWSQueryResult(0);                    
                }
                else {
                    // we need to determine if the user is on exchange or not.  The only way is to try and read the mail
                    $emailData = $this->GetEmail( $loggedInLiveId, $this->exchangeServiceData );
                }
            }

            if($emailData==null) 
            { 
                if( isset($_SESSION['msm_userIsOnExchange'] ) ) { unset($_SESSION['msm_userIsOnExchange']); }
                
                // By default, if we are not able to get the emailData from exchaange
                // then rener the hotmail links
                $returnData .= $this->RenderWindowsLive($loggedInLiveId);
            }
            else 
            {
                $_SESSION['msm_userIsOnExchange'] = "1";
                
                if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS) 
                {
                    $returnData .= $this->RenderWindowsLive($loggedInLiveId);
                }
                else 
                {
                    $inboxLink = '<h3 id="msmEmailAndCalendarHeader" class="msm_h3" onclick="toggleExpandCollapse(\'msmEmailAndCalendarImage\',\'msmEmailAndCalendar\');">
                    <table>
			    <tr>
				<td class="logo">
					<img src="'.$CFG->wwwroot.'/blocks/live_services/services/email/outlook.png"/>
				</td>
				<td class="caption">Outlook&reg;&nbsp;Live</td><td class="expandCollapse">
					<img id="msmEmailAndCalendarImage" src="'.$CFG->wwwroot.'/blocks/live_services/shared/images/switch_minus.gif">
				</td>
			    </tr>
		    </table></h3>
		    <div class="msm_collapsible" id="msmEmailAndCalendar" style="display:block">
		    	<div id="owaLinksContainer">
		    		<a href="'.$owaUrl.'" target="blank">'.getLocalizedString('emailViewLink').'</a>';
                    
                    if(!$refresh) 
                    {
                        $returnData .= $inboxLink;
                        if($CFG->block_live_services_showEmail) 
                        {
                            $returnData .= $this->GetQuickMessageLink();
                        }
                        if($CFG->block_live_services_showCalendar) 
                        {
                            $returnData .= $this->GetQuickEventLink();
                        }                        
                        $returnData .= '</div>';
                        $returnData .= '<div id="refreshContainer">';
                    }
                    if( $CFG->block_live_services_showEmail ) 
                    {
                        if( $refresh )
                        {
                            $emailContent = $this->RenderEmail( $loggedInLiveId, $emailData );
                            $_SESSION['emailContent'] = $emailContent;
                        }
                        else
                        {
                            $emailContent = @$_SESSION['emailContent'];
                        }
			
                        $returnData .= $emailContent;
                    }
                    if( $CFG->block_live_services_showCalendar ) 
                    {
                        if( $refresh )
                        {			    
                            // Get mail from Exchange Server. If there is no mail, display Hotmail version
                            $calendarData = $this->GetCalendar( $loggedInLiveId, $this->exchangeServiceData );

                            $calendarContent = $this->RenderCalendar( $loggedInLiveId, $calendarData );
                            $_SESSION['calendarContent'] = $calendarContent;
                        }
                        else
                        {
                            $calendarContent = @$_SESSION['calendarContent'];
                        }
			
                        $returnData .=  $calendarContent; 
                    }
                    if(!$refresh) 
                    {
                        $returnData .= '</div></div>'; //end of refreshContainer
                    }
                    $returnData .= $this->WriteLoadContactsAsyncScript($refresh);
                }
            }
        }
        else 
        {
            $returnData .= $this->RenderWindowsLive($loggedInLiveId);
        }
        $returnData .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/email_service_scripts.php"></script>';
        return $returnData;

    }
    /**
     * Renders the WindowsLive Hotmail version of email and calendar
     * @param <string> $loggedInLiveId - the liveid of the current user
     * @returns <string>
     */
    private function RenderWindowsLive($loggedInLiveId)
    {
        global $CFG;
        $returnData = $this->RenderWindowsLiveHeader();
        if( $CFG->block_live_services_showEmail ) {
            $returnData .= $this->RenderWindowsLiveMail($loggedInLiveId);
        }
        if( $CFG->block_live_services_showCalendar ) {
            $returnData .= $this->RenderWindowsLiveCalendar($loggedInLiveId);
        }
        $returnData .= $this->RenderWindowsLiveFooter();
        return $returnData;
    }

    /**
     * Renders the title bar of the Windows Live Hotmail mail and calendar
     */
    private function RenderWindowsLiveHeader()
    {
        global $CFG;
        $returnData = '<h3 id="msmHotmailHeader" class="msm_h3" onclick="toggleExpandCollapse(\'msmHotmailImage\',\'msmHotmail\');">
                    <table><tr><td class="logo"><img src="'.$CFG->wwwroot.'/blocks/live_services/services/email/windowslivehotmail.gif"/></td><td class="caption">Hotmail&reg;</td><td class="expandCollapse"><img id="msmHotmailImage" src="'.$CFG->wwwroot.'/blocks/live_services/shared/images/switch_minus.gif"></td></tr></table></h3>
                    <div class="msm_collapsible" id="msmHotmail" style="display:block">';
        return $returnData;
    }
    /**
     * Renders the footer section of the Windows Live Hotmail mail and calendar
     */
    private function RenderWindowsLiveFooter()
    {
        return '</div>'; //id msmHotmail
    }

    /**
     * Returns a link to the lightbox that allows the user to create a new message
     * @return string (of HTML)
     */
    private function GetQuickMessageLink() {
        $emailQuickMessage = getLocalizedString('emailQuickMessage');
        return '<span id="quickMessageSection" style="display:inline">
                    |
                    <a href="#" id="showEmail">'.$emailQuickMessage.'</a></span>';
    }

     /**
     * Returns a link to the lightbox that allows the user to create a new event
     * @return string (of HTML)
     */
    private function GetQuickEventLink() {
        $calendarQuickItem = getLocalizedString('calendarQuickItem');
        return '<span id="quickEventSection" style="display:inline"> | <a href="#" id="showAppointment">'.$calendarQuickItem.'</a></span>';
    }

    /**
     * If the block is being rendered for the first time, a script block is written to the page that performs AJAX refresh
     * @global array $CFG - array of global configuration values
     * @param bool $refresh - true if this has already been rendered and is being refreshed via AJAX, false if this is being rendered for
     * the first time
     * @return string (a script block)
     */
    private function WriteLoadContactsAsyncScript($refresh) {
        if($refresh)
            return '';
        else {
            global $CFG;
            $script = '<!-- Dependency -->
    <script src="http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js"></script>
    <!-- Used for Custom Events and event listener bindings -->
    <script src="http://yui.yahooapis.com/2.7.0/build/event/event-min.js"></script>
    <!-- Source file -->
    <script src="http://yui.yahooapis.com/2.7.0/build/connection/connection-min.js"></script>
    ';
            $script .='<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/email/email_service_ajax.php"></script>';
            return $script;
        }
    }

    /**
     * gets the unread email data from an EWS call
     * @param string $liveId - the LiveID of the current user
     * @param array $exchangeServiceData - the data necessary to connect to EWS, includes tokens and EWS server location
     * @return string
     */
    private function GetEmail($liveId, $exchangeServiceData) {
        // use the web service to get the number of unread messages
        if($liveId==null || $exchangeServiceData==null) {
            return null;
        }
        $ewsWrapper = new EWSWrapper();
        $unreadMailResponse = $ewsWrapper->GetUnreadEMail( $liveId, $exchangeServiceData, 20);
        $result = json_decode($unreadMailResponse->getResultString());
        if($result->code=="a:ErrorNonExistentMailbox") {
            return null;
        }
        return $unreadMailResponse;
    }

    /**
     * returns upcoming calendar event data  from an EWS call
     * @param string $liveId - the LiveID of the current user
     * @param array $exchangeServiceData - the data necessary to connect to EWS, includes tokens and EWS server location
     * @return string
     */
    private function GetCalendar($liveId, $exchangeServiceData) {
        $ewsWrapper = new EWSWrapper();
        try {
            $calendarItemsResponse = $ewsWrapper->GetUpcomingCalendarItems( $liveId, $exchangeServiceData, 3 );
            return $calendarItemsResponse;
        }

        catch(Exception $e) {
            handleException($e);
            return new EWSQueryResult(-1);
        }
    }
    /**
     * renders the unread email
     * @global array $CFG - array of global configuration values
     * @param string $liveId - the LiveID of the current user
     * @param array $exchangeServiceData - the data necessary to connect to EWS, includes tokens and EWS server location
     * @return string (of HTML)
     */
    private function RenderEmail( $liveId, $emailData ) {
        global $CFG;
        $owaUrl = @$CFG->block_live_services_ewsServiceUrl;
        if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS) {
            die(getLocalizedString('emailExchangeNotSetUp'));
        }
        //get count of new messages with a max of 20
        //this limit could be changed or made configurable
        $messageCount = $emailData->getCount();
        if($messageCount > 20)
            {$messageCount = 20;}

        $unreadMessages = "";
        switch($messageCount) {
            case -1:
                {
                    $emailCountText = getLocalizedString( 'emailFetchFailure' );
                    break;
                }
            case 0:
                {
                    $emailCountText = getLocalizedString( 'emailNoMessages' );
                    break;
                }
            case 1:
                {
                     $emailCountText = getLocalizedString( 'emailOneMessage' );
                     break;
                }
            default://>1
                {
                    $emailCountText = $messageCount . "&nbsp;" . getLocalizedString( 'emailManyMessages' );
                    break;
                }
        }
        if($messageCount > 0) {
            $unreadMessages = $this->RenderUnreadMail( $emailData->getItems(), $owaUrl );
        }

        $emailViewLink = getLocalizedString( 'emailViewLink' );
        $emailSendLink = getLocalizedString( 'emailSendLink' );
        $emailIconAltText = getLocalizedString( 'emailIconAltText' );
        $emailQuickMessage = getLocalizedString('emailQuickMessage');
        $emailServicePath = "$CFG->wwwroot/blocks/live_services/services/email";

        return <<<EMAIL_SERVICE
        <div class='live_service_div' id='msm_email_service'>
            <div class='live_service_header'>
                <div class='live_service_icon' style='margin:4px 8px 0px 0px'>
                    <a target='_blank' href='$owaUrl'>
                        <img src='$emailServicePath/email_icon.png' title='$emailIconAltText' />
                    </a>
                </div>
                <p class='live_service_text' style='padding-top:12px'>
                    <strong>$emailCountText</strong>
                </p>
            </div>
        $unreadMessages

        </div>
EMAIL_SERVICE;
    }
    
    /**
     * renders the upcoming calendar events
     * @global array $CFG - array of global configuration values
     * @param string $liveId - the LiveID of the current user
     * @param array $exchangeServiceData - the data necessary to connect to EWS, includes tokens and EWS server location
     * @return string (of HTML)
     */
    private function RenderCalendar( $liveId, $calendarData ) {
        global $CFG;
        $owaUrl = @$CFG->block_live_services_ewsServiceUrl;
        if(is_null($owaUrl) || !@$CFG->block_live_services_useEWS) {
            die(getLocalizedString('emailExchangeNotSetUp'));
        }
        $eventCount = $calendarData->getCount();

        $upcomingEvents = "";
        if( $eventCount == -1 ) {
            $eventCountText = getLocalizedString( 'calendarFetchFailure' );
        }
        else if( $eventCount == 0 ) {
            $eventCountText = getLocalizedString( 'calendarNoEvents' );
        }
        else {
            $upcomingEvents = $this->RenderUpcomingEvents( $calendarData->getItems(), $owaUrl );
            $eventCount = $this->_eventCount;
            if( $eventCount == 0 ) {
                $eventCountText = getLocalizedString( 'calendarNoEvents' );
            }
            if( $eventCount == 1 ) {
                $eventCountText = getLocalizedString( 'calendarOneEvent' );
            }
            else {
                $eventCountText = $eventCount . "&nbsp;" . getLocalizedString( 'calendarManyEvents' );
            }            
        }

        $calendarViewLink = getLocalizedString( 'calendarViewLink' );
        $calendarCreateLink = getLocalizedString( 'calendarCreateLink' );
        $calendarIconAltText = getLocalizedString( 'calendarIconAltText' );
        $calendarQuickItem = getLocalizedString( 'calendarQuickItem' );
        $calendarServicePath = "$CFG->wwwroot/blocks/live_services/services/email";

        return <<<CALENDAR_SERVICE
        <div class='live_service_div'>
            <div class='live_service_header'>
                <div class='live_service_icon' style='margin:8px 8px 0px 0px' >
                    <a target='_blank' href='$owaUrl'>
                        <img src='$calendarServicePath/calendar_icon.png' title='$calendarIconAltText' />
                    </a>
                </div>
                <p class='live_service_text' style='padding-top:12px'>
                    <strong>$eventCountText</strong>
                </p>
            </div>
        $upcomingEvents
        </div>
CALENDAR_SERVICE;
    }
    /**
     * called by Render, this renders Windows Live email when OWA is not configured or in use
     * @global array $CFG - array of global configuration values
     * @param string $liveId - the LiveID of the current user
     * @return string (of HTML)
     */
    private function RenderWindowsLiveMail( $liveid ) {
        $emailViewLink = getLocalizedString( 'emailViewLink' );
        $emailSendLink = getLocalizedString( 'emailSendLink' );
        $emailIconAltText = getLocalizedString( 'emailIconAltText' );

        $emailWindowsLiveMail = getLocalizedString( 'emailWindowsLiveMail' );

        global $CFG;
        $emailServicePath = "$CFG->wwwroot/blocks/live_services/services/email";

        $hotmailInboxLink = "http://mail.live.com/?rru=inbox";
        $hotmailComposeMessageLink = "http://mail.live.com/?rru=compose";

        return <<<EMAIL_SERVICE
        <div class='live_service_div'>
            <div class='live_service_header'>
                <div class="live_service_icon">
                    <a target='_blank' href='$hotmailInboxLink'>
                        <img src='$emailServicePath/email_icon.png' title='$emailIconAltText' />
                    </a>
                </div>
                <p class="live_service_text live_service_icon_height">$emailWindowsLiveMail<br/>
                    <a target='_blank' href='$hotmailInboxLink'>$emailViewLink</a>&nbsp;|&nbsp;<a target='_blank' href='$hotmailComposeMessageLink'>$emailSendLink</a>
                </p>
            </div>
        </div>
EMAIL_SERVICE;
    }
    /**
     * called by Render, this renders Windows Live Calendar events when OWA is not configured or in use
     * @global array $CFG - array of global configuration values
     * @param string $liveId - the LiveID of the current user
     * @return string (of HTML)
     */
    private function RenderWindowsLiveCalendar( $liveid ) {
        $calendarViewLink = getLocalizedString( 'calendarViewLink' );
        $calendarCreateLink = getLocalizedString( 'calendarCreateLink' );
        $calendarIconAltText = getLocalizedString( 'calendarIconAltText' );

        $calendarLiveCalendar = getLocalizedString( 'calendarLiveCalendar' );


        global $CFG;
        $emailServicePath = "$CFG->wwwroot/blocks/live_services/services/email";

        $hotmailCalendarLink = "http://calendar.live.com/calendar/calendar.aspx";
        $hotmailNewEventLink = "http://calendar.live.com/calendar/calendar.aspx?rru=addevent";

        return <<<EMAIL_SERVICE
        <div class='live_service_div'>
            <div class='live_service_header'>
                <div class="live_service_icon">
                    <a target='_blank' href='$hotmailCalendarLink'>
                        <img src='$emailServicePath/calendar_icon.png' title='$calendarIconAltText' />
                    </a>
                </div>
                <p class="live_service_text live_service_icon_height">$calendarLiveCalendar<br/>
                    <a target='_blank' href='$hotmailCalendarLink'>$calendarViewLink</a>&nbsp;|&nbsp;<a target='_blank' href='$hotmailNewEventLink'>$calendarCreateLink</a>
                </p>
            </div>
        </div>
EMAIL_SERVICE;
    }

    /**
     * Called by render, this renders the unread email items when OWA is enabled and configured
     * @global array $CFG - array of global configuration values
     * @param array $items - array of unread email items
     * @param string $linkUrl - a link to the unread email ite
     * @return string (of HTML)
     */
    private function RenderUnreadMail( $items, $linkUrl ) {
        global $CFG;
        $returnHtml = '';
        if( count( $items ) > 0 ) {
            $emailItemIdArray = array();
            for( $i = 0; $i < count( $items ); $i++ ) {
                $viewEmailId = 'viewemail'.$i;
                //itemid and changekey are how EWS identifies emails and versions
                $changeKey = urldecode($items[$i]['ItemId']['ChangeKey']);
                $id = urldecode($items[$i]['ItemId']['Id']);
                if($i < 3) {
                    $subject = stripslashes($items[$i]['Subject']);
                    if(strlen($subject) == 0)
                    {
                        $subject = 'Untitled Message';
                    }
                    $owaWebLink = $linkUrl . urldecode( $items[$i]['WebClientReadFormQueryString'] );
                    $returnHtml .= <<<HTML
                    <ul class='live_service_list_email'><li><a href="#" id="viewemail$i" itemid="$id" changekey="$changeKey">$subject</a></li></ul>
HTML;
                }
               $emailItemIdArray["$i"] = '{"itemId":"'.$id.'", "changeKey":"'.$changeKey.'"}';
            }
        }
        $_SESSION['emailItemIdArray'] = $emailItemIdArray;
        return $returnHtml;
    }
    /**
     * Called by render, this renders the upcoming event items when OWA is enabled and configured
     * @global array $CFG - array of global configuration values
     * @param array $items - array of upcoming event items
     * @param string $linkUrl - a link to the upcoming event item
     * @return string (of HTML)
     */
    private function RenderUpcomingEvents( $items, $linkUrl ) {
        global $CFG;
        $returnHtml = '';
        $this->_eventCount = 0;
        if( count( $items ) > 0 ) {
            $groupByDateArray = array();
            for( $i = 0; $i < count( $items ); $i++ ) {
                $id = $items[$i]['ItemId']['Id'];
                $changeKey = $items[$i]['ItemId']['ChangeKey'];
                $end = convertToLocalTime($items[$i]['End'],'Y-m-d\TH:i:s');
                $now = date('Y-m-d\TH:i:s');
                $now = convertToLocalTime($now,'Y-m-d\TH:i:s');
                if($now > $end) {
                    continue;
                }
                else {
                    $this->_eventCount = $this->_eventCount + 1;
                }
                $startDate = convertToLocalTime( $items[$i]['Start'], 'Y-m-d' );
                $startDateShort = $this->getDisplayDayOfWeek($startDate,true);
                $startDate = $this->getDisplayDayOfWeek($startDate,false);

                $endDate = convertToLocalTime( $items[$i]['End'], 'Y-m-d' );
                $endDateShort = $this->getDisplayDayOfWeek($endDate,true);
                $endDate = $this->getDisplayDayOfWeek($endDate,false);

                $startTime = convertToLocalTime( $items[$i]['Start'], 'g:i A' );
                $endTime = convertToLocalTime( $items[$i]['End'], 'g:i A' );
                
                $isAllDayEvent = $items[$i]['IsAllDayEvent'];
                $isMultiDayEvent = $startDate === $endDate?false:true;
                $subject = strlen(stripslashes($items[$i]['Subject']))==0?'Untitled Event':stripslashes($items[$i]['Subject']);
                $owaWebLink = $linkUrl . urldecode( $items[$i]['WebClientReadFormQueryString'] );
                $isRecurring = $items[$i]['IsRecurring'];

                $itemArray = array( 'Subject' => $subject,
                                    'StartDate' => $startDate,
                                    'StartDateShort' => $startDateShort,
                                    'StartTime' => $startTime,
                                    'EndDate' => $endDate,
                                    'EndDateShort' => $endDateShort,
                                    'EndTime' => $endTime,
                                    'OwaWebLink' => $owaWebLink,
                                    'IsRecurring' => $isRecurring,
                                    'IsAllDayEvent' => $isAllDayEvent,
                                    'IsMultiDayEvent' => $isMultiDayEvent,
                                    'Id'=>$id,
                                    'ChangeKey'=>$changeKey);

               $groupByDateArray[$startDate][] = $itemArray;
            }
            $i = 0;
            foreach ($groupByDateArray as $idx1=>$val1) {
                $dayofWeek = $idx1; //$this->getDisplayDayOfWeek($idx1);

                $returnHtml .= "<ul class='live_service_list_calendar'><b>$dayofWeek</b></ul>";
                foreach( $val1 as $idx=>$val ) {
                    $recurringIcon = "";
                    if( $val['IsRecurring'] == "true" ) {
                        $recurringIcon = "&nbsp;<img src='$CFG->wwwroot/blocks/live_services/services/email/recurring_event.png' />";
                    }

                    $owaWebLink = $val['OwaWebLink'];
                    $subject = $val['Subject'];
                    $startDate = $val['StartDate'];
                    $startDateShort = $val['StartDateShort'];
                    $startTime = $val['StartTime'];
                    $endDate = $val['EndDate'];
                    $endDateShort = $val['EndDateShort'];
                    $endTime = $val['EndTime'];
                    $isAllDayEvent = $val['IsAllDayEvent'];
                    $isMultiDayEvent = $val['IsMultiDayEvent'];
                    $allDayEvent = '';
                    if($isAllDayEvent=="true") {
                        $allDayEvent = '&nbsp;*<em>All Day Event</em>';
                        $timeDisplay = $allDayEvent;
                    }
                    else {
                        if($isMultiDayEvent=="true") {
                            $timeDisplay = $startDateShort.'&nbsp;'.$startTime.' - '.$endDateShort.'&nbsp;'.$endTime;
                        }
                        else {
                            $timeDisplay = $startTime.' - '.$endTime;
                        }
                    }
                    //itemid and changekey are how EWS identifies events and versions
                    $id = urldecode($val['Id']);
                    $changeKey = urldecode($val['ChangeKey']);
                    $viewEventId = 'viewevent'.$i;
                    $returnHtml .= <<<HTML
<ul class='live_service_list_calendar'><li><a href="#" id="$viewEventId" itemid="$id" changekey="$changeKey">$subject</a>$recurringIcon<br />$timeDisplay</li></ul>
HTML;
                    $i = $i + 1;
                }
            }            
        }
        return $returnHtml;
    }
    /**
     * returns the day of the week for the supplied date
     * @param string $date - a date in the format Y-m-d
     * @param bool $shortDisplay - if true, returns first 3 letters of day of week (e.g. "Mon")
     * if false, returns full day of week, e.g. "Monday".
     * @return string
     */
    private function getDisplayDayOfWeek( $date, $shortDisplay = false ) {
        // Get the current time and convert to localTime
        $today = date('Y-m-d\TH:i:s\Z');
        $today = convertToLocalTime($today,'Y-m-d');
        if($shortDisplay==false) {
            if( $date == $today ) {
                return getLocalizedString( 'calendarToday' );
            }
            return date_create($date)->format('l');
        }
        else {
            return substr(date_create($date)->format('l'),0,3);
        }
    }
}
?>
