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

    /**
     * A class that represents a Contact.
     * Dependency of RecServicesGroupMessage and RecServicesMessage classes.
     * Contains all fields expected by the groupDeliver method.
     */
    class RecServicesContact {
        var $from;
        var $ord;
        var $sendToContactID;
        var $to;
        var $transport;
        var $transportID;
        var $transportTypeID;

        /**
         * Class constuctor
         * @global $CFG
         * @param $from - the display name of the person, group, or system the message is sent from
         * @param $to - the email address the message is sent to
         */
        public function __construct($from, $to) {
            global $CFG;
            require_once( $CFG->dirroot . '/blocks/live_services/services/alerts/config.php');
            $alertsConfig = new AlertsConfig();
            $this->from = $from;
            $this->ord = 0;
            $this->sendToContactID = 0;
            $this->to = $to;
            $this->transport = $alertsConfig->transport_type;
            $this->transportID = 0;
            $this->transportTypeID = 0;
        }
    }

?>