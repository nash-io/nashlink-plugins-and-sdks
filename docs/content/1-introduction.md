---
weight: 10
title: API Reference
---

# Introduction

The Nash Link Payment Gateway API is designed for merchants that need full control over their customers’ shopping and checkout experience or for single person who wants to bill in crypto, receive in fiat.  

There are four interactions with the Nash Link service that this API enables:  
  
  ● create an invoice  
  ● fetch an invoice  
  ● receive invoice status (via webhook call from nash link backend on each invoice status change)  
  ● trigger a webhook  

## Invoice

The invoice is the main data structure for payments. When a user wants to pay with Nash Link, the merchant
creates an invoice for the specified amount and can optionally include further fields, such as internal order ID, redirect URL and many others.


```json
{
  "facade": "merchant/invoice",
  "data": {
    "id": "JDBtJCFV",
    "status": "complete",
    "price": 9,
    "currency": "EUR",
    "itemDesc": "Item XYZ",
    "orderId": "10118",
    "posData": "tx46523",
    "invoiceTime": 1588318118648,
    "expirationTime": 1588319018648,
    "currentTime": 1588325917427,
    "paidOn": 1588319018644,
    "notificationEmail": "youremail@domain.com",
    "notificationURL": "https://yourredirecturl.com",
    "redirectURL": "https://yourwebsite.com/checkout/10118",
    "url": "https://link.nash.io/widget?invoiceId=JDBtJCFV",
    "transactionCurrency": "BTC",
    "amountPaid": 92313,
    "displayAmountPaid": "0.00092313",
    "exchangeRates": {
      "BTC": {
        "EUR": 9749.44
      }
    },
    "supportedTransactionCurrencies": {
      "BTC": {
        "enabled": true
      }
    },
    "paymentWalletAddress": "bc1qx2qyua0kjzyhza5y4x9lj7mghu39sm4d0sl226",
    "paymentWalletBlockchain": "BTC",
    "paymentCodes": {
      "BTC": {
        "BIP21": "bitcoin:bc1qx2qyua0kjzyhza5y4x9lj7mghu39sm4d0sl226?value=0.00092313"
      }
    },
  }
}
```

**Data fields**

Name | Type | Description
-------------- | -------------- | --------------
`id` | string | ID of this specific invoice
`status` | string | `new` / `paid` / `complete` / `expired`
`price` | number | Fiat amount of the invoice
`currency` | string | Fiat currency of the invoice
`itemDesc` | string | Merchant-provided reference text about the items in this invoice
`orderId` | string | Merchant-provided reference ID
`posData` | string | Passthru variable for Merchant internal use
`invoiceTime` | number | Timestamp of when the invoice was created
`expirationTime` | number | Timestamp of when the invoice will expire
`currentTime` | number | Timestamp of the API call retrieving this invoice
`paidOn` | number | Timestamp of when the invoice was paid
`notificationEmail` | string | Email address of the merchant to receive notifications about invoice status changes
`notificationURL` | string | URL of the merchant backend to receive webhooks relating to invoice status changes
`redirectURL` | string | Merchant-provided URL to redirect the user after a successful payment
`url` | string | Web address of the invoice
`transactionCurrency` | string | Symbol of cryptocurrency the user paid with
`amountPaid` | number | Amount the user paid, in smallest unit of the cryptocurrency (initially `0`)
`displayAmountPaid` | string | Amount the user paid, in full unit of the cryptocurrency (initially `'0'`)
`exchangeRates` | object | Exchange rates for this invoice
`supportedTransactionCurrencies` | object | Supported cryptocurrencies to pay this invoice
`paymentWalletAddress` | string | Wallet address for payment
`paymentWalletBlockchain` | string | Wallet blockchain
`paymentCodes` | object | URI for sending a transaction to the invoice

<aside class="notice">
Note: Cryptocurrency amounts are always in the smallest unit of any specific currency (for instance, in case of Bitcoin, amounts are always denominated in satoshis).
</aside>

## Payment sequence 

Click to enlarge:

{{< figure src="/images/payment-sequence.png" >}}

## Invoice states

A Nash Link invoice can be in one of the following states. After each state transition a webhook is sent to the merchant callback URL, as described <a href="#invoice-webhooks">here</a>.

`new`

An invoice starts in the `new` state. Payments made to this invoice have a 20 minute window to be confirmed on the blockchain.

`paid`

After a payment was detected on the blockchain, an invoice is marked as `paid`. This is a transition state and the invoice will become either `expired` or `complete`.

`complete`

If the full payment made to an invoice has been confirmed on the blockchain during the confirmation window, the invoice is marked as `complete`. The merchant will be credited the invoice amount on the next payout.

`expired`

An invoice is marked as `expired` if the payment wasn't made in full or confirmed on the blockchain in time. All payments received will be automatically refunded.

<b>See also:</b>

* <a href="#invoice-webhooks">Invoice webhooks</a>

## Errors

The Nash Link API uses the following error codes:


Error Code | Meaning
---------- | -------
400 | Bad Request -- Your request sucks
401 | Unauthorized -- Your API key is incorrect
404 | Not Found -- The specified resource could not be found
500 | Internal Server Error -- We had a problem with our server – try again later
503 | Service Unavailable -- We’re temporarially offline for maintanance – try again later

## Activating API Access

The merchant must obtain an API key from the Nash link web app to get access to the API calls.  

The API generation/revoke actions are accessible at https://link.nash.io/developers/.  
  
A merchant can create multiple keys for use with different e-commerce stores or API functions.  

Once an API key has been created, Nash link will use this API key to authenticate your API connections.  

The merchant’s API key must remain private and should never be visible on any client-facing code.

Should it ever be compromised, the merchant can generate a new key in their Nash Link account.  

## Our SDKs

We provide well-maintained SDKs for several programming languages. These are a slim layer to simplify API access (in particular, request signing):

* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-node-typescript" target="_blank">Node.js / TypeScript</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-php" target="_blank">PHP</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-python" target="_blank">Python</a>

<a href="#sdks">Read more</a>

## E-commerce

We also provide well-maintained plugins for some well-know ecommerce frameworks. The easiest way to start using Nash link if you have any of those ecommerce platforms.

* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/plugin-magento2-checkout" target="_blank">Magento 2</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/plugin-prestashop-checkout" target="_blank">Prestashop</a>
* <a href="https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/plugin-woocommerce-checkout" target="_blank">Woocommerce</a>

<a href="#e-commerce-plugins">Read more</a>

## Rest API Access

Nash link provides a standards-based REST interface which enables application developers to interact in a powerful, yet secure way with their Nash link account.  

<a href="#rest-api">Read more</a>
