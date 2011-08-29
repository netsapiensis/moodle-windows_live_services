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

/**
 * The MessengerService class renders the Messenger portion of the Microsoft Live Services Plug-in for Moodle block
 * This should only be shown if the user is viewing a course
 */
class MessengerService
{
    private $_classmates = null;
    private $_teachers = null;

    /**
     * Class constructor (not currently implemented)
     */
    function __construct()
    {
    }

    /**
     * Renders the HTML for the Messenger portion of the Microsoft Live Services Plug-in for Moodle block
     * @global <array> $CFG - the global configuration array
     * @param <int> $courseId - the id of the course being viewed
     * @param <array> $contacts - the array of messenger contacts for this user
     * @param <bool> $isTeacher - true if the user is a teacher for the course being viewed, false if the user
     * is not a teacher for this course
     * @return <string> (HTML) 
     */
    function Render( $courseId, $contacts, $isTeacher)
    {
        try
        {
        //make sure this code is only executed from the course module
        $currentUrl = $_SERVER[ 'REQUEST_URI' ];
        $urlSearchText = '/course/';
        if( substr_count( $currentUrl, $urlSearchText ) == 0 )
        {
            return '';
        }

        global $CFG;
        $messengerServicePath = "$CFG->wwwroot/blocks/live_services/services/messenger";
        $messengerActionUrl = "$messengerServicePath/messenger_action.php?id=$courseId";
        if( $isTeacher )
        {
            $nameForClassmates = getLocalizedString( 'messengerStudents' );;
        }
        else
        {
            $nameForClassmates = getLocalizedString( 'messengerClassmates' );
        }
        $messengerClassmatesHeader = $nameForClassmates;
        $messengerHeader = getLocalizedString( 'messengerHeader' );
        $messengerTeachersHeader = getLocalizedString( 'messengerTeachersHeader' );
        $messengerIconAltText = getLocalizedString( 'messengerIconAltText' );

        $liveId = @$_COOKIE[ 'wls_liveId' ];
        $this->CreateUserArraysByRole( $courseId, $contacts, $liveId );

        $onlineClassmatesHtml = $this->RenderUsers( $this->_classmates, $nameForClassmates, $messengerServicePath );
        $onlineTeachersHtml = $this->RenderUsers( $this->_teachers, 'Teachers', $messengerServicePath );

        $messengerTeachersHeader .= is_null($this->_teachers)?'&nbsp;(0)':'&nbsp;('.count($this->_teachers).')';
        $messengerClassmatesHeader .= is_null($this->_classmates)?'&nbsp;(0)':'&nbsp;('.count($this->_classmates).')';
        $messengerServiceHtml =  <<<MESSENGER_SERVICE
            <h3 id="msmMessengerHeader" class="msm_h3" onclick="toggleExpandCollapse('msmMessengerImage','msmMessenger');"><table><tr><td  class="logo"><img src="$CFG->wwwroot/blocks/live_services/services/messenger/messenger_icon.png"/></td><td class="caption">Messenger</td><td class="expandCollapse"><img id="msmMessengerImage" src="$CFG->wwwroot/blocks/live_services/shared/images/switch_minus.gif"></td></tr></table></h3>
            <div class="msm_collapsible" id="msmMessenger" style="display:block">
            <h4 class='msm_h4' onclick='toggleExpandCollapse("msmTeachersImage","msmTeachersList");'>&nbsp;$messengerTeachersHeader&nbsp;&nbsp;<img id="msmTeachersImage" src="$CFG->wwwroot/blocks/live_services/shared/images/switch_minus.gif"></h4>
            <ul id="msmTeachersList" class='live_service_list' style="display:block;">
                $onlineTeachersHtml
            </ul>
            <h4 class='msm_h4' onclick='toggleExpandCollapse("msmClassmatesImage","msmClassmatesList");'>&nbsp;$messengerClassmatesHeader&nbsp;&nbsp;<img id="msmClassmatesImage" src="$CFG->wwwroot/blocks/live_services/shared/images/switch_minus.gif"></h4>
            <ul id="msmClassmatesList" class='live_service_list' style='display:block;'>
                $onlineClassmatesHtml
            </ul>
            </div>
MESSENGER_SERVICE;

        $javaScript = '<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/live_services/services/messenger/messenger_scripts.php"></script>';

        return $messengerServiceHtml.$javaScript;
        }
        catch(Exception $exc)
        {
            error_log( "Messenger Service: " . $exc->getMessage() );
        }
    }

    /**
     * Splits the contacts array into teachers and students
     * @param <int> $courseId - the id of the course being viewed
     * @param <array> $contacts - an array of all messenger contacts
     * @param <string> $liveId - the Live ID of the current user
     */
    private function CreateUserArraysByRole( $courseId, $contacts, $liveId )
    {
	global $CFG, $DB;	    
        $this->_classmates = array();
        $this->_teachers = array();

        $sql = "SELECT u.id, u.firstname, u.lastname, u.msn, ra.roleid
                FROM {context} cx
                LEFT OUTER JOIN {role_assignments} ra ON cx.id = ra.contextid AND ra.roleid IN ( '3', '4', '5' )
                LEFT OUTER JOIN {user} u ON ra.userid = u.id
                WHERE cx.contextlevel = '50' AND cx.instanceid = '$courseId' AND u.msn <> '$liveId'
                ORDER BY ra.roleid, u.firstname, u.lastname";

        $result = $DB->get_records_sql($sql);
        foreach($result as $row)
        {
            $msn = $row->msn;
            if( strlen( $msn ) > 0 )
            {
                $roleId = $row->roleid;
                if( $roleId == '5' )
                {
                    // student = 5, only add classmates who are in your Messenger contacts list
                    if( in_array( $msn, $contacts ) )
                    {
                        array_push( $this->_classmates, $msn . '|' . $row->firstname . ' ' . $row->lastname);
                    }
                }
                else
                {
                    // if you want to restrict display to teachers who are in the user's contact list,
                    // add the same "if ( in_array($email, $contacts))" block as the one provided for students
                    // teacher = 3, teacher assistant = 4
                    array_push( $this->_teachers, $msn . '|' . $row->firstname . ' ' . $row->lastname );
                }              
            }
        }

    }

    /**
     * Renders the icons and links for each messenger contact
     * @param <array> $users - an array of teachers or students
     * @param <string> $userType - teachers of students, used in the 'No contacts found' message
     * @param <string> $messengerServicePath - the path to the current directory
     * @return <string> (HTML)
     */
    private function RenderUsers( $users, $userType, $messengerServicePath )
    {
        $returnHtml = '';
        if( count( $users ) > 0 )
        {
            for( $i = 0; $i < count( $users ); $i++ )
            {
                $row = explode( '|', $users[ $i ] );
                $email = $row[ 0 ];
                $name = $row[ 1 ];
                if( strlen( $name ) > 20 ) { $name = substr( $name, 0, 20 ) . '...'; }
                //backing out msnim by request, 03/30/2009 - mf
                //if(getUserBrowser($_SERVER['HTTP_USER_AGENT'])=='ie')
                //{
                    //$href = 'msnim:chat?contact='.$email;
                //}
                //else
                //{
                    $href = "'javascript:void window.open(\"$messengerServicePath/popIM.php?invitee=$email\", \"messengerChat\",\"menubar=1,resizable=1,scrollbars=0,width=450,height=450\");'";
                    $src = "'http://messenger.services.live.com/users/".$email."/presenceimage/?mkt=en-US'";
                //}
                $returnHtml .= "<li><img style='border:none; vertical-align: middle; padding: 0px;' width='16' height='16'
                        src=$src onerror='this.src=\"$messengerServicePath/status_unknown.png\";' title='online status' id='presence$i' /><a
                        target='_parent' href=$href>$name</a></li>";
            }
        }
        else
        {
            $noneFoundMessage = getLocalizedString( 'messengerNo' . $userType . 'InContacts' );
            $returnHtml .= "<li>" . $noneFoundMessage . "</li>";
        }
        return $returnHtml;
    }
}
?>
