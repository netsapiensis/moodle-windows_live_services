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

    /**
     * Returns the inner HTML from an XHTML node
     * @param <string> $xpathContext - the XHTML to search
     * @param <string> $xpath - the xpath query to the node
     * @return <string> (XHTML)
     */
    function getNodeInnerHTML( $xpathContext, $xpath )
    {
        $xmlNodeList = $xpathContext->query( $xpath  );
        $nodeValue = '';
        if( $xmlNodeList->length > 0 )
        {
            foreach( $xmlNodeList as $xmlNode )
            {
                $doc = new DOMDocument();
                $doc->appendChild($doc->importNode($xmlNode,true));

                $nodeValue = $doc->saveXML();
                $nodeValue = str_replace('<?xml version="1.0"?>','',$nodeValue);
                break;
            }
        }
        return $nodeValue;
    }
    /**
     * Returns the text from an XHTML node
     * @param <string> $xpathContext - the XHTML to search
     * @param <string> $xpath - the xpath query to the node
     * @return <string>
     */
    function getNodeValue( $xpathContext, $xpath )
    {
        $xmlNodeList = $xpathContext->query( $xpath . "/text()" );
        $nodeValue = '';
        if( $xmlNodeList->length > 0 )
        {
            $nodeValue = $xmlNodeList->item( 0 )->nodeValue;
        }
        return $nodeValue;
    }
    /**
     * Utility function that queries the string resource for the live services block
     * and returns a value based on the supplied key
     * @param <string> $stringName - the name of the key to look up in the string resource
     * @return <string>
     */
    function getLocalizedString( $stringName )
    {
        return get_string( $stringName, 'block_live_services' );
    }
    /**
     * returns a random lower-case string from the English alphabet
     * @param <int> $length - the length of the string
     * @return <string>
     */
    function getRandomAlphaString($length)
    {
        $random= "";

        srand((double)microtime()*1000000);

        $data = "abcdefghijklmnopqrstuvwxyz";

        for($i = 0; $i < $length; $i++)
        {
            $random .= substr($data, (rand()%(strlen($data))), 1);
        }

        return $random;
    }
    /**
     * Returns a random alphanumeric string of length = $length
     * @param <int> $length - the length of the string to return
     * @return <string>
     */
    function getRandomAlphaNumericString($length)
    {
        $random= "";

        srand((double)microtime()*1000000);

        $data = "abcdefghijklmnopqrstuvwxyz0123456789"; //!@#$%^*()-_=+;: ,./?`~";

        for($i = 0; $i < $length; $i++)
        {
            $random .= substr($data, (rand()%(strlen($data))), 1);
        }

        return $random;
    }
    /**
     * Converts a datetime value to the local date/time in the supplied format
     * @param <string> $dateTime - the datetime to convert to local datetime
     * @param <string> $format - the format of the returned datetime
     * @return <date>
     */
    function convertToLocalTime($dateTime, $format)
    {

        $curTZ = date_default_timezone_get();
        
        $usersTimeZoneOffsetInHours = get_user_timezone_offset()*-1;
        
        if( $usersTimeZoneOffsetInHours != -99 ) {
            
            $usersTimeZone = "Etc/GMT";
        
            if( $usersTimeZoneOffsetInHours > 0 ) $usersTimeZone .= "+$usersTimeZoneOffsetInHours";
            elseif( $usersTimeZoneOffsetInHours < 0 ) $usersTimeZone .= "$usersTimeZoneOffsetInHours";

            date_default_timezone_set($usersTimeZone);
        }

        $dateTimeInSeconds = strtotime($dateTime);
        $retVal = date($format, $dateTimeInSeconds );
        
        date_default_timezone_set($curTZ);
        
        return $retVal;
    }

    /**
     * Converts a local datetime to UTC (aka GMT or Zulu time)
     * @param <string> $dateTime - a local datetime string
     * @return <date>
     */
    function convertToUTC($dateTime)
    { 
        $curTZ = date_default_timezone_get();
        
        $usersTimeZoneOffsetInHours = get_user_timezone_offset()*-1;
        
        if( $usersTimeZoneOffsetInHours != -99 ) {
            
            $usersTimeZone = "Etc/GMT";
        
            if( $usersTimeZoneOffsetInHours > 0 ) $usersTimeZone .= "+$usersTimeZoneOffsetInHours";
            elseif( $usersTimeZoneOffsetInHours < 0 ) $usersTimeZone .= "$usersTimeZoneOffsetInHours";

            date_default_timezone_set($usersTimeZone);
        }       
        
        $dateTimeInSeconds = strtotime($dateTime);
        $localTimeZoneOffset = date('Z');

        $retVal = date('Y-m-d\TH:i:s\Z' , $dateTimeInSeconds - $localTimeZoneOffset);
                
        date_default_timezone_set($curTZ);                
                
        return $retVal;

    }
    /**
     * An exception handler that can handle the custom SoapException type or
     * base Exception type
     * @param <Exception> $exception 
     */
    function handleException($exception)
    {
        if( get_class($exception) == "SoapException" )
        {
            $faultCode = $exception->getFaultCode();
            $message = $exception->getMessage();
        }
        else
        {
            $faultCode = $exception->getCode();
            $message = $exception->getMessage();
        }
        error_log( "Exception: $faultCode $message" );
    }

    /**
     * If the displayName (liveID) is longer than 25 characters, we wrap it at the
     * "@" symbol of the email address
     * @param <string> $liveId - the liveId of the currently logged in user
     * @return <string>
     */
    function wrapDisplayName($liveId)
    {
        $liveIdLength = strlen($liveId);
        if($liveIdLength < 26 || !strpos($liveId,"@",0))
        {
            return $liveId;
        }
        else
        {
            $atSymbolPos = strpos($liveId,"@",0);
            $line1 = substr($liveId,0,$atSymbolPos);
            $line2 = substr($liveId,$atSymbolPos + 1,($liveIdLength-$atSymbolPos)-1);
        }   return $line1."@<br/>".$line2;
    }
    /**
     * Returns a result string in JSON format. This can be parsed and displayed by the lightboxes
     * @param <int> $code - a result code. usually 0 is success and -1 is failure for EWS. this can be customized
     * @param <string> $reason - the reason for success or failure returned by EWS, or any custom reason message
     * @param <bool> $error - true if an error was returned, false if not
     * @param <string> $exceptionmessage - an exception message returned if error=true, usually an empty string if error=false
     * @return <string>
     */
    function getJsonResultString($code, $reason, $error, $exceptionmessage)
    {
        $resultString = '{"code":"{0}","reason":"{1}","error":"{2}","exceptionmessage":"{3}"}';
        $resultString = str_replace('{0}',$code,$resultString);
        $resultString = str_replace('{1}',$reason,$resultString);
        $resultString = str_replace('{2}',$error,$resultString);
        $resultString = str_replace('{3}',str_replace('"','\"',$exceptionmessage),$resultString);
        //$resultString = str_replace(':','\:',$resultString);
        return $resultString;
    }
    /**
     * Returns the browser for the current user
     * @param <string> $user_agent - the user agent from the response header
     * @return <string> 
     */
    function getUserBrowser($user_agent)
    {
        
        $user_browser = '';
        if(preg_match('/MSIE/i',$user_agent))
        {
            $user_browser = "ie";
        } 
        elseif(preg_match('/Firefox/i',$user_agent))
        {
            $user_browser = "firefox";
        }
        elseif(preg_match('/Safari/i',$user_agent))
        {
            $user_browser = "safari";
        }
        elseif(preg_match('/Chrome/i',$user_agent))
        {
            $user_browser = "chrome";
        }
        elseif(preg_match('/Flock/i',$user_agent))
        {
            $user_browser = "flock";
        }
        elseif(preg_match('/Opera/i',$user_agent))
        {
            $user_browser = "opera";
        }

        return $user_browser;
    }

?>
