<?php
// File: vendor/myvendor/mypackage/src/MyClass.php

namespace paytm\payment;

require_once __DIR__ . '/lib/PaytmChecksum.php'; // Manually include the file


use paytm\payment\src\lib\PaytmChecksum;

use Cake\Http\Client;


class Paytm
{
    public function initiatePayment($userdata)
    {
        // Implement the logic to initiate the payment with Paytm API
        // Use the $orderId, $amount, and other Paytm parameters
        // Return the Paytm payment gateway URL or form data

        $env = env('PAYTM_ENVIRONMENT');
   		$paytmParams = self::paytmCustomArray($userdata,$env);

		/*
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams, JSON_UNESCAPED_SLASHES), env('PAYTM_MERCHANT_KEY'));

		$postData=array();
		$postData["body"] = $paytmParams;
		$postData["head"] = array(
			"signature"	=> $checksum
		);
		$post_data = json_encode($postData, JSON_UNESCAPED_SLASHES);

		if(env('PAYTM_ENVIRONMENT')=='production'){
			/* for Production */
		 	$url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=".env('PAYTM_MERCHANT_ID')."&orderId=".$userdata['orderId'];
		}else{
			/* for Staging */
			$url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=".env('PAYTM_MERCHANT_ID')."&orderId=".$userdata['orderId'];
		}

        $client = new Client();
        $response = $client->post($url, $post_data, ['type' => 'json']);
        $responseData = $response->getJson();
	
		if(!empty($responseData['body']['resultInfo']['resultStatus']) && $responseData['body']['resultInfo']['resultStatus'] == 'S'){
			$txntoken = $responseData['body']['txnToken'];
			return $txntoken;
		}
	}


	public function paytmCustomArray($paytmParams=array(),$env){

		if (!array_key_exists("requestType", $paytmParams)) {
		    $paytmParams["requestType"] = "Payment";
		}

		if (!array_key_exists("userInfo", $paytmParams)) {
			$paytmParams['userInfo'] = array(
			    "custId" => "CUST_" . time()
			);
		}

		$txnAmount = $paytmParams['txnAmount'];

		$paytmParams['txnAmount'] = array(
		    "value" => $txnAmount,
		    "currency" => "INR"
		);	

		if (!array_key_exists("websiteName", $paytmParams)) {
			$paytmWebsite = (strpos($env, "stage") == true) ? "WEBSTAGING" : "DEFAULT";
		    $paytmParams["websiteName"] = $paytmWebsite;
		}

		return $paytmParams;
	}

    public function verifyPaymentResponse($postData)
    {
        // Implement the logic to verify the payment response from Paytm
        // Use the $postData received from Paytm callback
        // Return true if the payment is successful, false otherwise
        if(!empty($postData['CHECKSUMHASH'])){
			$post_checksum = $postData['CHECKSUMHASH'];
			unset($postData['CHECKSUMHASH']);	
		}else{
			$post_checksum = "";
		}

        //verify checksum
	 	if(!PaytmChecksum::verifySignature($postData, env('PAYTM_MERCHANT_KEY'), $post_checksum) === true){
	 		$responseData['body']['resultInfo'] = array(
	 			'resultMsg'=>"Security Error"
	 		); 
        	return $responseData;
        }
		/* initialize an array */
		$paytmParams = array();

		/* body parameters */
		$paytmParams["body"] = array(

		    /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
		    "mid" => env('PAYTM_MERCHANT_ID'),

		    /* Enter your order id which needs to be check status for */
		    "orderId" => $postData['ORDERID'],
		);

		/**
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), env('PAYTM_MERCHANT_KEY'));

		/* head parameters */
		$paytmParams["head"] = array(

		    /* put generated checksum value here */
		    "signature"	=> $checksum
		);

		/* prepare JSON string for request */
		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
		if(env('PAYTM_ENVIRONMENT')=='production'){
	        /* for Production */
			$url = "https://securegw.paytm.in/v3/order/status";
	    }else{
	    	/* for Staging */
			$url = "https://securegw-stage.paytm.in/v3/order/status";
	    }

	    $client = new Client();
        $response = $client->post($url, $post_data, ['type' => 'json']);
        $responseData = $response->getJson();
        return $responseData;

    }
}
