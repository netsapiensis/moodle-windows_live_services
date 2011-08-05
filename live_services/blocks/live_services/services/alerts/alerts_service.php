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
#require_once( $CFG->dirroot . '/lib/javascript.php');
require_once( $CFG->dirroot . '/blocks/live_services/services/alerts/subscription.class.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/alerts/alerts_helper.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/alerts/config.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );


/**
 * The AlertsService class renders the Alerts portion of the Microsoft Live Services Plug-in for Moodle block.
 * This should only be shown if the user is viewing a course
 */
class AlertsService {
	
     private $isAlertsWorking = false;

    /**
     * Renders the portion of the Microsoft Live Services Plug-in for Moodle block that shows the Alerts subscription status and
     * provides a link to send an alert if the user is a teacher for the course.
     * @global $CFG
     * @param int $courseId - the ID of the course
     * @param bool $isTeacher - used to determine if the "Send an Alert" link should be displayed * @return string: HTML
     */
    function Render( $courseId, $isTeacher) {
        $currentUrl = $_SERVER[ "REQUEST_URI" ];
        $urlSearchText = '/course/';
        if( substr_count( $currentUrl, $urlSearchText ) == 0 ) {
            //make sure this code is only executed from the course module
            return '';
        }
        global $CFG;
        $isSubscribed = $this->IsSubscribedForAlerts($courseId);
        //display status and a link to subscribe if not subscribed.
        //display link to unsubscribe if subscribed
        if( $isSubscribed ) {
             // student is subscribed to alerts for this course
            $alertsStatusText = getLocalizedString( 'alertsSubscribed' );
            $url = '/blocks/live_services/services/alerts/unsubscribe_pop.php?courseid='.$courseId;
            $url = 'javascript:void openpopup("'.$url.'","subscribe","menubar=0,location=0,scrollbars,status,resizable,width=800,height=600",0);';
            $alertsStatusText = str_replace( '[[url]]', $url, $alertsStatusText );
        }
        else {
            // // student is not subscribed to alerts for this course
            $groupName = AlertsHelper::GetGroupNameByCourseId($courseId);
            $alertsStatusText = getLocalizedString( 'alertsNotSubscribed');
            $url = '/blocks/live_services/services/alerts/subscribe_pop.php?courseid='.$courseId;
            $url = 'javascript:void openpopup("'.$url.'","subscribe","menubar=0,location=0,scrollbars,status,resizable,width=800,height=600",0);';            
            $alertsStatusText = str_replace( '[[url]]', $url, $alertsStatusText );
        }

        $alertsIconAltText = getLocalizedString( 'alertsIconAltText' );
        $alertsServicePath = $CFG->wwwroot.'/blocks/live_services/services/alerts';
        if($isTeacher && $isSubscribed) {            
            $teacherLinkText = '<p class="live_service_text"><a id="showAlert" href="#">'.getLocalizedString('alertsSendAnAlertLinkText').'</a>'.getLocalizedString('alertsSendAnAlertText').'</p>';
        }
        else {
            $teacherLinkText = '';
        }
        // alerts configuration settings stored in this directory's config.php file
        $alertsConfig = new AlertsConfig();
        $manageAlertsUrl = $alertsConfig->manage_alerts_url;
        $manageAlertsUrl = "javascript:void window.open('".$manageAlertsUrl."','manageAlerts','menubar=0,location=0,scrollbars,status,resizable,width=840,height=600')";
        $manageAlertsText = getLocalizedString('alertsManageText');
        $manageAlertsText = str_replace('[[alertsManageUrl]]',$manageAlertsUrl,$manageAlertsText);
        
	if( $this->isAlertsWorking ) {        
        return <<<ALERTS_SERVICE
        <h3 id="msmAlertsHeader" class="msm_h3" onclick="toggleExpandCollapse('msmAlertsImage','msmAlerts');"><table><tr><td  class="logo"><img src="$CFG->wwwroot/blocks/live_services/services/alerts/alerts_icon.png"/></td><td class="caption">Alerts</td><td class="expandCollapse"><img id="msmAlertsImage" src="$CFG->wwwroot/blocks/live_services/shared/images/switch_minus.gif"></td></tr></table></h3>
        <div class="msm_collapsible" id="msmAlerts" style="display:block">
            <p class="live_service_text">$manageAlertsText</p>
            <p class="live_service_text">$alertsStatusText</p>
            <p class="live_service_text">$teacherLinkText</p>
        </div>
ALERTS_SERVICE;
	}

	return <<<ALERTS_SERVICE
        <h3 id="msmAlertsHeader" class="msm_h3" onclick="toggleExpandCollapse('msmAlertsImage','msmAlerts');"><table><tr><td  class="logo"><img src="$CFG->wwwroot/blocks/live_services/services/alerts/alerts_icon.png"/></td><td class="caption">Alerts</td><td class="expandCollapse"><img id="msmAlertsImage" src="$CFG->wwwroot/blocks/live_services/shared/images/switch_minus.gif"></td></tr></table></h3>
        <div class="msm_collapsible" id="msmAlerts" style="display:block">
            <p class="live_service_text">The alerts services is currently not available.</p>
        </div>	
ALERTS_SERVICE;
    }
    /**
     * Determines whether or not the user is currently subscribed for alerts for this course.
     * @global $CFG
     * @global $USER
     * @param int $courseId - the ID of the course
     * @return bool - true if the user is subscribed for alerts, false if not.
     */
    private function IsSubscribedForAlerts( $courseId ) {
        // false = student is not subscribed to alerts for this course
        // true = student is subscribed to alerts for this course
        global $CFG;
        global $USER;
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/subscription.class.php');
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/alerts_helper.php');
        
        try
        {
		$subscription = new Subscription();
	
		$groupName = AlertsHelper::GetGroupNameByCourseId($courseId);
		//get the user so we can obtain his MSN address as the partnerUID
		if(isset($_COOKIE['wls_liveId'])) {
		    $partnerUid = $_COOKIE['wls_liveId'];
		}
		else {
		    $cannotIdentifyUserError = getLocalizedString('alertsCannotIdentifyUserError');
		    print_error($cannotIdentifyUserError);
		}  
		$isSubscribedForAlerts =  $subscription->validateGroup($partnerUid, $groupName);
		
		$this->isAlertsWorking = true;
		return $isSubscribedForAlerts;
	}
	catch(Exception $e)
	{
		$this->isAlertsWorking = false;
		return false;
	}
    }
}
?>
