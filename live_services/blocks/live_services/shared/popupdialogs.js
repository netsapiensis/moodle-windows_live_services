
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

    /*
     * grays out the page by adding a gray div over the top of the entire body
     */
  function grayOut(vis, opt) {
      // Pass true to gray out screen, false to ungray
    // options are optional.  This is a JSON object with the following (optional) properties
    // opacity:0-100
    // Lower number = less grayout higher = more of a blackout
    // zindex: #
    // HTML elements with a higher zindex appear on top of the gray out
    // bgcolor: (#xxxxxx)
    // Standard RGB Hex color code
    // grayOut(true, {'zindex':'50', 'bgcolor':'#0000FF', 'opacity':'70'});
    // Because options is JSON opacity/zindex/bgcolor are all optional and can appear
    // in any order.  Pass only the properties you need to set.
    var options = opt || {};
    var zindex = options.zindex || 50;
    var opacity = options.opacity || 70;
    var opaque = (opacity / 100);
    var bgcolor = options.bgcolor || '#999999';
    var dark=document.getElementById('darkenScreenObject');
    if (!dark) {
    // The dark layer doesn't exist, it's never been created.  So we'll
    // create it here and apply some basic styles.
    // If you are getting errors in IE see: http://support.microsoft.com/default.aspx/kb/927917
    var tbody = document.getElementsByTagName("body")[0];
    var tnode = document.createElement('div');
    // Create the layer.
    tnode.style.position='absolute';
    // Position absolutely
    tnode.style.top='0px';
    // In the top
    tnode.style.left='0px';
    // Left corner of the page
    tnode.style.overflow='hidden';
    // Try to avoid making scroll bars
    tnode.style.display='none';
    // Start out Hidden
    tnode.id='darkenScreenObject';
    // Name it so we can find it later
    tbody.appendChild(tnode);
    // Add it to the web page
    dark=document.getElementById('darkenScreenObject');
    // Get the object.
    }
    if (vis) {
    // Calculate the page width and height
    var pageWidth, pageHeight;
    pageWidth='100%';
    pageHeight = document.body.scrollHeight+'px';
            //set the shader to cover the entire page and make it visible.
            dark.style.opacity=opaque;
            dark.style.MozOpacity=opaque;
            dark.style.filter='alpha(opacity='+opacity+')';
            dark.style.zIndex=zindex;
            dark.style.backgroundColor=bgcolor;
            dark.style.width= pageWidth;
            dark.style.height= pageHeight;
            dark.style.display='block';
        } else {     dark.style.display='none';  }}
    
    
    function parentGrayOut(vis, opt) {
      // Pass true to gray out screen, false to ungray
    // options are optional.  This is a JSON object with the following (optional) properties
    // opacity:0-100
    // Lower number = less grayout higher = more of a blackout
    // zindex: #
    // HTML elements with a higher zindex appear on top of the gray out
    // bgcolor: (#xxxxxx)
    // Standard RGB Hex color code
    // grayOut(true, {'zindex':'50', 'bgcolor':'#0000FF', 'opacity':'70'});
    // Because options is JSON opacity/zindex/bgcolor are all optional and can appear
    // in any order.  Pass only the properties you need to set.
    var options = opt || {};
    var zindex = options.zindex || 50;
    var opacity = options.opacity || 70;
    var opaque = (opacity / 100);
    var bgcolor = options.bgcolor || '#666666';
    var dark=window.parent.document.getElementById('darkenScreenObject');

    if (!dark) {
    // The dark layer doesn't exist, it's never been created.  So we'll
    // create it here and apply some basic styles.
    // If you are getting errors in IE see: http://support.microsoft.com/default.aspx/kb/927917
    var tbody = window.parent.document.getElementsByTagName("body")[0];
    var tnode = window.parent.document.createElement('div');
    // Create the layer.
    tnode.style.position='absolute';
    // Position absolutely
    tnode.style.top='0px';
    // In the top
    tnode.style.left='0px';
    // Left corner of the page
    tnode.style.overflow='hidden';
    // Try to avoid making scroll bars
    tnode.style.display='none';
    // Start out Hidden
    tnode.id='darkenScreenObject';
    // Name it so we can find it later
    tbody.appendChild(tnode);
    // Add it to the web page
    dark=window.parent.document.getElementById('darkenScreenObject');
    // Get the object.
    }
    if (vis) {
    // Calculate the page width and height
    var pageWidth, pageHeight;
    pageWidth='100%';
    pageHeight = document.body.scrollHeight+'px';
            //set the shader to cover the entire page and make it visible.
            dark.style.opacity=opaque;
            dark.style.MozOpacity=opaque;
            dark.style.filter='alpha(opacity='+opacity+')';
            dark.style.zIndex=zindex;
            dark.style.backgroundColor=bgcolor;
            dark.style.width= pageWidth;
            dark.style.height= pageHeight;
            dark.style.display='block';
        } else {     dark.style.display='none';  }
    }

        function hideOverlay()
        {
            var overlay = window.parent.document.getElementById('lightboxOverlay');
            if(overlay)
                {
                    overlay.style.visibility='hidden';
                    iframe = window.parent.document.getElementById('popupIFrame');
                    if(iframe)
                        {
                            iframe.src = "";
                        }
                }
        }

