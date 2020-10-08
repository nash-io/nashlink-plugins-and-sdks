---
weight: 12
title: API Reference
---

# Resources

This section describes all the data structures used by the Nash Link API in detail.

## Invoice

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

<aside class="notice">
Note: Cryptocurrency amounts are always in the smallest unit of any specific currency (for instance, in case of Bitcoin, amounts are always denominated in satoshis).
</aside>

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

See also:

* <a href="https://bitpay.com/api/#rest-api-resources-invoices-resource" target="_blank">BitPay invoice documentation</a>
