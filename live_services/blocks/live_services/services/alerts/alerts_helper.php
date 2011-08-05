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
 * A library of static functions used by the Subscription and Message classes.
 */
class AlertsHelper {
    /**
     * Used to convert a datetime to the format expected by the Windows Live Alerts service methods
     * @return string
     */
    static function GetWindowsLiveAlertsDateTimeFormat() {
        return date("Y\-m\-d\TG:i:s.uP");
    }

    /**
     * Used to convert a date to the format expected by the Windows Live Alerts service methods
     * @return string
     */
    static function GetWindowsLiveAlertsDateFormat() {
        return date("Y\-m\-d");
    }

    /**
     * Creates a GUID from the string input using the MD5 function
     * @param string $str - the input string
     * @param bool $includeHyphen - outputs the GUID with hyphens if true, without hyphens if false.
     * @return string
     */
    static function MakeGUID ($str, $includeHyphen) {
        if($includeHyphen==true) {
            $h = "-"; //-- HYPHEN
        }
        else {
            $h = '';
        }
        $gMd5 = md5($str);
        $guidTag = substr($gMd5,0,8) . $h . substr($gMd5,7,4) . $h .
        substr($gMd5,11,4) . $h . substr($gMd5,15,4) . $h . substr($gMd5,19,12);
        return $guidTag;
    }

/**
 * Returns the GroupName in the format SITE(shortname)_COURSENAME
 * @global $SITE - the global SITE variable
 * @param int $courseId - the ID of the course
 * @return string
 */
    static function GetGroupNameByCourseId($courseId) {
        global $SITE;
        global $DB;
        $course = $DB->get_record( "course", array("id" => $courseId) );
        if(!$course ) {
            throw new Exception('Course is misconfigured');
        }

        $groupName = $SITE->shortname.'_'.$course->shortname;
        return $groupName;
    }

/**
 * Returns the GroupDescription, which is synonomous with course fullname
 * @param int $courseId - the ID of the course
 * @return string
 */
    static function GetGroupDescriptionByCourseId($courseId) {
        global $DB;
        $course = $DB->get_record( "course", array("id" => $courseId) );
        if(!$course ) {
            throw new Exception('Course is misconfigured');
        }
        $groupDescription = $course->fullname;
        return $groupDescription;
    }
}

?>