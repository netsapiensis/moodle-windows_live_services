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
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );
require_once ($CFG->dirroot . '/blocks/live_services/services/alerts/recservicesgroupmessage.class.php');
require_once($CFG->dirroot . '/blocks/live_services/services/alerts/recservicescontact.class.php');

/**
 * Class that represents an Alert message.
 */
class Message {

    var $id;
    var $header;
    var $soapClient;

/**
 * Class constructor. Values needed by SOAP Client calls are set at time of construction.
 * @global $CFG
 */
    public function __construct() {
        global $CFG;
        if(!isset($CFG->block_live_services_alertsPinId) || !isset($CFG->block_live_services_alertsPinPassword)) {
            throw new Exception("Pin or password not set. The administrator must configure this in the config_global page for the block.");
        }
        $pin = $CFG->block_live_services_alertsPinId;
        $password = $CFG->block_live_services_alertsPinPassword;
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/config.php');
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/alerts_helper.php');
        $alertsConfig = new AlertsConfig();
        $timestamp = AlertsHelper::GetWindowsLiveAlertsDateTimeFormat();
        $timestampGuid = AlertsHelper::MakeGUID($timestamp, false);
        $this->header = array('messageID'=>$timestamp.'.'.$timestampGuid.'.'.$pin,'version'=>$alertsConfig->version,'timestamp'=>$timestamp);
        $this->id = array('PINID'=>$pin,'PW'=>$password);
        $this->soapClient = new SoapClient($alertsConfig->message_wsdl,array('features'=>'SOAP_SINGLE_ELEMENT_ARRAYS'));
    }
/**
 * Uses the SOAP Client to send a message to an alert subscription group (course participants who are registered for alerts).
 * @global $CFG
 * @param RecServicesGroupMessage $groupMessage - contains the message content and delivery details
 * @return bool - true if message successfully sent, false if not.
 */
    public function groupDeliver($groupMessage) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/live_services/services/alerts/config.php');
        $alertsConfig = new AlertsConfig();
        $code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
        try {
            $response = $this->soapClient->__doRequest($this->buildGroupMessage($groupMessage),$alertsConfig->message_url,'',1); //(soap, url, action, version)
            $xml = simplexml_load_string($response);
            $xml->registerXPathNamespace( "soapenv", "http://schemas.xmlsoap.org/soap/envelope/" );
            $xml->registerXPathNamespace( "ns1", "http://soapservices.messagecast.net" );
            $xml->registerXPathNamespace( "ns2", "http://messagecast.net" );
            $statusCodeNode = $xml->xpath("/soapenv:Envelope/soapenv:Body/ns1:GroupDeliverResponse/GroupDeliverReturn/response/statusCode");
            $statusReasonNode = $xml->xpath("/soapenv:Envelope/soapenv:Body/ns1:GroupDeliverResponse/GroupDeliverReturn/response/statusReason");
            if( count($statusCodeNode) > 0 ) {
                $code = $statusCodeNode[0];
                $reason = $statusReasonNode[0];
                if($code=="0") {
                    $error = 'false';
                }
                else {
                    $error = 'true';
                    error_log( "Live Alerts Error: " . $reason );   			                    
                }
            }
            else {
                $code = '-1';
                $reason = 'Unable to send group alert: status code not returned';
                $error = 'true';
                error_log( "Live Alerts Error: " . $reason ); 
            }
        }
        catch(Exception $exc) {
            	handleException($exc);		
                $code = '-1';
                $reason = 'Unable to send group alert: communication with alerts service failed.';
                $error = 'true';
                $exceptionmessage = str_replace('"','\"',$exc->getMessage());
        }
        $resultString = getJsonResultString($code, $reason, $error, $exceptionmessage);
        return $resultString;
    }

/**
 * Called by the block's cron.php file to automatically send alerts when event-driven modules are updated
 * @global $CFG
 * @return none
 */

    public function runCronGroupDeliver() {
        global $CFG, $DB;
        //$sql = "SELECT lastcron FROM {$CFG->prefix}block WHERE name = 'live_services' AND cron = '1'";
        $blockRecord = $DB->get_record( "block", array('name' => 'live_services', 'cron' => '1') );
        $lastCron = $blockRecord->lastcron;
        $this->groupDeliverForModules(array('quiz','assignment',''), $lastCron);
        $timenow = time();
        $blocks = $DB->get_records( "block", array("name" => "live_services") );
        foreach ($blocks as $block) {
            $block->lastcron = $timenow;
            $DB->update_record( "block", $block );
        }
        
    }

    /**
     * Iterates thought the modules in the $modules array and send alerts for related events
     * @global $CFG
     * @param <array> $modules: an array of event-driven modules
     * @param <timestamp> $lastCron: the timestamp of the last time this block's cron was run
     */
    private function groupDeliverForModules($modules, $lastCron) {
        global $CFG, $DB;
        foreach($modules as $module) {
            $sql = "SELECT e.name, e.timestart, e.timeduration, e.courseid, e.description FROM {event} e WHERE timemodified > $lastCron AND e.modulename='$module' AND e.courseid > 1";
            $records = $DB->get_records_sql($sql);
            if($module==='') {
                $module = 'calendar event';
            }
            if($records!==FALSE && count($records) > 0) {
                foreach($records as $record) {
                    $name = $record->name;
                    $courseId = $record->courseid;
                    $timeStart = userdate($record->timestart);
                    $duration = ((string)((int)$record->timeduration)/60).' minutes';
                    $groupName = AlertsHelper::GetGroupNameByCourseId($courseId);
                    $groupMessageContent = strtoupper($module.' alert: ');
                    $groupMessageBody = 'The '.$name. ' calendar event has been scheduled for '.$duration.' beginning '.$timeStart;
                    $groupMessageContent.=$groupMessageBody;
                    $fromContact = array(new RecServicesContact('Live Alerts',''));
                    $groupMessage = new RecServicesGroupMessage('',$groupMessageContent, '', $fromContact,$groupName,$groupMessageBody,$groupMessageBody,$groupMessageBody);
                    try {
                        $this->groupDeliver($groupMessage);
                    }
                    catch(Exception $exc) {
                        mtrace('---Exception occurred during group delivery of message--');
                        mtrace($exc);
                    }
                }
            }
            else {
                mtrace(' --- No new alerts have been sent ---');
            }
        }
    }

/**
 * Builds a SOAP envelope that will be sent in the groupDeliver service method
 * @param RecServicesGroupMessage $groupMessage - contains the message content and delivery details
 * @return string - the SOAP envelope
 */
    private function buildGroupMessage($groupMessage) {
        try {
            $soap =
'<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
        <q1:GroupDeliver xmlns:q1="http://soapservices.messagecast.net">
            <h href="#id1"/>
            <ID href="#id2"/>
            <gm href="#id3"/>
        </q1:GroupDeliver>
        <q2:RecServicesHeader id="id1" xsi:type="q2:RecServicesHeader" xmlns:q2="messagecast.net">
            <messageID xsi:type="xsd:string">'.$this->header['messageID'].'</messageID>
            <timestamp xsi:type="xsd:string">'.$this->header['timestamp'].'</timestamp>
            <version xsi:type="xsd:string">'.$this->header['version'].'</version>
        </q2:RecServicesHeader>
        <q3:RecServicesIdentification id="id2" xsi:type="q3:RecServicesIdentification" xmlns:q3="messagecast.net">
            <PINID xsi:type="xsd:int">'.$this->id['PINID'].'</PINID>
            <PW xsi:type="xsd:string">'.$this->id['PW'].'</PW>
        </q3:RecServicesIdentification>
        <q4:RecServicesGroupMessage id="id3" xsi:type="q4:RecServicesGroupMessage" xmlns:q4="messagecast.net">
            <actionURL xsi:type="xsd:string">'.$groupMessage->actionURL.'</actionURL>
            <content xsi:type="xsd:string">'.$groupMessage->content.'</content>
            <emailMessage xsi:type="xsd:string">'.$groupMessage->emailMessage.'</emailMessage>
            <fromContacts href="#id4"/>
            <groupName xsi:type="xsd:string">'.$groupMessage->groupName.'</groupName>
            <locale xsi:type="xsd:string">'.$groupMessage->locale.'</locale>
            <messengerMessage xsi:type="xsd:string">'.$groupMessage->messengerMessage.'</messengerMessage>
            <mobileMessage xsi:type="xsd:string">'.$groupMessage->mobileMessage.'</mobileMessage>
            <superToastMessage xsi:type="xsd:string">'.$groupMessage->superToastMessage.'</superToastMessage>
        </q4:RecServicesGroupMessage>
        <q5:Array id="id4" q5:arrayType="q6:RecServicesContact[1]" xmlns:q5="http://schemas.xmlsoap.org/soap/encoding/" xmlns:q6="messagecast.net">
            <Item href="#id5"/>
        </q5:Array>
        <q7:RecServicesContact id="id5" xsi:type="q7:RecServicesContact" xmlns:q7="messagecast.net">
            <SGID xsi:nil="true"/>
            <from xsi:type="xsd:string">'.$groupMessage->fromContacts[0]->from.'</from>
            <ord xsi:type="xsd:int">0</ord>
            <sendToContactID xsi:type="xsd:int">0</sendToContactID>
            <to xsi:nil="true"/>
            <transport xsi:type="xsd:string">'.$groupMessage->fromContacts[0]->transport.'</transport>
            <transportID xsi:type="xsd:int">0</transportID>
            <transportTypeID xsi:type="xsd:int">0</transportTypeID>
        </q7:RecServicesContact>
    </s:Body>
</s:Envelope>';
            return $soap;
        }
        catch(Exception $exc) {
            throw new Exception("Unable to build message XML");
        }
    }
}

?>