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

// Enables debugging through PHP's error_log mechanism.
$DEBUG = false;

// Application key file, should be stored in an area that cannot be
// accessed by the Web.
$KEYFILE = 'webauth-key.xml';

//$COOKIE_WEB_AUTH_TOKEN = 'wls_webAuthToken';
$COOKIE_ACCESS_TOKEN = 'wls_AccessToken';
$COOKIE_CONSENT_TOKEN_CID = 'wls_consentToken_cid';
$COOKIE_Refresh_TOKEN = 'wls_RefreshToken';
$COOKIE_WLS_LIVEID = 'wls_liveId';

// Landing pages to use after processing login and logout respectively.
$LOGIN = 'authenticate.php';
$LOGOUT = 'authenticate.php';

?>
