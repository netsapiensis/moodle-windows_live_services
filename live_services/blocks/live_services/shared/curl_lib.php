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

/**
 * The SoapException class extends the Exception class and provides methods to get and set SoapFault codes
 */
class SoapException extends Exception
{
    private $faultCode = "";

    /**
     * Getter for the FaultCode property
     * @return <string>
     */
    public function getFaultCode()
    {
        return $this->faultCode;
    }
    /**
     * Setter for the FaultCode property
     * @param <string> $faultCode
     */
    public function setFaultCode($faultCode)
    {
        $this->faultCode = $faultCode;
    }
}

/**
 * The CurlLib class is a library of CURL methods
 */
class CurlLib
{
    /**
     * Class Constructor, currently not implemented
     */
    function __construct()
    {
    }
    /**
     * Posts a SOAP envelope to the
     * @param <string> $url - The location of the service where the SOAP will be posted
     * @param <string> $envelope - A SOAP Envelope to post to the service located at the given url
     * @param <string> $httpHeaders - the HTTP Headers that are posted to the service located at the given url
     * @return <string> (SOAP Response)
     */
    function postSoapEnvelope( $url, $envelope, $httpHeaders )
    {
        global $CFG;
        
        // Read impersonation information from configuration file
        $impersonationLiveId = @$CFG->block_live_services_ewsServiceAccountUserName;
        $impersonationPassword = @$CFG->block_live_services_ewsServiceAccountPassword;
        
        try
        {
            $session = curl_init();
            
            if (!empty($CFG->proxyhost)) {
                if (empty($CFG->proxyport)) {
                    curl_setopt($session, CURLOPT_PROXY, $CFG->proxyhost);
                } else {                    
                    curl_setopt($session, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
                }
            }
            
            
            curl_setopt( $session, CURLOPT_URL, $url );
			
            // Change authentciation to basic authentication 
            curl_setopt( $session, CURLOPT_USERPWD, "$impersonationLiveId:$impersonationPassword");
            curl_setopt( $session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt( $session, CURLOPT_HEADER, false );
            curl_setopt( $session, CURLOPT_POST, 1 );
            curl_setopt( $session, CURLOPT_POSTFIELDS, $envelope );
            curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $session, CURLOPT_SSL_VERIFYPEER, false );
            if( $httpHeaders )
            {
                curl_setopt( $session, CURLOPT_HTTPHEADER, $httpHeaders );
            }
            
            $time_start = microtime(true);
            
            $response = curl_exec( $session );
            
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $soapRequestBody = $this->getSoapBody($envelope);
            $executedAction = "<SoapRequestMade executionTime='$time'>$soapRequestBody</SoapRequestMade>";
            // use this for trouble shooting performance issues error_log( "SOAP Performance: $executedAction" );
            
            curl_close( $session );

            if( strlen($response) > 0 )
            {
                // Check for a soap fault and throw a SoapFault if there is a fault
                $this->checkForSoapFault($response);
            }
            
            return $response;
        }
        catch(Exception $exc)
        {
            throw $exc;
        }
    }
    /**
     * Performs a REST web service call
     * @param <string> $url - The url where the REST call is made
     * @param <string> $httpHeaders - The Http headers sent as part of the REST call
     * @return <string> (REST Response)
     */
    function getRestResponse( $url, $httpHeaders )
    {
        global $CFG;        
        
        $session = curl_init();
        
        if (!empty($CFG->proxyhost)) {
            if (empty($CFG->proxyport)) {
                curl_setopt($session, CURLOPT_PROXY, $CFG->proxyhost);
            } else {                    
                curl_setopt($session, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
            }
        }
            
        curl_setopt( $session, CURLOPT_URL, $url );
        curl_setopt( $session, CURLOPT_HEADER, false );
        if( $httpHeaders )
        {
            curl_setopt( $session, CURLOPT_HTTPHEADER, $httpHeaders ); //array( "Authorization: $token\r\nContent-Type: $contentType" ) );
        }
        curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $session, CURLOPT_SSL_VERIFYPEER, false );
        
        $time_start = microtime(true);
        $response = curl_exec( $session );
        $time_end = microtime(true);
        $time = $time_end - $time_start; 
        $executedAction = "<RestfulRequestMade executionTime='$time'>$url</RestfulRequestMade>";
        // use this for trouble shooting performance issues error_log( "SOAP Performance: $executedAction" );
        
        curl_close( $session );
        
        return $response;
    }

    /**
     * This is very specific to debugging the EWS calls.  Consider updating 
     * so it uses a DOMDocument to get the soap body.
     */
    private function getSoapBody($soapRequest)
    {
	$bodyStartIndex = strpos($soapRequest, '<soap:Body>');
	$bodyEndIndex = strpos($soapRequest, '</soap:Body>');

	$soapBody = substr( $soapRequest, $bodyStartIndex, ($bodyEndIndex-$bodyStartIndex) );
	
	if( strlen($soapBody) == 0 ) return "";
	
	$soapBody = str_replace  ( 'soap:', '', $soapBody);
	$soapBody = str_replace  ( 't:', '', $soapBody);
	$soapBody = str_replace  ( 'm:', '', $soapBody);
	$soapBody = str_replace  ( 'r:', '', $soapBody);
	$soapBody = str_replace  ( "\r", '', $soapBody);
	$soapBody = str_replace  ( "\n", '', $soapBody);
	$soapBody = str_replace  ( "\t", '', $soapBody);	

	return $soapBody . '</Body>';

    }

    /**
     * Inspects the given SOAP response and throws a SoapFault if one is found
     * @param <string> $soapResponse  - The SOAP Response to check
     */
    private function checkForSoapFault($soapResponse)
    {
        $xmlDomDocument = new DOMDocument();
        $xmlDomDocument->loadXML( $soapResponse );
        $xpathContext = new DOMXPath( $xmlDomDocument );

        $faultCode = getNodeValue( $xpathContext,'//faultcode' );
        if( strlen($faultCode) > 0 )
        {
            $faultString = getNodeValue( $xpathContext,'//faultstring' );

            $fault = new SoapException($faultString);
            $fault->setFaultCode($faultCode);            
            throw $fault;
        }
        else
        {
            $xpathContext->registerNamespace( "s", 'http://www.w3.org/2003/05/soap-envelope' );
            $xpathContext->registerNamespace( "psf", "http://schemas.microsoft.com/Passport/SoapServices/SOAPFault" );

            $faultCode = getNodeValue( $xpathContext, '/s:Envelope/s:Body/s:Fault/s:Code/s:Subcode/s:Value' );
            if( strlen($faultCode) > 0 )
            {
                $faultString = getNodeValue( $xpathContext, '/s:Envelope/s:Body/s:Fault/s:Detail/psf:error/psf:internalerror/psf:text' );
                
                if( strlen($faultString) < 1 )
                {
			$faultString = getNodeValue( $xpathContext, '/s:Envelope/s:Body/s:Fault/s:Reason/s:Text' );
		}
		
                $fault = new SoapException($faultString);
                $fault->setFaultCode($faultCode);

                throw $fault;
            }
        }
    }
}

?>
