<!--

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

-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <?php
    //TODO: localize this file
    require_once( '../../config.php' );
    require_once($CFG->dirroot.'/blocks/live_services/shared/utils.php');
    // Start the output.
    print_header(getLocalizedString('privacy'));
    print_simple_box_start();
    $privacyUrl = '<a href="http://privacy.microsoft.com/en-us/fullnotice.mspx" target="_blank" >[[privacyUrlText]]</a>';
    $privacyUrl = str_replace('[[privacyUrlText]]',getLocalizedString('privacyUrlText'),$privacyUrl);
    $privacyPolicyText = getLocalizedString('privacyPolicyText');
    $privacyPolicyText = str_replace('[[privacyUrl]]',$privacyUrl,$privacyPolicyText);
    ?>

    <h1 style="font-size:1.3em"><?php echo(getLocalizedString('privacyPolicyTitle'));?></h1>
    <hr/>
      <?php echo($privacyPolicyText);?>
      <?php print_simple_box_end(); ?>
      <?php close_window_button(); ?>
  </body>
  </html>
