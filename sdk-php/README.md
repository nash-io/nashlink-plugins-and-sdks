# NashLinkApi Install

You can install the library on your project by using composer:   
  
```lang=bash
$ composer require nash/link
```
  
Or, just directly require this repository NashLinkApi.php library file into your php files.  
  
## Usage

Create an invoice:  
  
```php
<?php

// via composer:
require_once 'vendor/autoload.php';
// via library file:
//require_once 'NashLinkApi.php';

use Nash\Link\NashLinkApi;

// instantiate api
// use 'sandbox' for integration tests
// use 'prod' for final production environment
$api = new NashLinkApi('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>');

// setup order data for the invoice
$invoice_data = array("price" => 1, "currency" => "EUR", "orderId" => "00000001", "redirectURL" => "http://mystore.com/orders/00000001", "notificationURL" => "http://mystore.com/orders/ipn");

// create the invoice
$response = $api->createInvoice($invoice_data);

// check for errors
if ($response['error'] == true) {
  print $response['message'];
  print $response['status_code'];
  return;
}

// created invoice data
var_dump($response['data'])
```
  
Get and invoice:  

```php
<?php

// via composer:
require_once 'vendor/autoload.php';
// via library file:
//require_once 'NashLinkApi.php';

use Nash\Link\NashLinkApi;

// instantiate api
// use 'sandbox' for integration tests
// use 'prod' for final production environment
$api = new NashLinkApi('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>');

// get the invoice
$response = $api->getInvoice('<INVOICE_ID>');

// check for errors
if ($response['error'] == true) {
  print $response['message'];
  print $response['status_code'];
  return;
}

// invoice data
var_dump($response['data'])
```
