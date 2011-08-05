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
global $CFG;

require_once( $CFG->dirroot . '/blocks/live_services/shared/curl_lib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/shared/utils.php' );

/**
 * The EWSQueryResult class represents the data returned by calls to the EWS web service
 * Properties are Count, Items, and ResultString.
 */
class EWSQueryResult
{
	private $_count;
	private $_items;
	private $_resultString;

	/**
	 * Class constrcutor
	 * @param <int> $count: by convention, supply -1 if there is no count returned or if there is a trapped error
	 */
	public function __construct($count)
	{
		self::setCount($count);
	}
	/**
	 * Setter for the Count property
	 * @param <int> $count: the count of records returned by the query
	 */
	public function setCount($count)
	{
		$this->_count = $count;
	}
	/**
	 * Returns the value of the Count property
	 * @return <int>
	 */
	public function getCount()
	{
		return $this->_count;
	}
	/**
	 * Setter for the Items property
	 * @param <array> $items: an array of items returned by the query
	 */
	public function setItems($items)
	{
		$this->_items = $items;
	}
	/**
	 * Returns the value of the Items property
	 * @return <array>
	 */
	public function getItems()
	{
		return $this->_items;
	}
	/**
	 * Setter for the ResultString property
	 * @param <string> $resultString: a standard JSON result string containing the properties
	 * code, reason, error, and exceptionmessage
	 */
	public function setResultString($resultString)
	{
		$this->_resultString = $resultString;
	}
	/**
	 * Returns the value of the ResultString property
	 * @return <string>
	 */
	public function getResultString()
	{
		return $this->_resultString;
	}
}

/**
 * The EWSWrapper class provides a wrapper for the EWS Web Service methods that are
 * needed for the Microsoft Live Services Plug-in for Moodle block.
 */
class EWSWrapper
{
	/**
	 * constructor not implemented at this time
	 */
	public function __construct()
	{
	}

	/**
	 * The GetUnreadEMail method returns unread email items
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <int> $count - the number of messages to return
	 * @return <EWSQueryResult>
	 */
	public function GetUnreadEMail( $liveId, $exchangeServiceAuthData, $count )
	{
		$returnValue = new EWSQueryResult( -1 );
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			$returnValue = new EWSQueryResult( $this->GetUnreadEMailCount($liveId, $serviceEndPoint) );

			if( $returnValue->getCount() > 0)
			{
				// The the SOAP Envelope for the UnreadEmail count
				$soapEnvelope = $this->GetUnreadEmailEnvelope( $liveId, $count );

				// POST The SOAP Envelope to the URL for the Exchange Server
				$curl = new CurlLib();
				$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
				$xml = simplexml_load_string($response);

				$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
				$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
				$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

				$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:FindItemResponse/m:ResponseMessages/m:FindItemResponseMessage/m:ResponseCode" );
				if( count($responseCodeNode) > 0 )
				{
					if( $responseCodeNode[0] == "NoError" )
					{
						$messageItems = $xml->xpath( "/s:Envelope/s:Body/m:FindItemResponse/m:ResponseMessages/m:FindItemResponseMessage/m:RootFolder/t:Items/*" );

						$messageArray = array();
						foreach( $messageItems as $messageItem)
						{
							// Get each of the items of interest from the CalendarItem node
							$itemId = $messageItem->xpath('t:ItemId');
							$subject = $messageItem->xpath('t:Subject');
							$webClientReadFormQueryString = $messageItem->xpath('t:WebClientReadFormQueryString');

							$messageItemArray = array(  'ItemId'                        =>$itemId[0],
                                                            'WebClientReadFormQueryString'  =>$webClientReadFormQueryString[0],
                                                            'Subject'                       => htmlspecialchars( $subject[0] )); 

							Array_push( $messageArray, $messageItemArray );
						}

						$returnValue->setItems($messageArray);
						$code = '0';
						$reason = 'Success';
						$error = 'false';
						$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));

						return $returnValue ;
					}
					else
					{
						$code = '-1';
						$reason = 'EWS Returned Error: '.$responseCodeNode[0];
						$error = 'true';
						$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
						return $returnValue;
					}
				}
				else
				{
					$code = '-1';
					$reason = 'Call to EWS Failed';
					$error = 'true';
					$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
					return $returnValue;
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
				return $returnValue;
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
			return $returnValue;
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
			return $returnValue;
		}

	}


	/**
	 * The GetEmailItem method returns one email message
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <string> $itemId - the ID of the email item being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <EWSQueryResult>
	 */
	public function GetEmailItem( $liveId, $exchangeServiceAuthData, $itemId, $changeKey )
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetViewEmailEnvelope( $liveId, $itemId,$changeKey);
			// echo $soapEnvelope;

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
			// error_log( $response );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:GetItemResponse/m:ResponseMessages/m:GetItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$messageNodes = $xml->xpath("/s:Envelope/s:Body/m:GetItemResponse/m:ResponseMessages/m:GetItemResponseMessage/m:Items/*");
					$messageNode = $messageNodes[0];
					$from = $messageNode->xpath('t:From/t:Mailbox/t:EmailAddress');
					$subject = $messageNode->xpath('t:Subject');
					$body = $messageNode->xpath('t:Body');
					$sentDateTime = $messageNode->xpath('t:DateTimeSent');
					$sent = convertToLocalTime($sentDateTime[0], 'D m/d/Y g:i A');
					$hasAttachments = $messageNode->xpath('t:HasAttachments');
					$attachmentsArray = array();
					if((bool)$hasAttachments[0])
					{
						$fileAttachments = $messageNode->xpath('t:Attachments/t:FileAttachment');
						foreach($fileAttachments as $fileAttachment)
						{
							$attachmentId = $fileAttachment->xpath('t:AttachmentId/@Id');
							$attachmentsContent = $this->GetAttachment($liveId, $serviceEndPoint, (string)$attachmentId[0]);
							$attachmentsArray[] = $attachmentsContent;
						}
					}
					$code = '0';
					$reason = $responseCodeNode[0];
					$error = 'false';
					$message = array('from'=>$from[0],'subject'=>$subject[0],'body'=>$body[0],'sent'=>$sent,'attachments'=>$attachmentsArray,'code'=>$code,'reason'=>$reason,'error'=>$error,'exceptionmessage'=>$exceptionmessage);
					return $message;
				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$responseCodeNode[0];
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * The GetCalendarItem method returns one calendar event
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <string> $itemId - the ID of the calendar event being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <EWSQueryResult>
	 */
	public function GetCalendarItem( $liveId, $exchangeServiceAuthData, $itemId, $changeKey )
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetViewCalendarEnvelope( $liveId, $itemId,$changeKey);
			// echo $soapEnvelope;

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:GetItemResponse/m:ResponseMessages/m:GetItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$calendarEventNodes = $xml->xpath("/s:Envelope/s:Body/m:GetItemResponse/m:ResponseMessages/m:GetItemResponseMessage/m:Items/*");
					$calendarEventNode = $calendarEventNodes[0];
					$subject = $calendarEventNode->xpath('t:Subject');
					$start = @$calendarEventNode->xpath('t:Start');
					$end = @$calendarEventNode->xpath('t:End');
					$isAllDayEvent = $calendarEventNode->xpath('t:IsAllDayEvent');
					$duration = $calendarEventNode->xpath('t:Duration');
					$location = $calendarEventNode->xpath('t:Location');
					$timeZone = $calendarEventNode->xpath('t:TimeZone');
					$body = $calendarEventNode->xpath('t:Body');
					$isRecurring = $calendarEventNode->xpath('t:IsRecurring');
					$webClientReadFormQueryString = $calendarEventNode->xpath('t:WebClientReadFormQueryString');
					$dateTimeStart = null;
					$dateTimeEnd = null;
					if($start)
					{
						$dateTimeStart = convertToLocalTime($start[0],'D m/d/Y g:i A');
					}
					if($end)
					{
						$dateTimeEnd = convertToLocalTime($end[0],'D m/d/Y g:i A');
					}
					$code = '0';
					$reason = $responseCodeNode[0];
					$error = 'false';
					$calendarEventArray = array('Subject'                           =>$subject[0],
                                                    'WebClientReadFormQueryString'      =>@$webClientReadFormQueryString[0],
                                                    'IsRecurring'                       =>$isRecurring?$isRecurring[0]:false,
                                                    'Start'                             =>@$dateTimeStart,
                                                    'End'                               =>@$dateTimeEnd,
                                                    'TimeZone'                          =>@$timeZone[0],
                                                    'IsAllDayEvent'                     =>$isAllDayEvent?$isAllDayEvent[0]:false,
                                                    'Duration'                          =>@$duration[0],
                                                    'Location'                          =>@$location[0],
                                                    'Body'                              =>@$body[0],
                                                    'code'                              =>$code,
                                                    'reason'                            =>$reason,
                                                    'error'                             =>$error,
                                                    'exceptionmessage'                  =>$exceptionmessage);
					return $calendarEventArray;

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$responseCodeNode[0];
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * The GetUpcomingCalendarItems returns a specific number of calendar events sorted by event date
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <int> $count - The number of events to return
	 * @return <EWSQueryResult>
	 */
	public function GetUpcomingCalendarItems( $liveId, $exchangeServiceAuthData, $count )
	{
		$returnValue = new EWSQueryResult(-1);
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			// The the SOAP Envelope for the UnreadEmail count
			$soapEnvelope = $this->GetUpcomingCalendarItemsEnvelope( $liveId, $count );

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();

			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
			//echo $response;
			if($response==false)
			{
				$code = '-1';
				$reason = 'EWS Returned Error: No response returned';
				$error = 'true';
				$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
				return $returnValue;
			}
			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:FindItemResponse/m:ResponseMessages/m:FindItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$calendarItems = $xml->xpath( "/s:Envelope/s:Body/m:FindItemResponse/m:ResponseMessages/m:FindItemResponseMessage/m:RootFolder/t:Items/*" );

					$returnValue = new EWSQueryResult( count($calendarItems) );
					$code = '0';
					$reason = $responseCodeNode[0];
					$error = 'false';
					$messageArray = array();
					$resultArray = array('code'=>$code,'reason'=>$reason,'error'=>$error,'exceptionmessage'=>$exceptionmessage);
					foreach( $calendarItems as $calendarItem)
					{
						// Get each of the items of interest from the CalendarItem node
						$itemId = $calendarItem->xpath('t:ItemId');
						$subject = $calendarItem->xpath('t:Subject');
						$start = $calendarItem->xpath('t:Start');
						$end = $calendarItem->xpath('t:End');
						$isAllDayEvent = $calendarItem->xpath('t:IsAllDayEvent');
						$duration = $calendarItem->xpath('t:Duration');
						$location = $calendarItem->xpath('t:Location');
						$timeZone = $calendarItem->xpath('t:TimeZone');
						$isRecurring = $calendarItem->xpath('t:IsRecurring');
						$webClientReadFormQueryString = $calendarItem->xpath('t:WebClientReadFormQueryString');

						$calendarItemArray = array( 'ItemId'                            =>$itemId[0],
                                                        'WebClientReadFormQueryString'      =>$webClientReadFormQueryString[0],
                                                        'Subject'                           =>$subject[0],
                                                        'IsRecurring'                       =>$isRecurring[0],
                                                        'Start'                             =>@$start[0],
                                                        'End'                               =>@$end[0],
                                                        'TimeZone'                          =>$timeZone[0],
                                                        'IsAllDayEvent'                     =>@$isAllDayEvent[0],
                                                        'Duration'                          =>@$duration[0],
                                                        'Location'                          =>@$location[0] );

						Array_push( $messageArray, $calendarItemArray );
					}

					$returnValue->setItems($messageArray);
					$code = '0';
					$reason = 'Success';
					$error = 'false';
					$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
					return $returnValue ;
				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$responseCodeNode[0];
					$error = 'true';
					$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
					return $returnValue;
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
				return $returnValue;
			}

		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
			return $returnValue;
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
			return $returnValue;
		}


	}
	/**
	 * Sends an email using EWS and returns a standard JSON ResultString (code, reason, error, exceptionmessage).
	 * By adding attachments, the sending process is completely changed, so a new method was created.
	 * An email will be created and saved (but not sent). Then the attachments will be created and added to the saved email.
	 * Finally, the email will be sent.
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <array> $to - an array of message recipients
	 * @param <string> $subject - the subject of the email
	 * @param <string> $body - the body of the email
	 * @param <array> $attachments - an array of attachments to be sent with the email.
	 * @return <string>
	 */
	public function SendMailWithAttachments($liveId, $exchangeServiceAuthData, $to, $subject, $body, $attachments)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetCreateEmailEnvelope( $liveId, $to, $subject,$body);

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
	
			$xml = simplexml_load_string($response);
			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );
			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$idNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:Items/t:Message/t:ItemId/@Id");
					$changeKeyNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:Items/t:Message/t:ItemId/@ChangeKey");
					$id = $idNode[0];
					$changeKey = $changeKeyNode[0];
					try
					{
						$attachmentArray = $this->CreateAttachments($liveId, $serviceEndPoint, $attachments, $id, $changeKey);
						$lastChangeIndex = count($attachmentArray) - 1;
						$id = $attachmentArray[$lastChangeIndex]['attachmentRootItemId'];
						$changeKey = $attachmentArray[$lastChangeIndex]['attachmentRootItemChangeKey'];
						$resultString = $this->SendSavedMailWithAttachments($liveId, $serviceEndPoint, $id, $changeKey);
						return $resultString;
					}
					catch(Exception $e)
					{
						$code = $e->getCode();
						$reason = 'Call to EWS failed';
						$error = 'true';
						$exceptionmessage = $e->getMessage();
						return getJsonResultString($code,$reason,$error,$exceptionmessage);
					}
				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
				
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * Gets an email attachment by attachmentId and returns an array of attachments (name and content)
	 * @param <string> $liveId - the impersonation account liveid
	 * @param <string> $serviceEndPoint - the URL of the exchange service
	 * @param <string> $attachmentId - the id that uniquely identfies the attachment
	 * @return <array>
	 */

	private function GetAttachment($liveId, $serviceEndPoint, $attachmentId)
	{
		try
		{
			$soapEnvelope = $this->GetAttachmentEnvelope($liveId, $attachmentId);
			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8'));
			$xml = simplexml_load_string($response);
			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );
			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:GetAttachmentResponse/m:ResponseMessages/m:GetAttachmentResponseMessage/m:ResponseCode" );

			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$name = $xml->xpath('/s:Envelope/s:Body/m:GetAttachmentResponse/m:ResponseMessages/m:GetAttachmentResponseMessage/m:Attachments/t:FileAttachment/t:Name');
					$content = $xml->xpath('/s:Envelope/s:Body/m:GetAttachmentResponse/m:ResponseMessages/m:GetAttachmentResponseMessage/m:Attachments/t:FileAttachment/t:Content');
					return array('name'=>(string)$name[0],'content'=>(string)$content[0]);
				}
			}
		}
		catch(Exception $exc)
		{
			handleException($exc);

			/*
			 * Swallowing the exception since we don't want to stop the email from being displayed
			 * if there is an error retrieving the attachment
			 */
		}
	}
	/**
	 * Forwards an email using the id of the original email
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <type> $id - the id of the email being replied to
	 * @param <array> $to - an array of message recipients
	 * @param <string> $subject - the subject of the email
	 * @param <string> $body - the body of the email
	 * @return <string>
	 */
	public function SendMailForward($liveId, $exchangeServiceAuthData, $id, $changeKey, $to, $subject, $body)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];
				
			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetSendEmailForwardEnvelope( $liveId, $id, $changeKey, $to, $subject, $body);

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$code = '0';
					$reason = 'success';
					$error = 'false';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * Replies to an email using the id of the original email
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <type> $id - the id of the email being replied to
	 * @param <array> $to - an array of message recipients
	 * @param <string> $subject - the subject of the email
	 * @param <string> $body - the body of the email
	 * @return <string>
	 */
	public function SendMailReply($liveId, $exchangeServiceAuthData, $id, $changeKey, $to, $subject, $body)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];
				
			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetSendEmailReplyEnvelope( $liveId, $id, $changeKey, $to, $subject, $body);

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$code = '0';
					$reason = 'success';
					$error = 'false';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * Creates an attachmentId for each attachment in the attachments array
	 * @param <string> $liveId - the impersonation account liveid
	 * @param <string> $serviceEndPoint - the URL of the exchange service
	 * @param <array>  $attachments - an array of attachments (name and content)
	 * @param <string> $itemId - the ID of the email being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <array>
	 */
	private function CreateAttachments($liveId, $serviceEndPoint, $attachments, $id, $changeKey)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		// POST The SOAP Envelope to the URL for the Exchange Server
		$curl = new CurlLib();
		foreach($attachments as $attachment)
		{
			try
			{
				$soapEnvelope = $this->GetCreateAttachmentEnvelope($liveId, $id, $changeKey, $attachment['name'], $attachment['content']);
				$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8'));
				$xml = simplexml_load_string($response);

				$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
				$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
				$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );
				$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateAttachmentResponse/m:ResponseMessages/m:CreateAttachmentResponseMessage/m:ResponseCode" );
				if( count($responseCodeNode) > 0 )
				{
					if( $responseCodeNode[0] == "NoError" )
					{
						$attachmentIdNode = $xml->xpath("/s:Envelope/s:Body/m:CreateAttachmentResponse/m:ResponseMessages/m:CreateAttachmentResponseMessage/m:Attachments/t:FileAttachment/t:AttachmentId/@Id");
						$attachmentRootItemIdNode = $xml->xpath("/s:Envelope/s:Body/m:CreateAttachmentResponse/m:ResponseMessages/m:CreateAttachmentResponseMessage/m:Attachments/t:FileAttachment/t:AttachmentId/@RootItemId");
						$attachmentRootItemChangeKeyNode = $xml->xpath("/s:Envelope/s:Body/m:CreateAttachmentResponse/m:ResponseMessages/m:CreateAttachmentResponseMessage/m:Attachments/t:FileAttachment/t:AttachmentId/@RootItemChangeKey");
						$attachmentId = (string)$attachmentIdNode[0];
						$attachmentRootItemId = (string)$attachmentRootItemIdNode[0];
						$attachmentRootItemChangeKey = (string)$attachmentRootItemChangeKeyNode[0];
						$attachmentArray[] = array('attachmentId'=>$attachmentId,'attachmentRootItemId'=>$attachmentRootItemId,'attachmentRootItemChangeKey'=>$attachmentRootItemChangeKey);
					}
				}
				else
				{
					//unsuccessful, continue with next attachment
				}
			}
			catch(SoapException $soapException)
			{
				handleException($soapException);
				//swallow exception and attempt next attachment
			}
			catch(Exception $e)
			{
				handleException($e);
				//swallow exception and attempt next attachment
			}
		}
		return $attachmentArray;
	}

	/**
	 *
	 * @param <string> $liveId - the impersonation account liveid
	 * @param <string> $serviceEndPoint - the URL of the exchange service
	 * @param <string> $itemId - the ID of the email being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <string>
	 */

	private function SendSavedMailWithAttachments($liveId, $serviceEndPoint, $id, $changeKey)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		// POST The SOAP Envelope to the URL for the Exchange Server
		$curl = new CurlLib();
		try
		{
			$soapEnvelope = $this->GetSendEmailWithAttachmentsEnvelope($liveId, $id, $changeKey);
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8'));
			$xml = simplexml_load_string($response);
			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );
			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:SendItemResponse/m:ResponseMessages/m:SendItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$code = '0';
					$reason = 'Success';
					$error = false;
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}

	/**
	 * Sends an email using EWS and returns a standard JSON ResultString (code, reason, error, exceptionmessage).
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <array> $to - an array of message recipients
	 * @param <string> $subject - the subject of the email
	 * @param <string> $body - the body of the email
	 * @return <string>
	 */
	public function SendMail( $liveId, $exchangeServiceAuthData, $to, $subject, $body )
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];

			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetSendEmailEnvelope( $liveId, $to, $subject,$body);

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$code = '0';
					$reason = 'success';
					$error = 'false';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}

		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
	}
	/**
	 * Creates a new calendar entry and returns a standard JSON ResultString (code, reason, error, exceptionmessage)
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <array> $to - an array of event invitees
	 * @param <string> $subject - the subject of the event notification message
	 * @param <string> $body - the body of the event notification message
	 * @param <date> $start - the start date/time of the event
	 * @param <date> $end the end date/time of the event
	 * @param <bool> $isAllDayEvent - flag that indicates whether or not this is an all day event
	 * @return <string>
	 */
	public function CreateAppointment($liveId, $exchangeServiceAuthData, $to, $subject, $body, $start, $end, $isAllDayEvent)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];


			// Get the SOAP Envelope that will be used to Send Email
			$soapEnvelope = $this->GetCreateAppointmentEnvelope( $liveId, $to, $subject, $body, $start, $end, $isAllDayEvent);
			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint,  $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:CreateItemResponse/m:ResponseMessages/m:CreateItemResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$code = '0';
					$reason = 'success';
					$error = 'false';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}

	}
	/**
	 * Returns a list of possible name resolutions containing the input string
	 * @param <string> $liveId - the liveId of the impersonation account
	 * @param <array> $exchangeServiceAuthData - authorization data including the SAML token, Service EndPoint, and Exchange URL
	 * @param <type> $unresolvedName - the input string we are attempting to match
	 * @return <EWSQueryResult>
	 */
	public function ResolveNames($liveId, $exchangeServiceAuthData, $unresolvedName)
	{
		$code = ''; $reason = ''; $error = ''; $exceptionmessage = '';
		$resolvedNames = array();
		$returnValue;
		try
		{
			// Get authentication data that was returned by the call to AuthenticateAgainstEWSEndPoint
			$exchangeUrl            = $exchangeServiceAuthData['ExchangeURL'];
			$serviceEndPoint        = $exchangeServiceAuthData['AuthenticationData']['TargetService'];
				

			// The the SOAP Envelope for the UnreadEmail count
			$soapEnvelope = $this->GetResolveNamesEnvelope( $liveId, $unresolvedName);
			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();

			$response = $curl->postSoapEnvelope( $serviceEndPoint, $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );
			$responseMessageNode = $xml->xpath("/s:Envelope/s:Body/m:ResolveNamesResponse/m:ResponseMessages/m:ResolveNamesResponseMessage");
			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:ResolveNamesResponse/m:ResponseMessages/m:ResolveNamesResponseMessage/m:ResponseCode" );
			$responseClassNode = $xml->xpath( "/s:Envelope/s:Body/m:ResolveNamesResponse/m:ResponseMessages/m:ResolveNamesResponseMessage/@ResponseClass" );

			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" || $responseCodeNode[0] == "ErrorNameResolutionMultipleResults")
				{
					$resolvedItems = $xml->xpath( "/s:Envelope/s:Body/m:ResolveNamesResponse/m:ResponseMessages/m:ResolveNamesResponseMessage/m:ResolutionSet/*" );
					$resolvedItemsCountNode = $xml->xpath( "/s:Envelope/s:Body/m:ResolveNamesResponse/m:ResponseMessages/m:ResolveNamesResponseMessage/m:ResolutionSet/@TotalItemsInView" );
					$itemCount = $resolvedItemsCountNode[0]["TotalItemsInView"];
					$returnValue = new EWSQueryResult((int)$itemCount);
					foreach($resolvedItems as $item)
					{
						$displayName = $item->xpath('t:Mailbox/t:Name');
						$email = $item->xpath('t:Mailbox/t:EmailAddress');
						array_push($resolvedNames,array("ResolvedName"=>$this->FormatDisplayNameAndEmail($displayName[0],$email[0])));
					}
					$returnValue->setItems($resolvedNames);
					$code = '0';
					$reason = $responseClassNode[0];
					$error = 'false';
					$returnValue->setResultString(getJsonResultString($code,$reason,$error,$exceptionmessage));
					return $returnValue;

				}
				else
				{
					$code = '-1';
					$reason = 'EWS Returned Error: '.$this->MakeErrorMessageFriendlier($responseCodeNode[0]);
					$error = 'true';
					return getJsonResultString($code,$reason,$error,$exceptionmessage);
				}
			}
			else
			{
				$code = '-1';
				$reason = 'Call to EWS Failed';
				$error = 'true';
				return getJsonResultString($code,$reason,$error,$exceptionmessage);
			}
		}
		catch(SoapException $soapException)
		{
			handleException($soapException);

			$code = $soapException->getFaultCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $soapException->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}
		catch(Exception $e)
		{
			handleException($e);

			$code = $e->getCode();
			$reason = 'Call to EWS failed';
			$error = 'true';
			$exceptionmessage = $e->getMessage();
			return getJsonResultString($code,$reason,$error,$exceptionmessage);
		}

	}


	/**************************************************************************************************************************/
	/* Private helper functions */
	/**************************************************************************************************************************/
	/**
	 * Returns the total number of unread emails
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @return <int>
	 */
	private function GetUnreadEMailCount($impersonationSmtpAddress, $serviceEndPoint )
	{
		$unreadCount = -1;
		try
		{

			// The the SOAP Envelope for the UnreadEmail count
			$soapEnvelope = $this->GetUnreadEmailCountEnvelope( $impersonationSmtpAddress );

			// POST The SOAP Envelope to the URL for the Exchange Server
			$curl = new CurlLib();
			$response = $curl->postSoapEnvelope( $serviceEndPoint, $soapEnvelope, array('Content-Type: text/xml; charset=utf-8') );
			if($response==false)
			{
				return -1;
			}
			// echo $response;

			$xml = simplexml_load_string($response);

			$xml->registerXPathNamespace( "s", "http://schemas.xmlsoap.org/soap/envelope/" );
			$xml->registerXPathNamespace( "m", "http://schemas.microsoft.com/exchange/services/2006/messages" );
			$xml->registerXPathNamespace( "t", "http://schemas.microsoft.com/exchange/services/2006/types" );

			$responseCodeNode = $xml->xpath( "/s:Envelope/s:Body/m:GetFolderResponse/m:ResponseMessages/m:GetFolderResponseMessage/m:ResponseCode" );
			if( count($responseCodeNode) > 0 )
			{
				if( $responseCodeNode[0] == "NoError" )
				{
					$unreadCountNode = $xml->xpath( "/s:Envelope/s:Body/m:GetFolderResponse/m:ResponseMessages/m:GetFolderResponseMessage/m:Folders/t:Folder/t:UnreadCount" );
					$unreadCount = $unreadCountNode[0];
				}
				else
				{
					throw new Exception( "EWS Returned error: $responseCodeNode[0]" );
				}
			}
			else
			{
				throw new Exception( "Call to EWS failed." );
			}

		}
		catch (Exception $e)
		{
			throw($e);
		}

		return $unreadCount;
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @return <string>
	 */
	private function GetUnreadEmailCountEnvelope($impersonationSmtpAddress )
	{
		// This is the Body for the Unread Mail scount request
		$soapBody = <<<SOAP
		<!-- GetUnreadEmailCountEnvelope -->
        <m:GetFolder>
            <m:FolderShape>
                <t:BaseShape>Default</t:BaseShape>
            </m:FolderShape>
            <m:FolderIds>
                <t:DistinguishedFolderId Id="inbox"/>
            </m:FolderIds>
        </m:GetFolder>
SOAP;

		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);

	}
	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <int> $count - The count of unread items to return
	 * @return <string>
	 */
	private function GetUnreadEmailEnvelope($impersonationSmtpAddress, $count)
	{
		// This is the Body for the Unread mails request
		$soapBody = <<<SOAP
		<!-- GetUnreadEmailEnvelope -->
        <m:FindItem Traversal="Shallow">
            <m:ItemShape>
                <t:BaseShape>AllProperties</t:BaseShape>
            </m:ItemShape>
            <m:IndexedPageItemView MaxEntriesReturned="$count" Offset="0" BasePoint="Beginning" />
            <m:Restriction>
                <t:IsEqualTo>
                    <t:FieldURI FieldURI="message:IsRead" />
                    <t:FieldURIOrConstant>
                        <t:Constant Value="0" />
                    </t:FieldURIOrConstant>
                </t:IsEqualTo>
            </m:Restriction>
            <m:ParentFolderIds>
                <t:DistinguishedFolderId Id="inbox" />
            </m:ParentFolderIds>
        </m:FindItem>
SOAP;

		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);

	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <int> $count - The count of upcoming events to return
	 * @return <string>
	 */
	private function GetUpcomingCalendarItemsEnvelope($impersonationSmtpAddress, $count)
	{
		// set the default timezone to use.
		// date_default_timezone_set('UTC');

		// Start looking from the current hour.  This will include items that are 'in progress'
		$dateBeginRange = date('Y-m-d\TH:00:00\Z'); // \Z indicates UTC timezone

		// Look ahead 7 days for items.
		$dateEndRange = date('Y-m-d\TH:i:s\Z', strtotime("+7 day"));

		// This is the Body for the Unread Mailcount request
		$soapBody = <<<SOAP
		<!-- GetUpcomingCalendarItemsEnvelope -->
        <m:FindItem Traversal="Shallow">
            <m:ItemShape>
                <t:BaseShape>AllProperties</t:BaseShape>
            </m:ItemShape>
            <m:CalendarView StartDate="$dateBeginRange" EndDate="$dateEndRange" MaxEntriesReturned="$count" />
        <!--             
            <m:SortOrder>
               <t:FieldOrder Order="Ascending">
                    <t:FieldURI FieldURI="calendar:Start" />
               </t:FieldOrder>
            </m:SortOrder>
        -->            
            <m:ParentFolderIds>
                <t:DistinguishedFolderId Id="calendar" />
            </m:ParentFolderIds>
        </m:FindItem>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);

	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <array> $toEmailAddresses - an array of invitees to the event/appointment
	 * @param <string> $subject - the subject of the event notification message
	 * @param <string> $body - the body of the event notification message
	 * @param <date> $start - the start date/time of the event
	 * @param <date> $end - the end date/time of the event
	 * @param <bool> $isAllDayEvent - a flag that indicates whether or not this is an all day event
	 * @return <string>
	 */
	private function GetCreateAppointmentEnvelope($impersonationSmtpAddress, $toEmailAddresses, $subject, $body, $start, $end, $isAllDayEvent)
	{

		$startDateTimeUTC = convertToUTC( date('Y-m-d\TH:i:s', strtotime($start)) );
		$endDateTimeUTC = convertToUTC( date('Y-m-d\TH:i:s', strtotime($end)) );

		$MailBox ="";
		foreach($toEmailAddresses as $address)
		{
			$address = trim($address);
			$MailBox .= <<< MAILBOX
            <t:Attendee>
                <t:Mailbox>
                    <t:EmailAddress>$address</t:EmailAddress>
                    <t:RoutingType>SMTP</t:RoutingType>
                </t:Mailbox>
            </t:Attendee>
MAILBOX;
		}
		$soapBody = <<<SOAP
        <m:CreateItem MessageDisposition='SendAndSaveCopy' SendMeetingInvitations='SendToAllAndSaveCopy'>
            <m:Items>
                <t:CalendarItem>
                    <t:Subject>$subject</t:Subject>
                    <t:Body BodyType='Text'>$body</t:Body>
                    <t:Start>$startDateTimeUTC</t:Start>
                    <t:End>$endDateTimeUTC</t:End>
                    <t:IsAllDayEvent>$isAllDayEvent</t:IsAllDayEvent>
                    <t:OptionalAttendees>
                    $MailBox
                    </t:OptionalAttendees>
                </t:CalendarItem>
            </m:Items>
        </m:CreateItem>
SOAP;

                    return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	private function GetSendEmailReplyEnvelope($impersonationSmtpAddress, $id, $changeKey, $toEmailAddresses, $subject, $body)
	{
		$MailBox ="";
		foreach($toEmailAddresses as $address)
		{
			$address = trim($address);
			$MailBox .= <<< MAILBOX
            <t:Mailbox>
                <t:EmailAddress>$address</t:EmailAddress>
                <t:RoutingType>SMTP</t:RoutingType>
            </t:Mailbox>
MAILBOX;
		}
		$soapBody = <<<SOAP
        <m:CreateItem MessageDisposition='SendAndSaveCopy'>
            <m:Items>
                <t:ReplyToItem>
                    <t:ReferenceItemId Id="$id" ChangeKey="$changeKey"></t:ReferenceItemId>
                    <t:NewBodyContent BodyType='HTML'><![CDATA[$body]]></t:NewBodyContent>
                </t:ReplyToItem>
            </m:Items>
        </m:CreateItem>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	private function GetSendEmailForwardEnvelope($impersonationSmtpAddress, $id, $changeKey, $toEmailAddresses, $subject, $body)
	{
		$MailBox ="";
		foreach($toEmailAddresses as $address)
		{
			$address = trim($address);
			$MailBox .= <<< MAILBOX
            <t:Mailbox>
                <t:EmailAddress>$address</t:EmailAddress>
                <t:RoutingType>SMTP</t:RoutingType>
            </t:Mailbox>
MAILBOX;
		}
		$soapBody = <<<SOAP
        <m:CreateItem MessageDisposition='SendAndSaveCopy'>
            <m:Items>
                <t:ForwardItem>
                    <t:ReferenceItemId Id="$id" ChangeKey="$changeKey"></t:ReferenceItemId>
                    <t:NewBodyContent BodyType='HTML'><![CDATA[$body]]></t:NewBodyContent>
                    <t:ToRecipients>
                    $MailBox
                    </t:ToRecipients>
                </t:ForwardItem>
            </m:Items>
        </m:CreateItem>
SOAP;
                    return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <array> $toEmailAddresses - an array of recipients' email addresses
	 * @param <string> $subject - the email subject
	 * @param <string> $body - the email body
	 * @return <string>
	 */
	private function GetCreateEmailEnvelope($impersonationSmtpAddress, $toEmailAddresses, $subject, $body)
	{
		$MailBox ="";
		foreach($toEmailAddresses as $address)
		{
			$address = trim($address);
			$MailBox .= <<< MAILBOX
            <t:Mailbox>
                <t:EmailAddress>$address</t:EmailAddress>
                <t:RoutingType>SMTP</t:RoutingType>
            </t:Mailbox>
MAILBOX;
		}

		$soapBody = <<<SOAP
        <m:CreateItem MessageDisposition='SaveOnly'>
            <m:Items>
                <t:Message>
                    <t:Subject>$subject</t:Subject>
                    <t:Body BodyType='HTML'><![CDATA[$body]]></t:Body>
                    <t:ToRecipients>
                    $MailBox
                    </t:ToRecipients>
                </t:Message>
            </m:Items>
        </m:CreateItem>
SOAP;
                    return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}
	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $itemId - the ID of the calendar event being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <string>
	 */
	private function GetSendEmailWithAttachmentsEnvelope($impersonationSmtpAddress, $id, $changeKey)
	{

		$soapBody = <<<SOAP
        <m:SendItem SaveItemToFolder="true">
            <m:ItemIds>
                <t:ItemId Id="$id" ChangeKey="$changeKey" />
            </m:ItemIds>
        </m:SendItem>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <array> $toEmailAddresses - an array of invitees to the event/appointment
	 * @param <string> $subject - the subject of the event notification message
	 * @param <string> $body - the body of the event notification message
	 * @return <string>
	 */
	private function GetSendEmailEnvelope($impersonationSmtpAddress, $toEmailAddresses, $subject, $body)
	{
		$MailBox = "";
		foreach($toEmailAddresses as $address)
		{
			$address = trim($address);
			$MailBox .= <<< MAILBOX
            <t:Mailbox>
                <t:EmailAddress>$address</t:EmailAddress>
                <t:RoutingType>SMTP</t:RoutingType>
            </t:Mailbox>
MAILBOX;
		}

		$soapBody = <<<SOAP
        <m:CreateItem MessageDisposition='SendAndSaveCopy'>
            <m:Items>
                <t:Message>
                    <t:Subject>$subject</t:Subject>
                    <t:Body BodyType='Text'>$body</t:Body>
                    <t:ToRecipients>
                    $MailBox
                    </t:ToRecipients>
                </t:Message>
            </m:Items>
        </m:CreateItem>
SOAP;
                    return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}
	/**
	 *
	 * @param <type> $impersonationSmtpAddress
	 * @param <type> $attachmentId
	 * @return <type>
	 */
	private function GetAttachmentEnvelope($impersonationSmtpAddress, $attachmentId)
	{
		$soapBody = <<<SOAP
         <m:GetAttachment xmlns="http://schemas.microsoft.com/exchange/services/2006/messages"
                xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types">
          <m:AttachmentShape/>
          <m:AttachmentIds>
            <t:AttachmentId Id="$attachmentId"/>
          </m:AttachmentIds>
        </m:GetAttachment>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $itemId - the ID of the calendar event being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <string>
	 */
	private function GetViewEmailEnvelope($impersonationSmtpAddress, $itemId,$changeKey)
	{
		$soapBody = <<<SOAP
    <m:GetItem>
      <m:ItemShape>
        <t:BaseShape>AllProperties</t:BaseShape>
        <t:IncludeMimeContent>true</t:IncludeMimeContent>
      </m:ItemShape>
      <m:ItemIds>
        <t:ItemId Id="$itemId" ChangeKey="$changeKey" />
      </m:ItemIds>
    </m:GetItem>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $itemId - the ID of the calendar event being retrieved
	 * @param <string> $changeKey - the changeKey is provided to return a specific revision of the item
	 * @return <string>
	 */
	private function GetViewCalendarEnvelope($impersonationSmtpAddress, $itemId, $changeKey)
	{
		$soapBody = <<<SOAP
    <m:GetItem xmlns="http://schemas.microsoft.com/exchange/services/2006/messages">
      <m:ItemShape>
        <t:BaseShape>AllProperties</t:BaseShape>
        <t:AdditionalProperties>
          <t:FieldURI FieldURI="item:Subject"/>
        </t:AdditionalProperties>
      </m:ItemShape>
      <m:ItemIds>
        <t:ItemId Id="$itemId" ChangeKey="$changeKey"/>
      </m:ItemIds>
    </m:GetItem>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $itemId
	 * @param <string> $changeKey
	 * @param <string> $name - the name of the attachment
	 * @param <string> $content - base 64 encoded content
	 * @return <string>
	 */
	private function GetCreateAttachmentEnvelope($impersonationSmtpAddress, $itemId, $changeKey,$name,$content)
	{
		$soapBody = <<<SOAP
  <m:CreateAttachment xmlns="http://schemas.microsoft.com/exchange/services/2006/messages">
    <m:ParentItemId Id="$itemId" ChangeKey="$changeKey"/>
    <m:Attachments>
      <t:FileAttachment>
        <t:Name>$name</t:Name>
        <t:Content>$content</t:Content>
      </t:FileAttachment>
    </m:Attachments>
  </m:CreateAttachment>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Returns the SOAP Envelope that will be sent to EWS
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $unresolvedName - the input string that we are attempting to match
	 * @return <string>
	 */
	private function GetResolveNamesEnvelope($impersonationSmtpAddress, $unresolvedName)
	{
		$soapBody = <<<SOAP
<!-- GetResolveNamesEnvelope -->        
            <m:ResolveNames xmlns="http://schemas.microsoft.com/exchange/services/2006/messages" ReturnFullContactData="false">
              <m:UnresolvedEntry>$unresolvedName</m:UnresolvedEntry>
            </m:ResolveNames>
SOAP;
		return $this->GetSoapEnvelope($impersonationSmtpAddress, $soapBody);
	}

	/**
	 * Creates and returns SOAP Envelope that can be passed to EWS methods
	 * @param <string> $impersonationSmtpAddress - the SMTP address for the impersonation account as set up in configuration
	 * @param <string> $body - a SOAP body
	 * @return <string>
	 */
	private function GetSoapEnvelope($impersonationSmtpAddress, $body)
	{
		//TODO: remove $to $authToken, $action from the parameter list

		$soapHeader = "<soap:Header>
    <t:RequestServerVersion Version='Exchange2010' />
    <t:ExchangeImpersonation>
        <t:ConnectingSID>
           <t:PrimarySmtpAddress>$impersonationSmtpAddress</t:PrimarySmtpAddress>
        </t:ConnectingSID>
    </t:ExchangeImpersonation>

    </soap:Header>";

		/*
		 <t:TimeZoneContext>
		 <t:TimeZoneDefinition Id='' />
		 </t:TimeZoneContext>
		 */

		return <<<SOAP
<?xml version='1.0' encoding='utf-8'?>
<soap:Envelope
        xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
        xmlns:m='http://schemas.microsoft.com/exchange/services/2006/messages'
        xmlns:t='http://schemas.microsoft.com/exchange/services/2006/types'
        xmlns:soap='http://schemas.xmlsoap.org/soap/envelope/'>
        $soapHeader
    <soap:Body>
    $body
    </soap:Body>

</soap:Envelope>
SOAP;
	}

	/**
	 * A helper function used by resolve names to concatenate and display the name and email
	 * in the format: Student One [student.one@domain.com]
	 * @param <string> $displayName - The resolved name returned by ResolveNames
	 * @param <string> $email - The email returned by ResolveNames
	 * @return <string>
	 */
	private function FormatDisplayNameAndEmail($displayName, $email)
	{
		$formattedItem = $displayName.' ['.$email.']';
		return $formattedItem;
	}
	/**
	 * Transforms the text of known catchable exceptions and provides a friendier message
	 * @param <string> $errorMessage
	 * @return <string>
	 */
	private function MakeErrorMessageFriendlier($errorMessage)
	{
		switch($errorMessage)
		{
			case 'ErrorInvalidRecipients':
				$message = 'One or more of the message recipients have been determined to be invalid. Please make sure that your list of recipients is in one of the following formats:\n';
				$message .= 'Recipient Name [recipientname@domain.com]\n';
				$message .='recipientname@domain.com\n\n';
				$message .='If you are entering more than one address, please separate each address with a semicolon.';
				return $message;
				break;
				//we don't know how to make it friendlier, so it will have to do.
			default:
				return $errorMessage;
				break;
		}

	}

}

?>
