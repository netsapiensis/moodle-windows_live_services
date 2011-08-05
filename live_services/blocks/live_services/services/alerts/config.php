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

require_once($CFG->dirroot.'/config.php');

/**
 * Class that stores configuration settings for Alerts-related classes.
 * These are settings that would rarely, if ever, be changed.
 */
 class AlertsConfig {
     var $subscription_url;
     var $message_url;
     var $subscription_wsdl;
     var $message_wsdl;
     var $transport_type;
     var $return_url;
     var $version;
     var $language_locale;
     var $manage_alerts_url;


/**
 * Class constructor. Member fields are set upon construction
 * @global <type> $CFG
 */
     public function __construct() {
         global $CFG;
         $this->subscription_url = 'http://services.alerts.live-ppe.com/axis/services/Subscription';
         $this->message_url = 'http://services.alerts.live-ppe.com/axis/services/Message';
         $this->subscription_wsdl = 'http://services.alerts.live-ppe.com/axis/services/Subscription?wsdl';
         $this->message_wsdl = 'http://services.alerts.live-ppe.com/axis/services/Message?wsdl';
         $this->transport_type = 'MSNA';
         $this->return_url = $CFG->wwwroot.'/blocks/live_services/services/alerts/subscribe_pop.php';
         $this->version = '1.0';
         $this->language_locale = "EN-US";
         $this->manage_alerts_url = "http://alerts.live.com/Alerts/default.aspx";
     }
 }

?>