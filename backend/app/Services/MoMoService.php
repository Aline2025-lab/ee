<?php
namespace App\Services;

use HTTP_Request2;
use HTTP_Request2_Exception;

class MoMoService{

    private static $subscriptionKey = "365bfe25b09a48eeab2e6e069d4f40a1";
    private static $referenceID = "f238347a-38eb-4f4c-9934-fd8f72bcd7f0";

    private static $providerCallBack = "https://webhook.site/f238347a-38eb-4f4c-9934-fd8f72bcd7f0";


    public static function createApiUser(): string
    {
        $request = new HTTP_Request2();
        $request->setUrl('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser');
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setConfig([
            'follow_redirects' => true
        ]);
        $request->setHeader([
            'X-Reference-Id' => self::$referenceID,
            'Ocp-Apim-Subscription-Key' => self::$subscriptionKey,
            'Content-Type' => 'application/json'
        ]);

        $body = json_encode([
            "providerCallbackHost" => self::$providerCallback
        ]);
        $request->setBody($body);

        try {
            $response = $request->send();
            if ($response->getStatus() == 201) { // MoMo API user creation typically returns 201 Created
                return $response->getBody();
            } else {
                // Return an error message as JSON
                return json_encode([
                    'error' => 'Unexpected HTTP status',
                    'status' => $response->getStatus(),
                    'reason' => $response->getReasonPhrase(),
                    'response_body' => $response->getBody() // Include body for more details
                ]);
            }
        } catch (HTTP_Request2_Exception $e) {
            // Return the exception message as JSON
            return json_encode([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    /**
     * Generates an API key for a previously created MoMo API user.
     *
     * @param string $referenceID Your unique X-Reference-Id used when creating the API user.
     * @param string $subscriptionKey Your Ocp-Apim-Subscription-Key for the MoMo API.
     * @return string A JSON string containing the API key response, or an error message.
     */
    public static function getApiKey(): string
    {
        $request = new HTTP_Request2();
        $request->setUrl("https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/" . self::$referenceID . "/apikey");
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setConfig([
            'follow_redirects' => true
        ]);
        $request->setHeader([
            'Ocp-Apim-Subscription-Key' => self::$subscriptionKey
        ]);

        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                return $response->getBody();
            } else {
                // Return an error message as JSON
                return json_encode([
                    'error' => 'Unexpected HTTP status',
                    'status' => $response->getStatus(),
                    'reason' => $response->getReasonPhrase(),
                    'response_body' => $response->getBody() // Include body for more details
                ]);
            }
        } catch (HTTP_Request2_Exception $e) {
            // Return the exception message as JSON
            return json_encode([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Requests an access token from the MoMo Collection API.
     *
     * @param string $subscriptionKey Your Ocp-Apim-Subscription-Key for the MoMo Collection API.
     * @return string A JSON string containing the response from the API, or an error message.
     */
    public static function getAccessToken($apiKey): string
    {
        $request = new HTTP_Request2();
        $request->setUrl('https://sandbox.momodeveloper.mtn.com/collection/token/');
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setConfig([
            'follow_redirects' => true
        ]);
        $authString = base64_encode(self::$referenceID . ':' . $apiKey);
        $request->setHeader([
            'Ocp-Apim-Subscription-Key' => self::$subscriptionKey,
            'Authorization' => 'Basic ' . $authString
        ]);

        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                return $response->getBody();
            } else {
                // Return an error message as JSON
                return json_encode([
                    'error' => 'Unexpected HTTP status',
                    'status' => $response->getStatus(),
                    'reason' => $response->getReasonPhrase(),
                    'response_body' => $response->getBody() // Include body for more details
                ]);
            }
        } catch (HTTP_Request2_Exception $e) {
            // Return the exception message as JSON
            return json_encode([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ]);
        }
    }



    /**
     * Initiates a MoMo Collection payment request (Request to Pay).
     *
     * @param string $referenceID A unique X-Reference-Id for this specific transaction.
     * @param string $subscriptionKey Your Ocp-Apim-Subscription-Key for the MoMo Collection API.
     * @param string $accessToken The access token obtained from the MoMo Collection /token endpoint.
     * @param MoMoTransaction $moMoTransaction An instance of the MoMoTransaction class representing the payment details.
     * @return string A JSON string containing the response from the API, or an error message.
     */
    public static function requestToPay( string $accessToken, MoMoTransaction $moMoTransaction, $transactionReference): string
    {
        $request = new HTTP_Request2();
        $request->setUrl('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay');
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setConfig([
            'follow_redirects' => true
        ]);
        $request->setHeader([
            'X-Reference-Id' => $transactionReference,
            'X-Target-Environment' => 'sandbox', // Often required for sandbox environments
            'Ocp-Apim-Subscription-Key' => self::$subscriptionKey,
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken // Use the provided accessToken
        ]);

        // Use the toJson() method of the MoMoTransaction object for the body
        $request->setBody($moMoTransaction->toJson());

        try {
            $response = $request->send();
            // MoMo API for requesttopay typically returns 202 Accepted for successful initiation
            // The original code checked for 200, but 202 is more common for asynchronous requests.
            // You might need to adjust this based on specific MoMo API documentation for your region.
            if ($response->getStatus() == 202 || $response->getStatus() == 200) {
                // For 202 Accepted, the body is often empty or just confirms acceptance.
                // For 200, it might contain more details, depending on the specific API implementation.
                return json_encode([
                    'status' => 'success',
                    'message' => 'Payment request successfully initiated or processed.',
                    'referenceId' => self::$referenceID, // The reference ID you provided
                    'http_status' => $response->getStatus(),
                    'response_body' => $response->getBody() // Include body for more details
                ]);
            } else {
                // Return an error message as JSON
                return json_encode([
                    'error' => 'Unexpected HTTP status',
                    'status' => $response->getStatus(),
                    'reason' => $response->getReasonPhrase(),
                    'response_body' => $response->getBody() // Include body for more details
                ]);
            }
        } catch (HTTP_Request2_Exception $e) {
            // Return the exception message as JSON
            return json_encode([
                'error' => 'Request failed',
                'message' => $e->getMessage()
            ]);
        }
    }

}





