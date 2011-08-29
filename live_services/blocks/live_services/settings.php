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

defined('MOODLE_INTERNAL') || die;

require_login();

require_once 'lib/settingslib.php';

$settings->add(new admin_setting_html('e1', '', '', '', 'live_services.php'));

$settings->add(new admin_setting_configtext('block_live_services_appId', get_string( 'app_id', 'block_live_services' ), null, null));
$settings->add(new admin_setting_configtext('block_live_services_secretKey', get_string( 'secret_key', 'block_live_services' ), null, null));

$settings->add(new admin_setting_html('e2', '', '', '', 'email_calendar.php'));

$settings->add(new admin_setting_configcheckbox('block_live_services_useEWS', get_string( 'use_outlook_live', 'block_live_services' ), '', null));
$settings->add(new admin_setting_configtext('block_live_services_ewsServiceAccountUserName', get_string('service_account', 'block_live_services'), '', null));
$settings->add(new admin_setting_configtext('block_live_services_ewsServiceAccountPassword', get_string('password', 'block_live_services'), '', null));
$settings->add(new admin_setting_configtext('block_live_services_ewsServiceUrl', get_string('outlook_url', 'block_live_services'), '', null));
$settings->add(new admin_setting_configcheckbox('block_live_services_showEmail', get_string('show_email', 'block_live_services'), '', null));
$settings->add(new admin_setting_configcheckbox('block_live_services_showCalendar', get_string('show_calendar', 'block_live_services'), '', null));

$settings->add(new admin_setting_html('e3', '', '', '', 'live_messenger.php'));
$settings->add(new admin_setting_configcheckbox('block_live_services_showMessenger', get_string('show_messenger', 'block_live_services'), '', null));

$settings->add(new admin_setting_html('e4', '', '', '', 'bing.php'));
$settings->add(new admin_setting_configcheckbox('block_live_services_showSearch', get_string('show_search', 'block_live_services'), '', null));
$settings->add(new admin_setting_configcheckbox('block_live_services_bingSearchEnabled', get_string('enable_bing_search', 'block_live_services'), '', null));
//---- end with a hr
$settings->add(new admin_setting_html('e5', '', '', '', '<table style="width: 100%"><tr><td><hr /></td></tr></table></div>'));