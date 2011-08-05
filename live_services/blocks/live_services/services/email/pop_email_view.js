
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

        function attachKeyHandler()
        {
        document.onkeypress = function(e){
            if(window.event) e = window.event;
            keyCode = e.keyCode?e.keyCode:e.which;
            switch(keyCode)
            {
                /* k or K = previous */
                case 75:
                case 107:
                    {
                       var prevLink = document.getElementById("prevEmail");
                       if(prevLink!==null)
                            location.href = prevLink.href;
                       break;
                    }
                /* j or J = next */
                case 74:
                case 106:
                    {
                        var nextLink = document.getElementById("nextEmail");
                        if(nextLink!==null)
                            location.href = nextLink.href;
                        break;
                    }
                // ESC key
                case 27:
                    {
                        parentGrayOut(false,null);hideOverlay();
                        break;
                    }
                }
            }
        }
        /*
         * sets the target attribute of all anchor tags to blank so links contained in the email body
         * won't take over the lightbox.
         */
        function fixLinkTargets()
        {
            var emailBody = document.getElementById('emailBody');
            var links = emailBody.getElementsByTagName('a');
            if(links.length > 0)
            {
                for(i=0;i<links.length;i++)
                    {
                        links[i].setAttribute("target","_blank");
                    }
            }
        }
        /*
         * initialization function that is called when the page body loads.
         * attaches handlers and focuses the cursor to the lightbox
         */
        function initialize()
        {
            attachKeyHandler();
            fixLinkTargets();
            this.focus();
        }

