// tslint:disable:no-console
import * as path from 'path'
import * as express from 'express'
import { NashLinkApi } from "@neon-exchange/nash-link"

// API key must be created specifically for the sandbox environment
const API_KEY = process.env.API_KEY || "G4ejBBAAneVxXTRMdK5gXVPsyXsr3qp1gxgGE19S1w4U"
const API_SECRET_KEY = process.env.API_SECRET_KEY || "BhwNFkc4mj91Akz8j5EKc9ngvHKQdPuVnUcxoajU513K"

// Create the Nash Link API instance
const nashLinkApi = new NashLinkApi("sandbox", API_KEY, API_SECRET_KEY)

// Setup Express webserver
const app = express()
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// serve public files under /static/
const publicPath = path.join(__dirname, '..', 'public')
app.use('/static', express.static(publicPath))

// index.html
const indexHtmlPath = path.join(publicPath, 'index.html')
app.get('/', async (_req, res) => res.sendFile(indexHtmlPath))

// API: create-invoice
app.post('/create-invoice', async (req, res) => {
  console.log('/create-invoice', req.body)

  // Input validation: amount
  if (!req.body?.amount) return res.status(400).send({ error: 'no amount' })
  const amount = Number(req.body.amount)
  if (typeof amount !== 'number') return res.status(400).send({ error: 'amount is not a number' })
  if (amount < 0.5) return res.status(400).send({ error: 'amount must be 0.5 or more' })

  // Create the invoice
  try {
    const invoiceData = await nashLinkApi.createInvoice({
      "currency": "eur",
      "price": amount
    })

    console.log("create-invoice response", invoiceData)

    return res.send({
      invoiceId: invoiceData.id,
      widgetUrl: invoiceData.url + `&env=sandbox`,
      data: invoiceData
    })
  } catch (err) {
    console.error(err)
    return res.status(500).send({ error: err })
  }
})

// API to receive a payment webhook
app.post('/payment-webhook', async (req, res) => {
  console.log('/payment-webhook', req.body)
  res.send({})
})

const run = async () => {
  const WEBSERVER_PORT: number = parseInt(process.env.WEBSERVER_PORT || '3000', 10)
  app.listen(WEBSERVER_PORT, () => console.log(`Example app listening at http://localhost:${WEBSERVER_PORT}`))
}

run()
