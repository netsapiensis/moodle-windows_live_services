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

require_once( $CFG->dirroot . '/config.php' );
/**
 * The SearchService class renders the Bing and Powerset search. This is available even if the user has not logged into Windows Live
 */
class SearchService
{
    /**
     * Class constructor, not currently implemented
     */
    function __construct()
    {
    }

    /**
     * Renders the HTML for the Bing and Powerset search.
     * @global <array> $CFG - the global configuration array
     * @return <string> (HTML)
     */
    function Render()
    {
        global $CFG;
        $searchHeader = getLocalizedString( 'searchHeader' );
        $searchBingAltText = getLocalizedString( 'searchBingAltText' );
        $searchPowersetAltText = getLocalizedString( 'searchPowersetAltText' );
        $bingSearchEnabled = (bool)(is_null(@$CFG->block_live_services_bingSearchEnabled)?0:$CFG->block_live_services_bingSearchEnabled);
        $searchBingDisplay = $bingSearchEnabled?'inline':'none';


        $searchServicePath = "$CFG->wwwroot/blocks/live_services/services/search";

        return <<<SEARCH_SERVICE
        <script type='text/javascript' src='$searchServicePath/search.js'></script>

        <div class='live_service_div' id="searchServiceContainer">
            <form name='searchForm' action='' onsubmit='return false;'>
                <p class='live_service_text'><b>$searchHeader</b></p>
                <table width='100%' style='margin:0'><tr><td><div style="line-height:28px">
                <input type='text' size='15' style='width: 95%;' name='searchFor'
                        onkeypress='return textboxKeyPress( event );' /></div></td><td width='1%'><img id='searchBingImage'
                        title='$searchBingAltText' src='$searchServicePath/bing_logo.png'
                        onclick='searchBing()' style='vertical-align: text-bottom;
                        cursor:pointer;display:$searchBingDisplay' /></td><td width='1%'><img title='$searchPowersetAltText'
                        src='$searchServicePath/powerset_logo.png' onclick='searchPowerset()'
                        style='vertical-align: text-bottom; cursor:pointer;' /></td></tr></table>
            </form>
            <div style='clear:both'></div>
        </div>
        <div style='clear:both'></div>
SEARCH_SERVICE;

    }
}
?>
