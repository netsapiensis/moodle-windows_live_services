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

require_once( '../../../../config.php' );
require_once( $CFG->dirroot . '/lib/moodlelib.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews.php' );
require_once( $CFG->dirroot . '/blocks/live_services/services/email/ews_auth.php' );
require_once($CFG->dirroot . '/blocks/live_services/shared/utils.php');
$loggedInLiveId = $_COOKIE['wls_liveId'];
$resolvedNames = '';

// Check to see if ExchangeLabs is enabled for this moodle instance.
if( @$CFG->block_live_services_useEWS == 1)
{
    // This username/password needs to be that of the Impersonation Account
    $impersonationLiveId = @$CFG->block_live_services_ewsServiceAccountUserName;
    $impersonationPassword = @$CFG->block_live_services_ewsServiceAccountPassword;
    $ews_auth = new EWSAuthentication($impersonationLiveId, $impersonationPassword);
    $exchangeServiceData = $ews_auth->AuthenticateAgainstEWSEndPoint( false );
    $exchangeEnabled = true;
}
else
{
    $exchangeEnabled = false;
}

//$query is the unresolved name
$query = @optional_param('query','', PARAM_TEXT);

if($query=='')
{
	// don't precache anything
	echo('Success');	
	return;
}
else
{
	$names = getUnresolvedNameFromQuery($query);
	$resolvedNames = htmlspecialchars($names[0]);
	$unresolvedName = $names[1];
    
	if( strlen($unresolvedName) > 2 )
	{
		echo FindMatches($loggedInLiveId, $exchangeServiceData, $unresolvedName, $resolvedNames);
	}
}
/**
 * reads the querystring and returns the portion of the querystring that we want to resolve
 * for example, if we receive teacher1@dthe.edu;stu, we only want to resolve "stu"
 * @param <string> $query - the entire string that is in the "To:" textbox
 * @return <string>
 */
function getUnresolvedNameFromQuery($query)
{

    $namesArray = array();
    $token = strtok(urldecode($query), ";");
    if($token==$query)
    {
        return array('',$token);
    }
    else
    {
        while ($token != false)
        {
            Array_push($namesArray,$token);
            $token = strtok(";");
        }
        $unresolvedName = Array_pop($namesArray);
        $resolvedNamesTemp = array_reduce($namesArray,"getResolvedNamesString");
        if(strpos($resolvedNamesTemp,';')==0)
        {
            $resolvedNamesTemp = substr($resolvedNamesTemp,1);
        }
        $resolvedNames = $resolvedNamesTemp.';';
        $returnValue = array($resolvedNames,$unresolvedName);
        return $returnValue;
    }
}
/**
 * concatenates resolved names using the semicolon separator
 * @param <string> $name1 - the first name
 * @param <string> $name2 - the second name
 * @return <string>
 */
function getResolvedNamesString($name1,$name2)
{
    return $name1.';'.$name2;
}

function setAllContacts($loggedInLiveId, $exchangeServiceData)
{
    if(!isset($_SESSION['msm_contacts']))
    {
        $ews = new EWSWrapper();
        $ewsQuery = new EWSQueryResult(0);
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $i = 0;
        for($i=0;$i < 26;$i++)
        {
            $letter = substr($letters, $i , 1);
            $names = $ews->ResolveNames($loggedInLiveId, $exchangeServiceData, $letter);
            if(is_object($names))
            {
                $currentItems = array($ewsQuery->getItems());
                $results = json_decode($names->getResultString());
                if($results->code=="0")
                {
                    $newItems = array($names->getItems());
                    if(count($newItems)>0)
                    {
                        if($currentItems[0]!=null)
                        {
                            $merged = array_merge($currentItems[0],$newItems[0]);
                            $ewsQuery->setItems($merged);
                        }
                        else
                        {
                            $ewsQuery->setItems($newItems[0]);
                        }
                    }
                }
            }
        }
        
        $items = $ewsQuery->getItems();
        if(count($items[0] > 0))
        {
            $code = '0';$reason='Success';$error='false';$exceptionmessage='';
            $resultString = getJsonResultString($code,$reason,$error,$exceptionmessage);
            $ewsQuery->setResultString($resultString);
            $results = json_decode($resultString);
            $names = $items;
            $resultsArray = array("ResultSet"=>array("query"=>"","resolvedNames"=>"","code"=>$results->code,"reason"=>$results->reason,"error"=>$results->error,"exceptionmessage"=>$results->exceptionmessage,"Result"=>$names));
            $_SESSION['msm_contacts'] = json_encode($resultsArray);
        }
        else
        {
            $code = '-1';$reason='Failure';$error='true';$exceptionmessage='Failed to retrieve resolvable names from the EWS service. No names returned';
            $resultString = getJsonResultString($code,$reason,$error,$exceptionmessage);
            $results = json_decode($resultString);
            $resultsArray = array("ResultSet"=>array("query"=>"","resolvedNames"=>"","code"=>$results->code,"reason"=>$results->reason,"error"=>$results->error,"exceptionmessage"=>$results->exceptionmessage,"Result"=>$names));
            unset($_SESSION['msm_contacts']);
        }
    }
}
function getResultsArray($unresolvedName, $resolvedNames)
{
    $names = filterResolvedNames($unresolvedName);  
    $results = json_decode(getResultStringFromSession());
    $resultsArray = array("ResultSet"=>array("query"=>$unresolvedName,"resolvedNames"=>$resolvedNames,"code"=>$results->code,"reason"=>$results->reason,"error"=>$results->error,"exceptionmessage"=>$results->exceptionmessage,"Result"=>$names));
    return json_encode($resultsArray);
}

/**
 * attempts to remove duplicates from the list of resolved names
 * @param <string> $unresolvedName - the partial name that we are trying to resolve
 * @return <array>
 */
function filterResolvedNames($unresolvedName)
{
    $resultSet = json_decode($_SESSION['msm_contacts']);
    $filteredResolvedNames = array();
    for($i=0;$i<count($resultSet->ResultSet->Result);$i++)
    {
        $resolvedName = $resultSet->ResultSet->Result[$i]->ResolvedName;
        if(stripos($resolvedName,$unresolvedName)!==FALSE)
        {
            array_push($filteredResolvedNames,$resolvedName);
        }
    }
    $filteredResolvedNames = array_unique($filteredResolvedNames);
    $uniqueResolvedNames = array();
    for($i=0;$i<count($filteredResolvedNames);$i++)
    {
        if(isset($filteredResolvedNames[$i])) {
            $uniqueResolvedNames[] = array('rn'=>$filteredResolvedNames[$i]);
        }
    }    
    return $uniqueResolvedNames;
}

/**
 * converts the contacts in session to JSON
 * @return <string> (JSON)
 */
function getResultStringFromSession()
{
   if(isset($_SESSION['msm_contacts']));
   {
       $resultSet = json_decode($_SESSION['msm_contacts']);
       
       $code = $resultSet->ResultSet->code;
       $reason = $resultSet->ResultSet->reason;
       $error = $resultSet->ResultSet->error;
       $exceptionmessage = $resultSet->ResultSet->exceptionmessage;
       
       return getJsonResultString($code, $reason, $error, $exceptionmessage);
   }
   return getJsonResultString('-1','Contacts not found in session','false','Contacts not found in session');
}

/**
 * Returns JSON in the case that there are no results found
 * @param <string> $unresolvedName - the partial name that we are trying to resolve
 * @param <string> $resolvedNames - a list of names that have already been resolved.
 * @return <string> JSON
 */
function getNoResultsArray($unresolvedName, $resolvedNames)
{
    $names = array("rn"=>"No results found");
    $resultsArray = array("ResultSet"=>array("query"=>$unresolvedName,"resolvedNames"=>$resolvedNames,"code"=>"0","reason"=>"No results found","error"=>"false","exceptionmessage"=>"","Result"=>$names));
    return json_encode($resultsArray);
}

// ---------------------------------------------------------------------------
// -- NEW Functions used to load and cache e-mail name resolution on demand --
// ---------------------------------------------------------------------------
/**
 * gets the list of possible matches and returns them as JSON.
 */
function FindMatches($loggedInLiveId, $exchangeServiceData, $unresolvedName, $resolvedNames)
{
	
	// if we have cached the results then use the cached results	 
	if( isset($_SESSION['rnCache'.$unresolvedName]) ) {
        
        // build the result array which includes the list of already resolved names
        // and the list of matches for the unresolvedName
        $resultsArray = array("ResultSet"=>array(   "query"=>$unresolvedName,
                                "resolvedNames"=>$resolvedNames,
                                "code"=>"0",
                                "reason"=>"Success",
                                "error"=>"false",
                                "exceptionmessage"=>"",
                                "Result"=> json_decode($_SESSION['rnCache'.$unresolvedName])) );

        return json_encode($resultsArray);                                
        
	}	

	// make a call to SearchContacts which will return the list of contacts the matched the $unresolvedName input param
	$resultSet = json_decode(SearchContacts($loggedInLiveId, $exchangeServiceData, $unresolvedName));    

	// repackage the array of matches into an associative array of 'rn' => 'email_address'	
	$uniqueResolvedNames = array();	
	for($i=0;$i<count($resultSet->ResultSet->Result);$i++)
	{
		if( isset($resultSet->ResultSet->Result[$i]->ResolvedName) ) {
			$uniqueResolvedNames[] = array('rn'=>$resultSet->ResultSet->Result[$i]->ResolvedName);
		}
	} 

    // build the result array which includes the list of already resolved names
    // and the list of matches for the unresolvedName
	$resultsArray = array("ResultSet"=>array(	"query"=>$unresolvedName,
							"resolvedNames"=>$resolvedNames,
							"code"=>$resultSet->ResultSet->code,
							"reason"=>$resultSet->ResultSet->reason,
							"error"=>$resultSet->ResultSet->error,
							"exceptionmessage"=>$resultSet->ResultSet->exceptionmessage,
							"Result"=> $uniqueResolvedNames ) );

	// convert the results array to json							
	$encodedResultsArray = json_encode($resultsArray);
		
	// if there was at least one item that matched, cache the results
	if( count($uniqueResolvedNames) > 0 ) 
	{
        $_SESSION['rnCache'.$unresolvedName] = json_encode($uniqueResolvedNames); //$encodedResultsArray;                           
	}

	// return the json encode results
	return $encodedResultsArray;
} 

/**
 * Searches for Contacts that start with $startsWith paramter.  This function will cache the results and use the cached results
 * on subsequent requests.
 */
 function SearchContacts($loggedInLiveId, $exchangeServiceData, $startsWith)
 {
	// initiat the class which will be used to query exchange via the exchange webservices
	$ews = new EWSWrapper();
	$ewsQuery = new EWSQueryResult(0);

	// ResolveNames method is used to return email address that match the given query
	$names = $ews->ResolveNames($loggedInLiveId, $exchangeServiceData, $startsWith );

	// if we go something back from ResolveNames we will check the return codes and
	// get the items returns
	if(is_object($names))
	{
		$currentItems = array($ewsQuery->getItems());
		$results = json_decode($names->getResultString());

		if($results->code=="0")
		{
			$newItems = array($names->getItems());
			if(count($newItems)>0)
			{
				if($currentItems[0]!=null)
				{
					$merged = array_merge($currentItems[0],$newItems[0]);
					$ewsQuery->setItems($merged);
				}
				else
				{
					$ewsQuery->setItems($newItems[0]);
				}
			}
		}
	}

	// extract the items that were returned from the EWS call
	$items = $ewsQuery->getItems();
	
	// if we have items in the list then success, otherwise failure.
	if(count($items[0]) > 0)
	{

		$code = '0';
		$reason='Success';
		$error='false';
		$exceptionmessage='';
		$resultString = getJsonResultString($code,$reason,$error,$exceptionmessage);
		
		$ewsQuery->setResultString($resultString);
		$results = json_decode($resultString);
		$names = $items;

		$resultsArray = array("ResultSet"=>array(	"query"=>"",
								"resolvedNames"=>"",
								"code"=>$results->code,
								"reason"=>$results->reason,
								"error"=>$results->error,
								"exceptionmessage"=>$results->exceptionmessage,
								"Result"=>$names) );
							
		return json_encode($resultsArray);
	}
	else
	{
		$code = '-1';$reason='Failure';$error='true';$exceptionmessage='Failed to retrieve resolvable names from the EWS service. No names returned';
		$resultString = getJsonResultString($code,$reason,$error,$exceptionmessage);
		$results = json_decode($resultString);
		$resultsArray = array("ResultSet"=>array("query"=>"","resolvedNames"=>"","code"=>$results->code,"reason"=>$results->reason,"error"=>$results->error,"exceptionmessage"=>$results->exceptionmessage,"Result"=>$names));

		return json_encode($resultsArray); 
	}
}

?>
