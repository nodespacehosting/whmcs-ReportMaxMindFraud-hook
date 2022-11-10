<?php
//This script published by Commercial Network Services and is offered under GNU GPL free to the WHMCS community.
//the more companies using this hook, the stronger all our fraud screening will be.
//It is provided free with the understanding that if you perfect it, you will make it available to the WHMCS community without charge.
/* This hook was updated by NodeSpace Hosting (https://www.nodespace.net) for compatibility with PHP 8.1+
   Actual working status of this hook is unknown. 
   https://github.com/nodespacehosting
*/

function SendToMaxMind($args){
	
	
	$URL = 'http://www.maxmind.com/app/report_fraud_http?l=' . $args['MaxMindKey'] . '&ipaddr=' . $args['ipaddr'] . '&fraud_score=5&txnID=' . $args['txnID'] . '&maxmindID=' . $args['maxmindID'];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$maxmindresponse = curl_exec($ch);
	curl_close($ch);

	$data = explode(";",$maxmindresponse);

		foreach ($data AS $temp) {

			if ($temp != "") {

				$temp = explode("=",$temp);
				$results[$temp[0]] = $temp[1];
			}
		}	
	
	if ($results['status'] == 'success') {return true;}
		
	return false;
	
}


function ReportFraudToMaxMind($vars) {
	
	$txnID = $vars['orderid'];
	
	//Get maxmind key from db
	$query = "SELECT VALUE FROM tblfraud WHERE setting = 'MaxMind License Key'";
	$theresult = mysql_query($query);
	
	if ($theresult) {
		$fieldid = mysql_fetch_array($theresult,MYSQL_ASSOC); 
		$MaxMindKey = $fieldid['VALUE'];
		
	}else{
		
		return false; // no maxmind key, can not continue
	}
	
	//get order data
	$query = "SELECT * FROM tblorders WHERE id=" . $txnID;
	$theresult = mysql_query($query);
	
	if ($theresult) {
		$fieldid = mysql_fetch_array($theresult,MYSQL_ASSOC); 
		$IPAddress = $fieldid['ipaddress'];
		$FraudOutput = $fieldid['fraudoutput'];
	
	}else{
		
		return false; // no maxmind key, can not continue
	}
	
	
	
	//report it
	$data = explode("\n",$FraudOutput);

	foreach ($data AS $temp) {

		if ($temp != "") {

			$temp = explode(" => ",$temp);
			$ParsedFraudResults[$temp[0]] = $temp[1];
		}
	}


	$args['txnID'] = $txnID;
	$args['MaxMindKey'] = $MaxMindKey;
	$args['ipaddr'] = $IPAddress;
	$args['fraud_score'] = "5";
	$args['maxmindID'] = $ParsedFraudResults['maxmindID'];
	
	return (SendToMaxMind($args));
	
}

add_hook("FraudOrder",1,"ReportFraudToMaxMind","");
?>
