---
weight: 20
title: API Reference
---

# SDKs

To start using a SDK, simply download and follow instructions:  

* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-node-typescript" target="_blank">Node.js / TypeScript</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-php" target="_blank">PHP</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-python" target="_blank">Python</a>

## Create a invoice

Creating a invoice in a few lines of code.

> Creating an invoice with our SDK:

```javascript
import { NashLinkApi } from '@neon-exchange/nash-link'

const api = new NashLinkApi('sandbox', `<YOUR_API_KEY>`, `<YOUR_API_SECRET_KEY>`)

// create the invoice
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
invoice = {'price': 10, 'currency': 'EUR'}
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

```shell
# there is no shell sdk, use it for REST API access.
```

> Returns <a href="#invoice">`Invoice` object</a> or <a href="#erros">Error status/message</a> 

## Get a invoice

Getting a invoice in a few lines of code.

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

```shell
# there is no shell sdk, use it for REST API access.
```

> Returns <a href="#invoice">`Invoice` object</a> or <a href="#erros">Error status/message</a> 