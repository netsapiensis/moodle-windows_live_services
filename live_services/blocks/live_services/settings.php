<?php

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