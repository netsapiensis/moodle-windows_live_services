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

require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * Class that manages the authentication with Exchange Web Services (EWS)
 */
class EWSAuthentication
{
    private $appId = '';
    private $username;
    private $password;

    /**
     * Class constructor. Create a new EWSAuthentication class for the given
     * Username and Password
     * @param <string> $username - username for the account in Exchange
     * @param <string> $password - password for the given user
     */
    function __construct($username, $password)
    {
        global $CFG;
        
        // Use the base64 encoded version of wwwroot as the appId
        $appId = base64_encode($CFG->wwwroot);

        $this->username = $username;
        $this->password = $password;

        if( strlen($username) < 1 || strlen($password) < 1 )
        {
            throw new Exception("username and password is required");
        }
    }
    
    /**
     *
     * @param <boolean> $forceRefetch - if $forceRefetch is false, use the exchange service data in Session, otherwise query
     * the EWS service for data
     * @return <ExchangeServiceData>
     */
    public function AuthenticateAgainstEWSEndPoint( $forceRefetch )
    {
        $exchangeServiceData = @$_SESSION[ 'wls_EWSAuthEndPoint' ];

        // No SAMLToken and no need to refresh SAMLToken
        /* 
        if( $forceRefetch == false )
        {
            if( isset($exchangeServiceData['AuthenticationData']['Experation']) )
            {
                // Check to see if autodiscovery has expired or if the token has expired.
                $now = date('Y-m-d\TH:i:s\Z');
                if( $now < $exchangeServiceData['AuthenticationData']['Experation'] )
                {
                    return $exchangeServiceData;
                }
            }
        }
        */

        try
        {
            $exchangeUrl = $this->AutodicoverExchangeURL( $this->username );
            
            // Change service url in favor of basic authentication 
            $targetService = "$exchangeUrl/ews/exchange.asmx";

            // Don't need token anymore after changing authentication to Basic Authenticaiton. Replace with empty string. 
            //$token = $this->GetAuthorizationTokenForEWS( $targetService, $this->username, $this->password );
	    	//TODO:Related code are to be removed completely. Dummy string to pass length check.
	    	$exchangeServiceData = array(
                                            'ExchangeURL'           => $exchangeUrl,
                                            'AuthenticationData'    =>
                                                    array(  'TargetService' => $targetService,
                                                            'SAMLToken'     => "") 
                                                    );
            /* 
            $token = "";  
            $exchangeServiceData = array(   'Experation'            => date('Y-m-d\TH:i:s\Z', strtotime("+6 hours")),
                                            'ExchangeURL'           => $exchangeUrl,
                                            'AuthenticationData'    =>
                                                    array(  'Experation'    => date('Y-m-d\TH:i:s\Z', strtotime("+6 hours")),
                                                            'TargetService' => $targetService,
                                                            'SAMLToken'     => $token ) );
			*/
            $_SESSION[ 'wls_EWSAuthEndPoint' ] = $exchangeServiceData ;

            return $exchangeServiceData;
        }
        catch( Exception $e )
        {
           unset( $_SESSION[ 'wls_EWSAuthEndPoint' ] );
           handleException($e);
        }
        return null;
    }

    /**
     * Returns a SAML token
     * @param <string> $targetService - the URL of the Exchange service
     * @param <string> $username - the username used to generate the SAML token
     * @param <string> $password - the password used to generate the SAML token
     * @return <string> (a SAML token)
     */
    private function GetAuthorizationTokenForEWS( $targetService, $username, $password )
    {
        $ewsMetaData = $this->GetEWSMetaData();
        $devicePuid = $ewsMetaData['DevicePuid'];
        $authEndPoint = $ewsMetaData['FederationMetaData']['AuthenticationEndPoint'];

        $envelope = $this->getEnvelope( $devicePuid, $authEndPoint, $username, $password, "MBI_FED_SSL", $targetService );

        $curl = new CurlLib();
        $response = $curl->postSoapEnvelope( $authEndPoint, $envelope, null );

        $samlToken = $this->getSAMLTokenFromResponse( $response );

        return $samlToken;

    }

    /**
     * TODO: implement actual Autodiscover. For now, we are using $CFG to store the Exchange URL
     * @global <array> $CFG - the global configuration array
     * @param <string> $username - the username used to autodiscover the Exchange server
     * @return <string> (URL)
     */
    private function AutodicoverExchangeURL( $username )
    {
        global $CFG;
        $autoDiscoverySOAP = <<<SOAP
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006">
   <Request>
     <EMailAddress>$username</EMailAddress>
     <AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a</AcceptableResponseSchema>
   </Request>
</Autodiscover>
SOAP;


        // TODO: Do autodiscovery, for now we will configure the server in the block configuration
        // Get the autodisocver URL from the username
        // $autoDiscoverUrl = substr_replace($username, 'autodiscover.', 0,  strpos($username,'@')+1);
        // $autoDiscoverUrl = "http://$autoDiscoverUrl/Autodiscover/Autodiscover.xml";
        $exchangeUrl = @$CFG->block_live_services_ewsServiceUrl;
        return $exchangeUrl;
    }

    /**
     * Gets EWS MetaData
     * @return <array>
     */
    private function GetEWSMetaData()
    {
        if( isset($_SESSION['wls_EWSMetaData']) )
        {
            $ewsMetaData = $_SESSION['wls_EWSMetaData'];
            if( isset($ewsMetaData[$this->appId]) )
            {
                return $ewsMetaData[$this->appId];
            }
        }

        $fedDataArray = $this->GetFederationMetaData();
        $devicePuid = $this->GetDeviceRegistrationPuid( $fedDataArray['DeviceRegistrationEndpoint'] );

        $ewsMetaData = array(   'DevicePuid'            => $devicePuid,
                                'FederationMetaData'    => $fedDataArray);

        $_SESSION['wls_EWSMetaData'][$this->appId] = $ewsMetaData;

        return $ewsMetaData;
    }

    /**
     * Gets Federation MetaData
     * @return <array>
     */
    private function GetFederationMetaData()
    {
        $fedUrl = 'https://nexus.passport.com/federationmetadata/2006-12/FederationMetaData.xml';

        $curl = new CurlLib();
        $xmlResponse = $curl->getRestResponse( $fedUrl, null );

        $xml = simplexml_load_string($xmlResponse);
        $xml->registerXPathNamespace( "fed", "http://schemas.xmlsoap.org/ws/2006/03/federation" );
        $xml->registerXPathNamespace( "wsa", "http://www.w3.org/2005/08/addressing" );

        $addressNode = $xml->xpath( '/fed:FederationMetadata/fed:Federation/fed:TargetServiceEndpoint/wsa:Address' );

        $authenticationEndPoint = trim($addressNode[0]);

        $deviceRegistrationEndpoint = substr( $authenticationEndPoint, 0, strripos( $authenticationEndPoint, "/" ) ) . "/ppsecure/DeviceAddCredential.srf";

        return array('AuthenticationEndPoint'=>$authenticationEndPoint, 'DeviceRegistrationEndpoint'=>$deviceRegistrationEndpoint);

    }

    /**
     *
     * @param <string> $deviceRegistrationEndPoint - a URL
     * @return <string> (XML)
     */
    private function GetDeviceRegistrationPuid($deviceRegistrationEndPoint)
    {
        $memberName = "11"; // This is always 11
        $deviceName = getRandomAlphaString(17);
        $secretKey = getRandomAlphaNumericString(17);

        $soapMessage = <<<SOAP
<!--  GetDeviceRegistrationPuid -->       
<?xml version='1.0' encoding='utf-8' ?>
<DeviceAddRequest>
    <ClientInfo name='$this->appId' version='1.0'/>
    <Authentication>
    <Membername>$memberName$deviceName</Membername>
    <Password>$secretKey</Password>
    </Authentication>
</DeviceAddRequest>
SOAP;

        $curl = new CurlLib();
        $xmlResponse = $curl->postSoapEnvelope($deviceRegistrationEndPoint, $soapMessage, null);

        $xml = simplexml_load_string($xmlResponse);

        $puidNode = $xml->xpath( '/DeviceAddResponse/puid' );
        
	if( count($puidNode) < 1 ) {
		error_log( "SOAP Performance: <PuidError>$xmlResponse</PuidError>" );		
		return "moodle_live_services_plugin_could_not_get_devicepuid";
	}

        return trim($puidNode[0]);

    }

    /**
     * Creates a SOAP Envelope to send to the EWS service
     * @param <string> $devicePuid
     * @param <string> $authEndPoint
     * @param <string> $username
     * @param <string> $password
     * @param <string> $ticketType
     * @param <string> $targetService
     * @return <string> (SOAP Envelope)
     */
    private function getEnvelope( $devicePuid, $authEndPoint, $username, $password, $ticketType, $targetService )
    {
        $created = date('Y-m-d\TH:i:s\Z');
        $expires = date('Y-m-d\TH:i:s\Z', strtotime('+1 hour'));  // TODO: What is the right expires value?

        return <<<RST
<?xml version='1.0' encoding='UTF-8'?>
<s:Envelope
        xmlns:s='http://www.w3.org/2003/05/soap-envelope'
        xmlns:wsse='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'
        xmlns:wsp='http://schemas.xmlsoap.org/ws/2004/09/policy'
        xmlns:wsu='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd'
        xmlns:wsa='http://www.w3.org/2005/08/addressing'
        xmlns:wst='http://schemas.xmlsoap.org/ws/2005/02/trust'>
    <s:Header>
        <wsa:Action s:mustUnderstand='1'>http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</wsa:Action>
        <wsa:To s:mustUnderstand='1'>$authEndPoint</wsa:To>
        <ps:AuthInfo xmlns:ps='http://schemas.microsoft.com/LiveID/SoapServices/v1' Id='PPAuthInfo'>
            <ps:HostingApp>$this->appId</ps:HostingApp>
        </ps:AuthInfo>
        <wsse:Security>
            <wsse:UsernameToken wsu:Id='user'>
                <wsse:Username>$username</wsse:Username>
                <wsse:Password>$password</wsse:Password>
            </wsse:UsernameToken>
            <wsse:BinarySecurityToken ValueType='urn:liveid:device' id='DeviceDAToken'>$devicePuid</wsse:BinarySecurityToken>
            <wsu:Timestamp Id='Timestamp'>
                <wsu:Created>$created</wsu:Created>
                <wsu:Expires>$expires</wsu:Expires>
            </wsu:Timestamp>
        </wsse:Security>
    </s:Header>
    <s:Body>
        <wst:RequestSecurityToken Id='RST0'>
            <wst:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</wst:RequestType>
            <wsp:AppliesTo>
                <wsa:EndpointReference>
                    <wsa:Address>$targetService</wsa:Address>
                </wsa:EndpointReference>
            </wsp:AppliesTo>
            <wsp:PolicyReference URI='$ticketType'></wsp:PolicyReference>
        </wst:RequestSecurityToken>
    </s:Body>
</s:Envelope>
RST;

    }

    /**
     * Extracts a SAML Token from a SOAP Repsonse
     * @param <string> $response - SOAP Response containing a SAML token
     * @return <string> (SAML Token)
     */
    private function getSAMLTokenFromResponse( $response )
    {
        if( strlen( $response ) > 0 )
        {
            // setup the xpath dom objects
            $xmlDomDocument = new DOMDocument();
            $xmlDomDocument->loadXML( $response );
            $xpathContext = new DOMXPath( $xmlDomDocument );
            $xpathContext->registerNamespace( "S", "http://www.w3.org/2003/05/soap-envelope" );
            $xpathContext->registerNamespace( "wst", "http://schemas.xmlsoap.org/ws/2005/02/trust" );
            $xpathContext->registerNamespace( "xmlenc", "http://www.w3.org/2001/04/xmlenc#" );
            $token = getNodeInnerHTML( $xpathContext,
                    "/S:Envelope/S:Body/wst:RequestSecurityTokenResponse/wst:RequestedSecurityToken/xmlenc:EncryptedData" );
        }
        else
        {
            $token = 'empty response';
        }
        return $token;
    }

}

?>
