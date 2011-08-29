
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
    //define and set up autocomplete
    var MicrosoftServices = {};
    MicrosoftServices.QueryMatchContains = function(){
        var ds = new YAHOO.util.XHRDataSource("resolve_names.php");
        ds.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
        ds.responseSchema = {resultsList: "ResultSet.Result",fields:["rn"],metaFields:{query:"ResultSet.query", resolvedNames:"ResultSet.resolvedNames"}};
        // First AutoComplete
        var autoComplete = new YAHOO.widget.AutoComplete("to","autoCompleteResults",ds);
        autoComplete.queryMatchContains = true;
        autoComplete.queryMatchCase = false;
        autoComplete.minQueryLength = 3;
        autoComplete.queryDelay = 0.3;
        autoComplete.useIFrame = false;
        autoComplete.formatResult = function(resultData,query,resultMatch) {
            return "<div class=\"result\">" + resultMatch + "</div>";
        };
        autoComplete.doBeforeLoadData = function(query, response,payload) {
                YAHOO.util.Dom.get("resolvedNamesHidden").value = response.meta.resolvedNames;
                return true;
        };
        return {
            oDS: ds,
            oAC: autoComplete
        }
    }();
    var itemSelectHandler = function(type, args) {
        var oData = args[2];
        //concatenate the previously resolved names and the newly selected name
        YAHOO.util.Dom.get("to").value = YAHOO.util.Dom.get("resolvedNamesHidden").value + oData[0] + ";";
    };
    MicrosoftServices.QueryMatchContains.oAC.itemSelectEvent.subscribe(itemSelectHandler);


