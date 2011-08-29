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
global $CFG;
require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * Class that represents a subscription to Windows Live Alerts
 */
class Subscription {

    var $id;
    var $header;
    var $soapClient;

/**
 * Class constructor.  Values needed by SOAP Client calls are set at time of construction.
 * @global $CFG
 */
    public function __construct() {
        global $CFG;
        $pin = $CFG->block_live_services_alertsPinId;
        $password = $CFG->block_live_services_alertsPinPassword;
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/config.php');
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/alerts_helper.php');
        $alertsConfig = new AlertsConfig();
        $timestamp = AlertsHelper::GetWindowsLiveAlertsDateTimeFormat();
        $timestampGuid = AlertsHelper::MakeGUID($timestamp, false);
        $this->header = array('messageID'=>$timestamp.'.'.$timestampGuid.'.'.$pin,'version'=>$alertsConfig->version,'timestamp'=>$timestamp);
        $this->id = array('PINID'=>$pin,'PW'=>$password);

		
	$curl = new CurlLib();
	$httpHeaders = array( );
	$response = $curl->getRestResponse( $alertsConfig->subscription_wsdl, $httpHeaders );
	if( strlen( $response ) )
	{		
		$this->soapClient = new SOAPClient($alertsConfig->subscription_wsdl, array('exceptions' => True, 'connection_timeout' => 2) );
	}
	else
	{
		$exceptionmessage = "Error retrieving Alerts WSDL from $alertsConfig->subscription_wsdl";
		error_log( "Live Alerts Error: " . $exceptionmessage ); 
		
		throw new Exception($exceptionmessage);		


	}

    }
/**
 * Adds a new group and adds the first user to the group.
 * @param string $partnerUid - the email address of the user to add to the new group
 * @param string $groupName - the name of the new group, will be in the form SITE(shortname)_COURSE(shortname)
 * @param string $groupDescription - the description of the new group, synonomous with COURSE(fullname)
 * @return int - status code. -1 if cannot be determined due to SOAP fault. Otherwise actual return code.
 */
    function addGroup($partnerUid, $groupName, $groupDescription) {
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        try {
            $response = $this->soapClient->AddGroup($this->header, $this->id, $groupName, $groupDescription);
            $code = $response->response->statusCode;
            $reason = $response->response->statusReason;

            switch($code) {
                case 0: //success
                case 326: { //group already exists
                        $resultString = $this->addSubscriberToGroup($partnerUid, $groupName);
                        //echo($resultString);
                        $result = json_decode($resultString);
                        //var_dump($result);
                        $code = $result->code;
                        $reason = $result->reason;
                        $error = $result->error;
                        $exceptionmessage = $result->exceptionmessage;
                        break;
                    }
                default: {
                        $error = 'true';
                        break;
                    }
            }
        }
         catch(SoapFault $soapFault) {
                $code = '-1';
                $reason = 'Unable to register a new group for alerts';
                $error = 'true';
                $exceptionmessage = $soapFault->getMessage();
                error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );                
        }
        return getJsonResultString($code, $reason, $error, $exceptionmessage);
    }
/**
 * Adds a new subscriber to an existing group
 * @param string $partnerUid - the email address of the user to add to the group
 * @param string $groupName - the name of the new group, will be in the form SITE(shortname)_COURSE(shortname)
 * @return int - status code. -1 if cannot be determined due to SOAP fault. Otherwise actual return code.
 */
    function addSubscriberToGroup($partnerUid, $groupName) {
        $code = ''; $reason = ''; $error = 'false'; $exceptionmessage = '';

        try {
            $response = $this->soapClient->ValidateSubscriberGroup($this->header, $this->id, $partnerUid, $groupName);
            $code = $response->response->statusCode;
            $reason = $response->response->statusReason;
            if($code==0 && !$response->validRequest) {
                $changeresponse = $this->soapClient->ChangeSubscription($this->header, $this->id, $partnerUid,array($groupName), 'add','',0,'','');
                $code = $changeresponse->response->statusCode;
                $reason =  $changeresponse->response->statusReason;
                if($code!=0) {
                    $error = 'true';
                }
            }
        }
        catch(SoapFault $soapFault) {
            $code = '-1';
            $reason = 'Unable to add subscriber to the '.$groupName.' group';
            $error = 'true';
            $exceptionmessage = $soapFault->getMessage();
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );            
        }
        catch(Exception $exc) {
            $code = '-1';
            $reason = 'Unable to add subscriber to the '.$groupName.' group';
            $error = 'true';
            $exceptionmessage = $exc->getMessage();           
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );                        
        }
        return getJsonResultString($code, $reason, $error, $exceptionmessage);
    }
/**
 * Determines if a user is currently subscribed to a group
 * @param string $partnerUid - the email address of the user
 * @param string $groupName - the name of the new group, will be in the form SITE(shortname)_COURSE(shortname)
 * @return bool - true if subscribed, false if not.
 */
    function validateGroup($partnerUid, $groupName) {
        try {
            $response = $this->soapClient->ValidateSubscriberGroup($this->header, $this->id, $partnerUid, $groupName);
            return ($response->response->statusCode==0 && $response->validRequest);
        }
        catch(SoapFault $soapFault) {
            error_log( "Live Alerts Error: " . $soapFault->getMessage() );
            return false;
        }
        catch(Exception $exc) {
            error_log( "Live Alerts Error: " . $exc->getMessage() );		
            return false;
        }
    }

/**
 * Removes a user from all groups that the user is currently subscribed to. Does not remove user from Windows Live Alerts system.
 * @param string $partnerUid - the email address of the user
 * @return int - status code. -1 if cannot be determined due to SOAP fault. Otherwise actual return code.
 */
    function unsubscribeAll($partnerUid) {
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        try {
            $response = $this->soapClient->FindGroupsForUser($this->header, $this->id, $partnerUid);
            $groups = $response->subscriptionGroups;
            if(count($groups)>0) {
                $changeresponse = $this->soapClient->ChangeSubscription($this->header, $this->id, $partnerUid, $groups, 'remove','',0,'','');
                $code = $changeresponse->response->statusCode;
                $reason = $changeresponse->response->statusReason;
                $error = 'false';
            }
            else {
                $code = '-1';
                $reason = 'No change made. The user was not subscribed to any alert groups';
                $error = 'false';
            }
        }
        catch(SoapFault $soapFault) {
            $code = -1;
            $reason = 'Error unsubscribing from alerts. Please try again later.';
            $error  = 'true';
            $exceptionmessage = $soapFault->getMessage();
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );              
        }
        return getJsonResultString($code, $reason, $error, $exceptionmessage);
    }
/**
 * Removes a subscriber from an existing group
 * @param string $partnerUid - the email address of the user
 * @param string $groupName - the name of the new group, will be in the form SITE(shortname)_COURSE(shortname)
 * @return int - status code. -1 if cannot be determined due to SOAP fault. Otherwise actual return code.
 */
    function removeSubscriberFromGroup($partnerUid, $groupName) {
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        try {
            $response = $this->soapClient->FindGroupsForUser($this->header, $this->id, $partnerUid);
            $groups = $response->subscriptionGroups;
            if(count($groups)>0) {
                foreach($groups as $group) {
                    if($group==$groupName) {
                        $groups = array($groupName);
                        $response = $this->soapClient->ChangeSubscription($this->header, $this->id, $partnerUid, $groups, 'remove','',0,'','');
                        $code = $response->response->statusCode;
                        $reason = $response->response->statusReason;
                        $error = 'false';
                        $resultString = getJsonResultString($code, $reason, $error, $exceptionmessage);        return $resultString;
                        return $resultString;
                    }
                }
            }
            $code = '-1';
            $reason = 'No change made. The user was not a member of the '.$groupName.' alert group.';
            $error = 'false';
        }
        catch(SoapFault $soapFault) {
            $code = -1;
            $reason = 'Error unsubscribing from the '.$groupName.' alert group. Please try again later.';
            $error  = 'true';
            $exceptionmessage = $soapFault->getMessage();
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );
        }
        return getJsonResultString($code, $reason, $error, $exceptionmessage);
    }
/**
 * Redirects the user to the Windows Live Alerts signup page and initiates the signup process.
 * global $CFG
 * @param string $partnerUid - the email address of the user
 * @param int $courseid - used to generate the associated group name and group description
 * @return int - status code. -1 if cannot be determined due to SOAP fault. Otherwise actual return code.
 */
    function initiateSignup($partnerUid, $courseid) {
        global $CFG;
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/config.php');
        $alertsConfig = new AlertsConfig();
        try {
            $returnurl = $alertsConfig->return_url.'?courseid='.$courseid.'&addgroup=true';
            $transportType = $alertsConfig->transport_type;

            $response = $this->soapClient->InitiateSignup($this->header, $this->id, $partnerUid, $returnurl, $transportType);
            if($response->response->statusCode==0) {
                header('Location: '. $response->URL);
            }
            else {
                $code = $response->response->statusCode;
                $reason = $changeresponse->response->statusReason;
                $error = 'false';
            }
        }
        catch(SoapFault $soapFault) {
            $code = -1;
            $reason = 'Error subscribing to the '.$groupName.' alert group. Please try again later.';
            $error  = 'true';
            $exceptionmessage = $soapFault->getMessage();
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );            
        }
        return getJsonResultString($code, $reason, $error, $exceptionmessage);
    }
/**
 * Returns an indicator of the derived subscription status for the user for all courses available. The indicator will be used
 * to generate a specific message about the alerts status in the UI.
 * @param string $partnerUid - the email address of the user
 * @param string $groupName - the name of the new group, will be in the form SITE(shortname)_COURSE(shortname)
 * @return string - returns one of the values "SUBSCRIBED_FOR_OTHER_COURSES", "SUBSCRIBED_FOR_THIS_COURSE",
 * "SUBSCRIBED_FOR_NO_COURSES", "NOT_SUBSCRIBED", or "ERROR"
 */
    function getAlertsStatus($partnerUid, $groupName) {
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        try {
            $response = $this->soapClient->FindGroupsForUser($this->header, $this->id, $partnerUid);
            switch($response->response->statusCode) {
                case 0: { //success
                        if(count($response->subscriptionGroups) > 0) {
                            $status =  "SUBSCRIBED_FOR_OTHER_COURSES";
                            foreach($response->subscriptionGroups as $group) {
                                if($group==$groupName) {
                                    $status =  "SUBSCRIBED_FOR_THIS_COURSE";
                                    break;
                                }
                            }
                        }
                        else {
                            $status =  "SUBSCRIBED_FOR_NO_COURSES";
                        }
                        break;
                    }
                case 325: { //user is not subscribed
                        $status =  "NOT_SUBSCRIBED";
                        break;
                    }
                default: {
                        $status =  "NOT_SUBSCRIBED";
                        break;
                    }
            }
            return $status;
        }
        catch(SoapFault $soapFault) {
            $code = -1;
            $reason = 'Error subscribing to the '.$groupName.' alert group. Please try again later.';
            $error  = 'true';
            $exceptionmessage = $soapFault->getMessage();
            error_log( "Live Alerts Error: " . $reason . " ". $exceptionmessage );

            $resultString = getJsonResultString($code, $reason, $error, $exceptionmessage);        
            return $resultString;

        }
    }
}

?>