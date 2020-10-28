---
weight: 10
title: API Reference
---

Welcome to the Nash Link API!

This REST API is highly compatible with <a href="https://bitpay.com/api" target="_blank">BitPay</a> and <a href="https://btcpayserver.org/" target="_blank">BTCPay</a>. It allows developers to interact with Nash Link securely for creating invoices as a merchant and retrieving invoices as a user.

# Introduction

## Payment sequence

Click to enlarge:

{{< figure src="/images/payment-sequence.png" >}}


## SDKs

> Creating an invoice with our SDK:

```javascript
import { NashLinkApi } from '@neon-exchange/nash-link'

const api = new NashLinkApi('sandbox', `<YOUR_API_KEY>`, `<YOUR_API_SECRET_KEY>`)
const invoiceResponse = await api.createInvoice({
  price: 10
  currency: 'EUR'
})
```

```php
<?php
require_once 'NashLinkApi.php';

use Nash\Link\NashLinkApi;

$api = new NashLinkApi('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>');
$invoice_data = array("price" => 10, "currency" => "EUR");

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

```python
from nashlink import nashlink

# instantiate api
# use 'sandbox' for integration tests
# use 'prod' for final production environment
nashlink_api = nashlink.Api('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>')
# setup order data for the invoice
invoice = {'price': 1, 'currency': 'EUR'}
# create the invoice
response = nashlink_api.create_invoice(invoice)

# check for errors
if response['error']:
    print(response['message'])
    print(response['status_code'])
    exit()

# created invoice data
print(str(response['data']))
```

> Getting an invoice data with our SDK:

```javascript
import { NashLinkApi } from '@neon-exchange/nash-link'

const api = new NashLinkApi('sandbox', `<YOUR_API_KEY>`, `<YOUR_API_SECRET_KEY>`)
const invoiceResponse = await api.getInvoice('<INVOICE_ID>')
```

```php
<?php
require_once 'NashLinkApi.php';

use Nash\Link\NashLinkApi;

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

```python
from nashlink import nashlink

# instantiate api
# use 'sandbox' for integration tests
# use 'prod' for final production environment
nashlink_api = nashlink.Api('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>')
# get a invoice
response = nashlink_api.get_invoice('<INVOICE_ID>')

# check for errors
if response['error']:
    print(response['message'])
    print(response['status_code'])
    exit()

# invoice data
print(str(response['data']))
```

We provide well-maintained SDKs for several programming languages. These are a slim layer to simplify API access (in particular, request signing):

* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/master/sdk-node-typescript" target="_blank">Node.js / TypeScript</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/master/sdk-php" target="_blank">PHP</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/master/sdk-python" target="_blank">Python</a>

# Making requests

## API URLs

The Nash Link API base URLs are:

* `https://link.nash.io/api/v1/sandbox` for sandbox
* `https://link.nash.io/api/v1/prod` for production

Note: You need to use an API key created for the specific environment.

## Request headers

All authenticated requests need to include the following HTTP headers:

* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="?javascript#signing-requests">here</a>

## Signing requests

> You can use our SDK to create the signature:

```javascript
import { signRequest } from '@neon-exchange/nash-link'

const url = "https://link.nash.io/api/v1/sandbox/invoices"
const body = { price: 40, currency: "EUR" }
const signingResult = signRequest({ url, body, secretKey: `<YOUR_API_SECRET_KEY>` })
```

Authenticated requests need to be signed with the API secret key. You need to concatenate the URL with the request body, create a HMAC-SHA256 signature, then send this as the `x-signature` header.

<aside class="notice">If you use <a href="#sdks">our SDKs</a> you don’t need to sign requests manually.</aside>

For example, say you wish to send a request to `https://link.nash.io/api/v1/sandbox/invoices` with this payload:

`
{
    "price": 40,
    "currency": "EUR"
}
`

You’d concatenate the URL and request body like this:

`https://link.nash.io/api/v1/sandbox/invoices{"price":40,"currency":"EUR"}`

This string is then signed using <a href="https://en.wikipedia.org/wiki/HMAC" target="_blank">HMAC-SHA256</a> and the API secret key.

The resulting signature needs to be sent in the `x-signature` HTTP header.

If you want to implement the signing yourself, take a look at our [example code](#sdks).
