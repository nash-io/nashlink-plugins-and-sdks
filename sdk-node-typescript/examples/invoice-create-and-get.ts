// tslint:disable:no-console
import { NashLinkApi, CreateInvoiceData } from "../src/sdk"

const API_KEY = "8TzeTt2jkwyZJBJk8k2CphfqVL91Ff9n8M1aJCipzUxb"
const API_SECRET_KEY = "HCXHYAaQQBFzAn2Fy4uX3FLG9QH96KUCJtD1LdStLQyC"

const api = new NashLinkApi("sandbox", API_KEY, API_SECRET_KEY, 'dev4')

const createInvoice = async () => {
  const payload: CreateInvoiceData = {
    "currency": "eur",
    "price": 0.5
  }
  // const payload: CreateInvoiceData = {
  //   "currency": "eur",
  //   "itemDesc": "H item",
  //   "notificationEmail": "chris@nash.io",
  //   "notificationURL": "test.com/notification",
  //   "posData": "posdata-2",
  //   "orderId": "acde123",
  //   "price": 0.5,
  //   "redirectURL": "test.com"
  // }

  const res = await api.createInvoice(payload)
  console.log('res', res)
}

const getInvoice = async () => {
  const invoiceId = 'BuDDDrYy'
  const res = await api.getInvoice(invoiceId)
  console.log('res', res)
}

// createInvoice()
getInvoice()
