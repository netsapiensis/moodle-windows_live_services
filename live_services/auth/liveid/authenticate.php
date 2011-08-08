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

require_once( '../../config.php' );
require_once( $CFG->dirroot . '/lib/moodlelib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );
require_once( $CFG->dirroot . '/auth/liveid/settings.php' );
require_once( $CFG->dirroot . '/auth/liveid/windowslivelogin_factory.php' );

global $DB, $PAGE, $OUTPUT, $SITE;

$PAGE->set_url('/authenticate.php');
$PAGE->set_course($SITE);

// if we are logging out, don't bother with this page
$action = @optional_param('action', '', PARAM_TEXT);
if( $action == 'logout' ) { header( "Location: $CFG->wwwroot" ); exit(); }

// Initialize the WindowsLiveLogin module.
$wll = WindowsLiveLogin_Factory::initFromMoodleConfig( $CFG );
$wll->setDebug( $DEBUG );
$userId = null;
$isConsentGranted = false;
$status = 'FAILURE';
$moodleUserName = null;
$liveId = '';

// If the user token has been cached in a site cookie, attempt to
// process it and extract the user ID.
$AccessToken = @$_COOKIE[ $COOKIE_ACCESS_TOKEN ];
$RefreshToken=@$_COOKIE[$COOKIE_Refresh_TOKEN];
if( $AccessToken )
{
    $isConsentGranted = true;
}
else
{//print_error("no");
    header("Location: ".$wll->getReturnUrl()."?return=true");
	exit();
}
if( $isConsentGranted )
{
    // get the user's Live ID
    $request ="https://apis.live.net/v5.0/me?access_token=".$AccessToken;
    $curl = new CurlLib();
    $httpHeaders="";

    $response = $curl->getRestResponse( $request, $httpHeaders );
    if($response)
    {
        $array=json_decode($response,true);
        for($i =0; $i < count($array); $i++)
        {
            //The first element in the matches array is the combination
            //of both matches.
            if($array['id'])
            {
                $cid=$array['id'];
            }
            else 
            {
                throw new Exception('No id exist.');
            }
            if($array['emails'])
            {
                for($j=0; $j<count($array['emails']);$j++)
                {
                    if($array['emails']['account'])
                    {
                        $liveId=$array['emails']['account'];
                    }
                    else
                    {
                        throw new Exception('please sign in live.com, and add you account emails info.');
                    }
                }
            }
        }
        // make sure this user is in Moodle
        $moodleUserName = $DB->get_field_sql("SELECT username FROM {user} WHERE msn='{$liveId}' and deleted=0");
    }
    else
    {
        $wll->getRefreshedToken($RefreshToken);
        header("Location: ".$wll->getReturnUrl()."?refresh=true");
    }
    if( $moodleUserName != null )
    {
        $status = 'SUCCESS';
        setcookie( $COOKIE_WLS_LIVEID, $liveId, 0, '/' );
        setcookie( $COOKIE_CONSENT_TOKEN_CID, $cid, 0, '/' );
        $wll->ExpireCookies();
    }
    else
    {
        $status = 'INVALID_USER';
    }
}

$heading = '';
$subhead = '';
$message = '';

switch( $status )
{
    case 'INVALID_USER':
        $heading = "<img src='authenticate.jpg' /><br />Windows Live Authentication";
        $subhead = 'Invalid Moodle User';
        $message = <<<LIVE_ID_NOT_IN_MOODLE
                <p>Your account has been successfully authenticated by Windows Live,
                but your Windows Live ID, <b>$liveId</b>, is not associated with a user account in this Moodle system.
                </p><p>You can <a href='logout.php'>try again</a> with a different Windows Live
                ID. If you continue to have problems, contact your Moodle system administrator.</p>
                
LIVE_ID_NOT_IN_MOODLE;
        break;


    case 'FAILURE':
        $heading = "<img src='authenticate.jpg' /><br />Windows Live Authentication";
        $subhead = 'Authentication Failed';
        $message = <<<SIGN_IN_FAIL
                <p>Windows Live was not able to authenticate your account. You can try again by <a href='logout.php'>logging out</a>
                and using a different Windows Live ID. If you continue to have problems, contact your Moodle system administrator.</p>
SIGN_IN_FAIL;
        break;

    default: //SUCCESS
        $heading = "<img src='authenticate.jpg' /><br />Windows Live Authentication";
        $subhead = "Authentication Successful";
        $message = <<<SIGN_IN_SUCCESS
                <p>Your account, <b>$liveId</b>, has been successfully authenticated by Windows Live. Please wait while you are logged into Moodle.
                If you are not automatically redirected, click the 'Continue to Moodle' button below.</p>
                <form name="authform" action="$CFG->wwwroot/login/index.php" method="post">
                    <input type="hidden" value="$moodleUserName" name="username" />
                    <input type="hidden" value="password_not_used" name="password" />
                    <input type="submit" value="Continue to Moodle" />
                </form>
                <script language="JavaScript">document.authform.submit();</script>
SIGN_IN_SUCCESS;
        break;

}
print_header('Windows Live&trade; ID Authentication'.' - '.$SITE->fullname);
?>
        <center>
            <table><tr><td width="450" align="center">
                <h3 style="margin-top:40px;text-align:center;"><?php echo $heading; ?></h3>
                <h4><?php echo $subhead; ?></h4>
                <?php echo $message; ?>
            </td></tr></table>
        </center>
        </body>
        </html>
