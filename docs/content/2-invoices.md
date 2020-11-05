---
weight: 11
title: API Reference
---

# Invoice API

The invoice is the main data structure for payments. When a user wants to pay with Nash Link, the merchant
creates an invoice for the specified amount and can optionally include further fields, such as internal order ID, redirect URL and many others.

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

## Create an invoice

`POST /invoices`

```
POST /invoices

{
    "price": 40,
    "currency": "EUR"
}

Response:

<InvoiceObject>
```

> See also: <a href="#invoice">`Invoice` object documentation</a>

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
`notificationURL` | string | URL of the merchant backend to receive webhooks relating to invoice status changes
`redirectURL` | string | URL to redirect the user to after a successful purchase

### Response

An `Invoice` object as described in the <a href="#invoice">`Invoice` object documentation</a>.


## Get an invoice

`GET /invoices/<invoiceId>`

```
GET /invoices/JDBtJCFV

Response:

<InvoiceObject>
```

> see also: <a href="#invoice">`Invoice` object documentation</a>

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="#signing-requests">here</a>

### Response

Invoice object as described in the <a href="#invoice">`Invoice` object documentation</a>

## Trigger webhook

`GET /invoices/<invoiceId>/trigger_webhook`

```
GET /invoices/JDBtJCFV/trigger_webhook

Response:

{ "sent": true }
```

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

### Request headers

* `content-type`: `application/json`

### Request body (JSON)

Invoice object as described in the <a href="#invoice">`Invoice` object documentation</a>

<b>See also:</b>

* <a href="#invoice-states">Invoice states</a>
