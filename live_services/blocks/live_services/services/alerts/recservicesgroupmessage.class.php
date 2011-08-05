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


/**
 * A class that represents a Message that will be delivered to an alert subscription group (the members
 * of a course that are subscribed to receive alerts).
 */
    class RecServicesGroupMessage {
        var $actionURL;
        var $fromContacts;
        var $groupName;
        var $content;
        var $emailMessage;
        var $messengerMessage;
        var $mobileMessage;
        var $locale;
        var $superToastMessage;

/**
 * Class constructor
 * @global $CFG
 * @param string $actionURL - a URL embedded in the message
 * @param string $content - the subject of the message
 * @param string $emailMessage - the body of the message
 * @param array(RecServicesContact) $fromContacts - an array of contacts listed as the message sender
 * @param string $groupName - the name of the alerts subscription group the message will be sent to
 * @param string $messengerMessage - the message that will be sent to Windows Live messenger recipients
 * @param string $mobileMessage - the message that will be sent to mobile recipients
 * @param string $superToastMessage - a message that contains additional branding
 * NOTE: in this version, all recipients (email, mobile, messenger) will receive the same version of the message. We are
 * only sending a simple text message. The actionURL is not sent.
 */
        public function __construct($actionURL, $content, $emailMessage, $fromContacts, $groupName, $messengerMessage, $mobileMessage,$superToastMessage) {
            global $CFG;
            require_once( $CFG->dirroot . '/blocks/live_services/services/alerts/config.php');
            $alertsConfig = new AlertsConfig();
            $this->actionURL = $actionURL;
            $this->content = $content;
            $this->emailMessage = $emailMessage;
            $this->fromContacts = $fromContacts;
            $this->groupName = $groupName;
            $this->locale = $alertsConfig->language_locale;
            $this->messengerMessage = $messengerMessage;
            $this->mobileMessage = $mobileMessage;
            $this->superToastMessage = $superToastMessage;                        
        }
    }

?>