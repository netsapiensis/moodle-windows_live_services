
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


/*
 * Performs a search of a string using the Microsoft Bing search engine
 */
function searchBing()
{
    var selectedText = new String( getSelectedText() );
    selectedText = selectedText.replace( / /g, '+' );
    selectedText = Url.encode( selectedText );
    popWindow( 'http://www.bing.com/search?q=' + selectedText );
}

/*
 * Performs a search of string using Powerset
 */
function searchPowerset()
{
    var selectedText = new String( getSelectedText() );
    selectedText = selectedText.replace( / /g, '-' );
    selectedText = Url.encode( selectedText );
    if(selectedText.length > 0)
    {
        popWindow( 'http://www.powerset.com/explore/go/' + selectedText );
    }
    else
    {
    	popWindow( 'http://www.powerset.com' );
    }
}

/*
 * Event handler for the Enter key (keycode 13)
 * Launches the default search
 */
function textboxKeyPress( e )
{
    keyCode = e.keyCode?e.keyCode:e.which;
    if( keyCode == 13 )
    {
        if(document.getElementById('searchBingImage').style.display=="inline")
        {
            searchBing();
        }
        else
        {
            searchPowerset();
        }
    }
}

/*
 * Displays the search results in a new window
 */
function popWindow( url )
{
    var searchWindow = window.open( url, 'search', 'width=800, height=600, resizable=yes, scrollbars=yes' );
    searchWindow.focus();
}

/*
 * Returns the text that has been highlighted with the mouse
 */
function getSelectedText()
{
    txt = '';
    if( document.searchForm.searchFor.value == '' )
    {
        if( window.getSelection )
        {
            txt = window.getSelection();
        }
        else if( document.getSelection )
        {
            txt = document.getSelection();
        }
        else if( document.selection )
        {
            txt = document.selection.createRange().text;
        }
    }
    else
    {
        txt = document.searchForm.searchFor.value;
    }
    return txt;
}

var Url = {

	// public method for url encoding
	encode : function (string) {
		return escape(this._utf8_encode(string));
	},

	// public method for url decoding
	decode : function (string) {
		return this._utf8_decode(unescape(string));
	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace( /\r\n/g, "\n" );
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length ) {

			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}