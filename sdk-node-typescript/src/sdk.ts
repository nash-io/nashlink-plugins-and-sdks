// tslint:disable:no-console
/**
 * API Documentation: https://nash-io.github.io/nashlink-plugins-and-sdks
 *
 * Usage example:
 *
 *     import { NashLinkApi } from '@neon-exchange/nash-link'
 *     const api = new NashLinkApi('sandbox', `<YOUR_API_KEY>`, `<YOUR_API_SECRET_KEY>`)
 *     const invoiceResponse = await api.createInvoice({
 *         price: 10
 *         currency: 'EUR'
 *     })
 */
import * as crypto from 'crypto'
import fetch from 'node-fetch'

const DEBUG_FETCH_REQUESTS = true
const DEBUG_FETCH_RESPONSE = false

const HOSTS = {
  production: 'https://link.nash.io',
  dev4: 'https://link.dev4.nash.io'
}

const ENVIRONMENT_PATHS = {
  production: 'prod',
  sandbox: 'sandbox'
}

export interface CreateInvoiceData {
  price: number
  currency: 'eur'  // ISO 4217 3-character currency code (eg. 'eur')

  itemDesc?: string
  notificationEmail?: string
  notificationURL?: string
  posData?: string
  orderId?: string
  redirectURL?: string
}

export interface InvoiceData {
  id: string
  price: number,
  status: string,
  currency: string,
  cryptoAmount: string,
  amountPaid: number,
  currentTime: number,
  displayAmountPaid: string,
  exchangeRates: object,
  expirationTime: number,
  invoiceTime: number,
  itemDesc: string,
  notificationEmail: string,
  notificationURL: string,
  orderId: string,
  paymentCodes: object,
  paymentWalletAddress: string,
  paymentWalletBlockchain: string,
  redirectURL: string,
  supportedTransactionCurrencies: object,
  transactionCurrency: string,
  updatedAt: number,
  url: string
}

export interface InvoiceResult {
  data: InvoiceData
  facade: string
}

export class NashLinkApi {
  apiUrl: string
  private apiKey: string
  private apiSecretKey: string

  constructor(environment: 'sandbox' | 'production', apiKey: string, apiSecretKey: string, host: 'production' | 'dev4' = 'production') {
    this.apiUrl = `${HOSTS[host]}/api/v1/${ENVIRONMENT_PATHS[environment]}`
    this.apiKey = apiKey
    this.apiSecretKey = apiSecretKey
  }

  private async _fetchPromiseExec(fetchPromise: Promise<fetch.Response>): Promise<any | null> {
    const res = await fetchPromise
    const text = await res.text()
    if (DEBUG_FETCH_RESPONSE) console.log(`DEBUG_FETCH_RESPONSE: ${res.status}`, text)

    if (res.status >= 300) {
      const dataSubstr = text ? `, data: '${text}'` : ''
      throw new Error(`API response error (status ${res.status}${dataSubstr})`)
    }

    if (!text) return null
    return JSON.parse(text)
  }

  private async fetchGet(url: string): Promise<any> {
    if (DEBUG_FETCH_REQUESTS) console.log(`DEBUG fetchGet: ${url}`)

    const signingResponse = signRequest({ url, secretKey: this.apiSecretKey })
    const headers = {
      'content-type': 'application/json',
      'x-identity': this.apiKey,
      'x-signature': signingResponse.signature
    }

    const fetchPromise = fetch(url, { headers })
    return this._fetchPromiseExec(fetchPromise)
  }

  async createInvoice(data: CreateInvoiceData): Promise<InvoiceData> {
    const url = `${this.apiUrl}/invoices`
    const signingResponse = signRequest({ url, body: data, secretKey: this.apiSecretKey })

    const headers = {
      'content-type': 'application/json',
      'x-identity': this.apiKey,
      'x-signature': signingResponse.signature
    }

    const fetchPromise = fetch(url, {
      method: 'post',
      body: JSON.stringify(data),
      headers
    })
    const res = await this._fetchPromiseExec(fetchPromise)
    return res.data
  }

  async getInvoice(invoiceId: string): Promise<InvoiceData> {
    const url = `${this.apiUrl}/invoices/${invoiceId}`
    const res = await this.fetchGet(url)
    return res.data
  }
}

interface SignRequestArgs {
  url: string
  body?: object
  queryArguments?: object
  secretKey: string
}

export const signRequest = (args: SignRequestArgs): { signature: string, signingString: string } => {
  let signingString = args.url
  if (args.body) signingString += JSON.stringify(args.body)
  if (args.queryArguments && Object.keys(args.queryArguments).length > 0) {
    const argString = Object.keys(args.queryArguments).map(key => `${key}=${encodeURIComponent(args.queryArguments![key])}`).join('&')
    signingString += '?' + argString
  }

  const signature = crypto.createHmac("sha256", args.secretKey).update(signingString).digest("hex")
  return { signature, signingString }
}
