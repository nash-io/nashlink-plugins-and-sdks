---
weight: 40
title: API Reference
---

# Rest API

Nash link provides a standards-based REST interface which enables application developers to interact in a powerful, yet secure way with their Nash link account. Developers may call the API directly over HTTPS using the language of their choice, or take advantage of one of Nash link code libraries (Javascript, Python and PHP).  

For a shell environment we higly recommends curl, openssl and sed to sign requests.  

## API URLs

The Nash Link API base URLs are:

* `https://link.nash.io/api/v1/sandbox` for sandbox
* `https://link.nash.io/api/v1/prod` for production

Note: You need to use an API key created for the specific environment.

## Request headers

All authenticated requests need to include the following HTTP headers:

* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="#signing-requests">here</a>

We suggest you to use openssl for signatures since its opensource and widely avaliable over different operational systems.

## Signing requests

> Sign you payload:

```javascript
import * as crypto from 'crypto'

const apiSecretKey = '<YOUR_API_SECRET_KEY>'
const environment = 'https://link.nash.io/api/v1/sandbox/invoices'

const invoice = {"price": 10, "currency": "EUR"}
const payload = environment+JSON.stringify(invoice)
const signature = crypto.createHmac("sha256", apiSecretKey).update(payload).digest("hex")
```

```php
<?php
const API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
const ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices';

$invoice_data = array("price" => 10, "currency" => "EUR");
$payload = ENVIRONMENT . json_encode($invoice_data)
$signature = hash_hmac('sha256', $payload, API_SECRET_KEY);
```

```python
import hmac
import hashlib

API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices';

invoice = {'price': 10, 'currency': 'EUR'}
payload = ''.join([ENVIRONMENT,str(json.dumps(invoice))])
signature = hmac.new(API_SECRET_KEY.encode('utf-8'), 
                     msg=payload.encode('utf-8'),
                     digestmod=hashlib.sha256).hexdigest().upper()
```

```shell
#!/bin/sh
API_SECRET_KEY='<YOUR_API_SECRET_KEY>'
ENVIRONMENT='https://link.nash.io/api/v1/sandbox/invoices'

INVOICE='{"price": 10, "currency": "EUR"}'
# make use of openssl + sed
SIGNATURE=`echo -n "${ENVIRONMENT}${INVOICE}" | openssl dgst -sha256 -hmac "${API_SECRET_KEY}" | sed 's/(stdin)= //g'`
```

Authenticated requests need to be signed with the API secret key. You need to concatenate the URL with the request body, create a HMAC-SHA256 signature, then send this as the `x-signature` header.

<aside class="notice">If you use <a href="#sdks">our SDKs</a> you don’t need to sign requests manually.</aside>

For example, say you wish to send a request to `https://link.nash.io/api/v1/sandbox/invoices` with this payload:

`
{
    "price": 10,
    "currency": "EUR"
}
`

You’d concatenate the URL and request body like this:

`https://link.nash.io/api/v1/sandbox/invoices{"price": 10, "currency": "EUR"}`

This string is then signed using <a href="https://en.wikipedia.org/wiki/HMAC" target="_blank">HMAC-SHA256</a> and the API secret key.

The resulting signature needs to be sent in the `x-signature` HTTP header.

## Create an invoice

> Create a Invoice with Rest call:

```javascript
import * as crypto from 'crypto'

const apiKey = '<YOUR_API_KEY>'
const apiSecretKey = '<YOUR_API_SECRET_KEY>'
const environment = 'https://link.nash.io/api/v1/sandbox/invoices'

const invoice = {"price": 10, "currency": "EUR"}
const payload = environment+JSON.stringify(invoice)
const signature = crypto.createHmac("sha256", apiSecretKey).update(payload).digest("hex")

const headers = {
  'Accept': 'application/json';
  'Content-Type': 'application/json',
  'x-identity': apiKey,
  'x-signature': signature
}

const fetchPromise = fetch(environment, {
  method: 'post',
  body: JSON.stringify(data),
  headers
})
```

```php
<?php
const API_KEY = '<YOUR_API_KEY>';
const API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
const ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices';

$invoice_data = array("price" => 10, "currency" => "EUR");
$payload = ENVIRONMENT . json_encode($invoice_data)
$signature = hash_hmac('sha256', $payload, API_SECRET_KEY);

$request_headers = array();
$request_headers[] = 'Accept: application/json';
$request_headers[] = 'Content-Type: application/json';
$request_headers[] = 'x-identity: ' . API_KEY;
$request_headers[] = 'x-signature: ' . $signature;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ENVIRONMENT);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invoice_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
        
```

```python
import hmac
import hashlib

API_KEY = '<YOUR_API_KEY>';
API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices';

invoice = {'price': 10, 'currency': 'EUR'}
payload = ''.join([ENVIRONMENT,str(json.dumps(invoice))])
signature = hmac.new(API_SECRET_KEY.encode('utf-8'), 
                     msg=payload.encode('utf-8'),
                     digestmod=hashlib.sha256).hexdigest().upper()

request_headers = {'Accept': 'application/json',
                   'Content-Type': 'application/json',
                   'x-identity': API_KEY,
                   'x-signature': signature}

r = requests.post(url=ENVIRONMENT, headers=request_headers, json=invoice)
```

```shell
#!/bin/sh
API_KEY='<YOUR_API_KEY>'
API_SECRET_KEY='<YOUR_API_SECRET_KEY>'
ENVIRONMENT='https://link.nash.io/api/v1/sandbox/invoices'

INVOICE='{"price": 10, "currency": "EUR"}'
# make use of openssl + sed
SIGNATURE=`echo -n "${ENVIRONMENT}${INVOICE}" | openssl dgst -sha256 -hmac "${API_SECRET_KEY}" | sed 's/(stdin)= //g'`

curl \
-H 'Accept: application/json' \
-H 'Content-Type: application/json' \
-H "x-identity: ${API_KEY}" \
-H "x-signature: ${SIGNATURE}" \
-X POST \
-d "$INVOICE" \
$ENVIRONMENT
```

`POST /invoices`

> Returns <a href="#invoice">`Invoice` object</a> or <a href="#erros">Error status/message</a> 

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="#signing-requests">here</a>

### Request body (JSON)

**Mandatory fields**

Name | Type | Description
-------------- | -------------- | --------------
`price` | number | Amount in fiat
`currency` | string | Fiat currency, in ISO 4217 3-character currency code. Must be `EUR` currently.

<br>

**Optional fields**

Name | Type | Description
-------------- | -------------- | --------------
`orderId` | string | Merchant order reference ID
`itemDesc` | string | Description of the purchase
`posData` | string | Passthru variable for Merchant internal use
`notificationEmail` | string | Email address of the merchant to receive notifications about invoice status changes
`notificationURL` | string | URL of the merchant backend to enable and receive webhooks relating to invoice status changes
`redirectURL` | string | URL to redirect the user to after a successful purchase

### Response

An `Invoice` object as described in the <a href="#invoice">`Invoice` object documentation</a>.


## Get an invoice

`GET /invoices/<invoiceId>`


```javascript
import * as crypto from 'crypto'

const apiKey = '<YOUR_API_KEY>'
const apiSecretKey = '<YOUR_API_SECRET_KEY>'
const environment = 'https://link.nash.io/api/v1/sandbox/invoices/'

const invoiceId = '<INVOICE_ID>'
const payload = environment+invoiceId
const signature = crypto.createHmac("sha256", apiSecretKey).update(payload).digest("hex")

const headers = {
  'Accept': 'application/json';
  'Content-Type': 'application/json',
  'x-identity': apiKey,
  'x-signature': signature
}

const fetchPromise = fetch(environment+invoiceId, { headers })
```

```php
<?php
const API_KEY = '<YOUR_API_KEY>';
const API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
const ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices/';

$invoice_id = '<INVOICE_ID>';
$payload = ENVIRONMENT . invoice_id
$signature = hash_hmac('sha256', $payload, API_SECRET_KEY);

$request_headers = array();
$request_headers[] = 'Accept: application/json';
$request_headers[] = 'Content-Type: application/json';
$request_headers[] = 'x-identity: ' . API_KEY;
$request_headers[] = 'x-signature: ' . $signature;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ENVIRONMENT . invoice_id);
curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
        
```

```python
import hmac
import hashlib

API_KEY = '<YOUR_API_KEY>';
API_SECRET_KEY = '<YOUR_API_SECRET_KEY>';
ENVIRONMENT = 'https://link.nash.io/api/v1/sandbox/invoices/';

invoice_id = '<INVOICE_ID>'
payload = ''.join([ENVIRONMENT,invoice_id])
signature = hmac.new(API_SECRET_KEY.encode('utf-8'), 
                     msg=payload.encode('utf-8'),
                     digestmod=hashlib.sha256).hexdigest().upper()

request_headers = {'Accept': 'application/json',
                   'Content-Type': 'application/json',
                   'x-identity': API_KEY,
                   'x-signature': signature}

r = requests.get(url=''.join([ENVIRONMENT,invoice_id]), headers=request_headers)
```

```shell
#!/bin/sh
API_KEY='<YOUR_API_KEY>'
API_SECRET_KEY='<YOUR_API_SECRET_KEY>'
ENVIRONMENT='https://link.nash.io/api/v1/sandbox/invoices/'

INVOICE_ID='<INVOICE_ID>'
# make use of openssl + sed
SIGNATURE=`echo -n "${ENVIRONMENT}${INVOICE_ID}" | openssl dgst -sha256 -hmac "${API_SECRET_KEY}" | sed 's/(stdin)= //g'`

curl \
-H 'Accept: application/json' \
-H 'Content-Type: application/json' \
-H "x-identity: ${API_KEY}" \
-H "x-signature: ${SIGNATURE}" \
${ENVIRONMENT}${INVOICE_ID}
```

> Returns <a href="#invoice">`Invoice` object</a> or <a href="#erros">Error status/message</a> 

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="#signing-requests">here</a>

### Response

Invoice object as described in the <a href="#invoice">`Invoice` object documentation</a>

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="#signing-requests">here</a>

### Response

The following JSON payload: `{ "sent": true }`

## Invoice webhooks

After each <a href="#invoice-states">invoice state</a> transition, a POST request is sent to the `notificationURL` field provided when creating the invoice. No webhook is sent when the invoice is created.

If a request fails, we retry the webhook up to 20 times, waiting `5min * attempt` between retries.

You can also manually trigger the latest webhook call for an invoice by calling the <a href="#trigger-webhook">Trigger webhook endpoint</a>.

<aside class="notice">
Note: To prevent eavesdropping, we recommend securing your callback URL by using SSL and providing a secret parameter appended to the callback URL. We send the payload to the unaltered URL, which allows you to check on your server that the parameter was not modified.
</aside>


## Setup a webhook endpoint

At the creation of invoice, you can setup a webhook url so Nash link servers can callback your merchant server and inform about any invoice status update. To make use of this feature just set `notificationURL` param with you webhook endpoint.

## Receiving webhook data

When Nash link servers call you merchant webhook endpoint you will receive a <a href="#invoice">`Invoice` object data</a>

## Trigger webhook

`GET /invoices/<invoiceId>/trigger_webhook`

```bash
GET /invoices/JDBtJCFV/trigger_webhook

Response:

{ "sent": true }
```

### Request headers

* `content-type`: `application/json`

### Request body (JSON)

Invoice object as described in the <a href="#invoice">`Invoice` object documentation</a>

<b>See also:</b>

* <a href="#invoice-states">Invoice states</a>
