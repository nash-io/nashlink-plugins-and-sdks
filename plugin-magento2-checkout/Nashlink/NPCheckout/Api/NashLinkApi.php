<?php

namespace Nashlink\NPCheckout\Api;

const SERVER_URI = 'https://link.nash.io';
const SERVER_API = '/api/v1/';

class NashLinkApi
{
    private $serverUri = SERVER_URI;
    private $serverApi = SERVER_API;
    protected $apiKey;
    protected $secretKey;
    protected $environment;

    function __construct($environment, $apiKey, $secretKey) 
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->environment = $environment;
    }
    
    public function createInvoice($data)
    {
        $post_fields = json_encode($data);
        $endpoint = $this->serverUri . $this->serverApi . $this->environment . '/invoices';
        $signature = $this->signRequest($endpoint . $post_fields);
        $request_headers = $this->getRequestHeader($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->handleResponseData($result, $status_code);
    }

    public function getInvoice($id)
    {
        $endpoint = $this->serverUri . $this->serverApi . $this->environment . '/invoices/' . $id;
        $signature = $this->signRequest($endpoint);
        $request_headers = $this->getRequestHeader($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $this->handleResponseData($result, $status_code);
    }

    public function signRequest($payload)
    {
        return hash_hmac('sha256', $payload, $this->secretKey);
    }

    private function getRequestHeader($signature) 
    {
        $request_headers = array();
        $request_headers[] = 'X-NashLink-Api-Info: 1.0.0';
        $request_headers[] = 'Accept: application/json';
        $request_headers[] = 'Content-Type: application/json';
        $request_headers[] = 'x-identity: ' . $this->apiKey;
        $request_headers[] = 'x-signature: ' . $signature;

        return $request_headers;
    }

    private function handleResponseData($response, $status_code)
    {
        // from json to php array
        $data = json_decode($response, true);
        // handle errors
        if (!is_array($data)) {
            $data = array('error' => true, 'status_code' => $status_code, 'message' => $response);
        }
        if ($status_code != 200) {
            if (!array_key_exists('error', $data)) {
                // no error message from server?
                $data['error'] = true;
                $data['message'] = 'Error: ' . $response;
            }
            $data['status_code'] = $status_code;
        } else {
            // no errors
            $data['error'] = false;
        }

        return $data;
    }

    public function getServerUri() 
    {
        return $this->serverUri;
    }

    public function getServerApi() 
    {
        return $this->serverApi;
    }    
}