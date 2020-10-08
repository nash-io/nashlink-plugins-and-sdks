---
weight: 11
title: API Reference
---

# Invoice API

The invoice is the main data structure for payments. When a user wants to pay with Nash Link, the merchant
creates an invoice for the specified amount and can optionally include further fields, such as internal order ID, redirect URL and many others.

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

> See also: <a href="?javascript#invoice">`Invoice` object documentation</a>

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="?javascript#signing-requests">here</a>

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

An `Invoice` object as described in the <a href="?javascript#invoice">`Invoice` object documentation</a>.


## Get an invoice

`GET /invoices/<invoiceId>`

```
GET /invoices/JDBtJCFV

Response:

<InvoiceObject>
```

> see also: <a href="?javascript#invoice">`Invoice` object documentation</a>

### Request headers

* `content-type`: `application/json`
* `x-identity`: your API key
* `x-signature`: the signature, as described <a href="?javascript#signing-requests">here</a>

### Response

Invoice object as described in the <a href="?javascript#invoice">`Invoice` object documentation</a>
