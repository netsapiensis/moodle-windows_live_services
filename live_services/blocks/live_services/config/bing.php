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

global $CFG;
$searchScreenshot = "$CFG->wwwroot/blocks/live_services/services/search/screenshot.png";

?>
</td>
</tr>
<tr><td colspan="3"><hr /></td></tr>
<tr>
    <td colspan="2">
        <p><b>Powerset&nbsp;and&nbsp;Bing&reg;&nbsp;Search</b><br />
        Provide your users with the ability to highlight onscreen text and search the web for related information using Powerset and/or the new Bing search engine from Microsoft. A textbox is also provided for user input so any topic can be searched from Moodle.<br /><br/>
        If the 'Enable Bing Search' checkbox is checked, Bing will be the default search. Disabling Bing Search will make Powerset the default search.
    </td>
    <td class="screenshot" rowspan="2">
        <img class="screenshot"  src="<?php echo $searchScreenshot ?>" title="Search Screenshot" />
        <span class="imageCaption">Figure 5: Search block with Bing and Powerset enabled</span>
    </td>
</tr>
</table>
