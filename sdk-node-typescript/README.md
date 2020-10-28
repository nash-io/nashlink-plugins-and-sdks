# Nash Link SDK for Node.js / TypeScript

SDK for merchants to integrate Nash Link on their backend.

* [Node.js module](https://www.npmjs.com/package/@neon-exchange/nash-link)
* [Nash Link API Documentation](https://docs-link.nash.io/)
* [Github repository](https://github.com/nash-io/nashlink-plugins-and-sdks)

## Quickstart

Install with

    # npm
    npm install --save @neon-exchange/nash-link

    # yarn
    yarn add @neon-exchange/nash-link

Start building!

```javascript
 import { NashLinkApi } from '@neon-exchange/nash-link'
 const api = new NashLinkApi('sandbox', `<YOUR_API_KEY>`, `<YOUR_API_SECRET_KEY>`)

// Create a new invoice:
const invoiceCreateResponse = await api.createInvoice({
  price: 10
  currency: 'eur'
})

// Get an invoice by ID:
const invoiceGetResponse = await api.getInvoice(invoiceId)
```

Take a look at the [examples](https://github.com/nash-io/nashlink-plugins-and-sdks/tree/main/sdk-node-typescript/examples), for instance [`invoice-create-and-get.ts`](https://github.com/nash-io/nashlink-plugins-and-sdks/blob/main/sdk-node-typescript/examples/invoice-create-and-get.ts).

You can run this example like this:

```shell
$ npm install -g ts-node
$ ts-node examples/invoice-create-and-get.ts
```

## Developing

These are the two main files:

* [`sdk.ts`](https://github.com/nash-io/nashlink-plugins-and-sdks/blob/main/sdk-node-typescript/src/sdk.ts)
* [`sdk.test.ts`](https://github.com/nash-io/nashlink-plugins-and-sdks/blob/main/sdk-node-typescript/src/sdk.test.ts)

Often used yarn commands:

    yarn install
    yarn build
    yarn lint
    yarn test
