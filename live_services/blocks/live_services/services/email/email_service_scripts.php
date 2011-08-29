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

header("Content-type: text/javascript"); ?>
<?php require_once('../../../../config.php');
GLOBAL $CFG;
?>
/*
 * toggles the expanding and collapsing of the sections within the Microsoft Live Services Plug-in for Moodle block
*/
function toggleExpandCollapse(imageId, containerId)
{
    var container = document.getElementById(containerId);
    var image = document.getElementById(imageId);
    if(container.style.display=="block")
    {
        image.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/switch_plus.gif";
        container.style.display = "none";
    }
    else
    {
        image.src = "<?php echo($CFG->wwwroot);?>/blocks/live_services/shared/images/switch_minus.gif";
        container.style.display = "block";
    }
}
