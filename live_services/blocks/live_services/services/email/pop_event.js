
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

        /**
         * trims the spaces from a string
         * @PARAM val - the string to trim
         * @RETURN <string>
         */
        function trim(val) {
            var stringToTrim = new String(val);
            return stringToTrim.replace(/^\s+|\s+$/g,"");
        }

        var dateControl;
        var validationMessage;

        var handleSuccess = function( response )
        {
            try
            {
                var result = YAHOO.lang.JSON.parse(response.responseText);
                if(result.error=='true')
                {
                    alert(result.reason + "\n" + result.exceptionmessage);
                    YAHOO.util.Dom.get('to').focus();
                    YAHOO.util.Dom.get('sendButton').disabled = false;
                    return;
                }
            }
            catch(Exception)
            {
                alert('Unexpected error creating the event');
            }
            parentGrayOut(false,null);
            hideOverlay();
        };

        var handleFailure = function( response )
        {
            var messageText;
            if( response.responseText !== undefined )
            {
                messageText = new String();
                messageText = "Email message not sent successfully \n";
                messageText += "HTTP status: " + response.status + "\n";
                messageText += "Status code message: " + response.statusText;

            }
            else
            {
                messageText = "Event could not be created due to unknown failure";
            }
            YAHOO.util.Dom.get('sendButton').disabled = false;
            alert(messageText);
        };

        var callback =
        {
            success:handleSuccess,
            failure:handleFailure,
            argument:[]
        };
        /**
         * validates the user input before sending the AJAX request
         * @RETURNS <bool>
         */
        function validateRequest()
        {
            validationMessage = "";
            var to = new String(document.getElementById( 'to' ).value);
            if(trim(to).length==0)
                {
                    validationMessage += "The 'To' field cannot be empty\n";
                }
            var startDate = new String(document.getElementById('startDate').value);
            if(trim(startDate).length==0)
                {
                    validationMessage += "The 'Start Date' cannot be empty\n";
                }
            var startTime = document.getElementById('startTime');
            var startDateTimeString;

            if(startTime.selectedIndex < 1)
                {
                    validationMessage += "The 'Start Time' must be selected\n";
                }
            else
                {
                    startDateTimeString = startDate + ' ' + startTime.options[startTime.selectedIndex].value;
                    startDateTime = formatJavaScriptDate(startDateTimeString,true);
                }
            var endDate = new String(document.getElementById('endDate').value);
            var endDateTimeString;
            if(trim(endDate).length==0)
                {
                    validationMessage += "The 'End Date' cannot be empty\n";
                }
            var endTime = document.getElementById('endTime');
            if(endTime.selectedIndex < 1)
                {
                    validationMessage += "The 'End Time' must be selected\n";
                }
            else
                {
                    endDateTimeString = endDate + ' ' + endTime.options[endTime.selectedIndex].value;
                    endDateTime = formatJavaScriptDate(endDateTimeString,true);
                }

            try
            {
                if(isNaN(startDateTime || isNaN(endDateTime)))
                    {
                      validationMessage += "Please check the values for start date and end date. An invalid date value has beeen detected.\n";
                    }
                else
                    {
                    if(endDateTime - startDateTime < 0)
                        {
                            validationMessage += "The Start Date and Time must precede the End Date and Time.\n";
                        }
                    var now = new Date();
                    var nowDateTime = now.getTime();
                    if(startDateTime - nowDateTime < 0)
                        {
                            validationMessage += "The Start Date and Time cannot be in the past. Please select a future Start Date and Time for your event.\n";
                        }
                    }

            }
            catch(err)
            {
                validationMessage += "Please check the values for start date and end date. An invalid date value has beeen detected.\n";
            }

            if(validationMessage.length > 0)
                {
                    return false;
                }
            return true;
        }
        /**
         * sends the AJAX request to send_ews_item.php
         * @RETURNS <mixed>
         */
        function makeRequest()
        {
            if(validateRequest()==false)
            {
                alert(validationMessage);
                return false;
            }
            else
            {
                YAHOO.util.Dom.get('sendButton').disabled = true;
                var sUrl = 'send_ews_item.php';
                var to = document.getElementById( 'to' );
                var subject = document.getElementById( 'subject' );
                var body = document.getElementById( 'body' );
                var startDate = document.getElementById( 'startDate' );
                var startTime = document.getElementById( 'startTime' );
                var endDate = document.getElementById( 'endDate' );
                var endTime = document.getElementById('endTime');
                var isAllDayEvent = document.getElementById('isAllDayEvent');
                var isRecurring = false; //TODO: this could possibly be added in a future version, leaving placeholder
                var postData = "type=appointment&to=" + encodeURI( to.value ) +
                                "&subject=" + encodeURI( subject.value ) +
                                "&body=" + encodeURI( body.value ) +
                                "&startDate=" + encodeURI( startDate.value ) +
                                "&startTime=" + startTime.options[startTime.selectedIndex].value +
                                "&endDate=" + encodeURI( endDate.value )+
                                "&endTime=" + endTime.options[endTime.selectedIndex].value +
                                "&isAllDayEvent=" + isAllDayEvent.checked +
                                "&isRecurring=" + isRecurring.checked;
                YAHOO.util.Connect.asyncRequest( 'POST', sUrl, callback, postData );
            }
        }
        /**
         * converts a date returned from EWS into a date that can be understood by JavaScript
         * @PARAM ewsDate - the date returned from EWS
         * @PARAM parseDate - bool that indicates whether the date is already in date format
         * @RETURN <date>
         */
        function formatJavaScriptDate(ewsDate, parseDate)
        {
            if(ewsDate.length!=16)
                {
                    return null;
                }
            var jsDate = new String(ewsDate.substr(5,2) + '/' + ewsDate.substr(8,2) + '/' + ewsDate.substr(0,4) + ' ' + ewsDate.substr(11,5));
            if(!parseDate)
            {
                return jsDate;
            }
            else
            {
                return Date.parse(jsDate);
            }
        }

        /**
         * if IsAllDayEvent is checked, this function will select an appropriate end time based
         * on the selected start time
         * @RETURNS void
         */
        function makeAllDayEvent()
        {
            var isAllDayEvent = document.getElementById('isAllDayEvent')
            if(!isAllDayEvent.checked)
                {
                    return;
                }
            var startDate = document.getElementById('startDate');
            var startTime = document.getElementById('startTime');
            var endDate = document.getElementById('endDate');
            var endTime = document.getElementById('endTime');
            var sd = new Date();
            var ed = new Date();
            try
            {
                var dateString = new String(startDate.value.substr(5,2) + '/' + startDate.value.substr(8,2) + '/' + startDate.value.substr(0,4));
                var theDateInMilliseconds = Date.parse(dateString);
                if(!isNaN(theDateInMilliseconds))
                    {
                        sd.setTime(theDateInMilliseconds);
                    }
                startDate.value = YAHOO.util.Date.format( sd, {format: "%Y-%m-%d"} );
            }
            catch(Exception)
            {
                if(startDate.value.length > 0)
                {
                    alert("Please enter a start date in the form yyyy-mm-dd or use the calendar control to select a start date");
                }
                startDate.value = YAHOO.util.Date.format( sd, {format: "%Y-%m-%d"} );
            }

            ed.setDate(sd.getDate()+1);
            endDate.value = YAHOO.util.Date.format( ed, {format: "%Y-%m-%d"} );
            startTime.selectedIndex = 1;
            endTime.selectedIndex = 1;

        }

        YAHOO.namespace( "example.calendar" );
        
        /**
         * handles the selection of a calendar date
         * @RETURNS <void>
         */
        function mySelectHandler( type, args, obj )
        {
            var selected = args[ 0 ];
            var selDate = this.toDate(selected[ 0 ]);
            var startDate = document.getElementById( 'startDate' );
            var endDate = document.getElementById('endDate');
            var isAllDayEvent = document.getElementById('isAllDayEvent');
            if(dateControl==startDate)
                startDate.value = YAHOO.util.Date.format( selDate, {format: "%Y-%m-%d"} );
            else {
                if(dateControl==endDate)
                    endDate.value = YAHOO.util.Date.format( selDate, {format: "%Y-%m-%d"} );
                else
                    {
                       endDate.value = '';
                       startDate.value = YAHOO.util.Date.format( selDate, {format: "%Y-%m-%d"} );
                    }
            }
            if(isAllDayEvent.checked)
                {
                    makeAllDayEvent();
                }
        };

        YAHOO.example.calendar.init = function() {
            YAHOO.example.calendar.cal1 = new YAHOO.widget.Calendar( "cal1", "cal1Container" );
            YAHOO.example.calendar.cal1.selectEvent.subscribe( mySelectHandler, YAHOO.example.calendar.cal1, true );
            YAHOO.example.calendar.cal1.render();
        }

        YAHOO.util.Event.onDOMReady(YAHOO.example.calendar.init);
        /**
         * sets the default date and time for a new event
         * this could be modified by developers if they wished to use different defaults
         * these are what OWA uses.
         * @RETURNS <void>
         */
        function setDefaultDates()
        {
            var startDate = document.getElementById( 'startDate' );
            var endDate = document.getElementById('endDate');
            var today = new Date(Date());
            startDate.value = YAHOO.util.Date.format( today, {format: "%Y-%m-%d"} );
            endDate.value = YAHOO.util.Date.format( today, {format: "%Y-%m-%d"} );
            var startTime = document.getElementById('startTime');
            var endTime = document.getElementById('endTime');
            startTime.selectedIndex = 17;
            endTime.selectedIndex = 18;
            document.getElementById('subject').focus();
        }
        /**
         * attaches event to key 27 (ESC) to close lightbox
         * @RETURNS <void>
         */
        function attachKeyHandler()
        {
            document.onkeypress = function(e){
            if(window.event) e = window.event;
            keyCode = e.keyCode?e.keyCode:e.which;
            switch(keyCode)
            {
                case 27:
                    {
                        parentGrayOut(false,null);
                        hideOverlay();
                        break;
                    }
                }
            }
        }

        /**
         * initialization function for body load event
         * @RETURNS <void>
         */
        function initialize()
        {
            attachKeyHandler();
            setDefaultDates();
        }
        
        /**
         * sets focus to the "To" textbox
         * @RETURNS void
         */
        function focusTo()
        {
            YAHOO.util.Dom.get('to').focus();
        }

