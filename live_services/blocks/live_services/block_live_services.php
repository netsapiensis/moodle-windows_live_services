<?php

/* * *****************************************************************************
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

 * ***************************************************************************** */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//TODO remove before production deployment
if (@$_SESSION['wls_UAT_authorization'] == null) {
    if (file_exists('c:\\is_uat.txt')) {
        die('This UAT site has restricted access.');
    }
}

require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * The block_live_services class defines the Microsoft Live Services Plug-in for Moodle block
 */
class block_live_services extends block_base {

    /**
     * The initialization function for the block
     */
    function init() {

        $this->blockname = get_class($this);
        $this->title = get_string('blockname', $this->blockname);
    }

    /**
     * Provides cron services for the Microsoft Live Services Plug-in for Moodle block
     * @global <array> $CFG - the global configuration array
     */
    function cron() {
        global $CFG;
        include($CFG->dirroot . '/blocks/live_services/cron.php');
    }

    /**
     * Renders the HTML content for the entire block
     * @return <string> (HTML)
     */
    function get_content() {
        // this function is called many times so stop re-entry
        if ($this->content != NULL) {
            return $this->content;
        }

        // check to see if this is a course page
        // default to site course (courseid=1)
        global $COURSE;
        $courseId = $COURSE->id;
        
        // content rendering was moved to an external file to increase code readablilty
        include "render_content.php";

        // store the value and return the content
        $this->content = new stdClass;
        $this->content->text = $content_html;
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Only allow one instance of this block and don't allow it to be configurable except
     * by an admin through config_global.html
     * @return <bool>
     */
    function instance_allow_config() {
        return false;
    }

    function specialization() {
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        } else {
            $this->config->title = '';
        }

        if (empty($this->config->text)) {
            $this->config->text = '';
        }
    }

    /**
     * Only allow one instance of this block and don't allow it to be configurable except
     * by an admin through config_global.html
     * @return <bool>
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Only allow one instance of this block and don't allow it to be configurable except
     * by an admin through config_global.html
     * @return <bool>
     */
    function has_config() {
        return true;
    }

    /**
     * Save configuration values
     * @param <array> $data
     * @return <bool>
     */
    function config_save($data) {
        // Default behavior: save all variables as $CFG properties
        foreach ($data as $name => $value) {
            set_config($name, $value);
        }
        return true;
    }

    /**
     * Show the block header
     * @return <bool>
     */
    function hide_header() {
        return false;
    }

    /**
     * Set the preferred width
     * @return <int>
     */
    function preferred_width() {
        // The preferred value is in pixels
        return 200;
    }

    /**
     * defines default html attributes for the block
     * @return <array>
     */
    function html_attributes() {
        return array
            (
            'class' => 'block_live_services block block_with_controls'
        );
    }

    /**
     * not currently implemented
     * @return <bool>
     */
    function _self_test() {
        return true;
    }
    
    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }
    
    function instance_can_be_docked() {
        return true;
    }

}
