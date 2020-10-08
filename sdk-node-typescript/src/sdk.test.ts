import { signRequest, NashLinkApi } from "./sdk"

const testApiKeys = {
  publicKey: "8TzeTt2jkwyZJBJk8k2CphfqVL91Ff9n8M1aJCipzUxb",
  secretKey: "HCXHYAaQQBFzAn2Fy4uX3FLG9QH96KUCJtD1LdStLQyC"
}

test('creating request signatures', async () => {
  const url = "https://link.dev4.nash.io/api/v1/sandbox/invoices"

  const payload = {
    "currency": "eur",
    "itemDesc": "H item",
    "notificationEmail": "allan@nash.io",
    "notificationURL": "test.com/notification",
    "posData": "posdata-2",
    "orderId": "acde123",
    "price": 0.5,
    "redirectURL": "test.com"
  }

  const sig = signRequest({ url, body: payload, secretKey: testApiKeys.secretKey })
  expect(sig.signature).toEqual('f69114665f0f8787ecc1d6c42105ecc68cac4e944a576d17f27454ef3e07df94')
})

test('NashLinkApi is building correct API urls', async () => {
  const api1 = new NashLinkApi('sandbox', 'a', 'b')
  expect(api1.apiUrl).toEqual('https://link.nash.io/api/v1/sandbox')

  const api2 = new NashLinkApi('sandbox', 'a', 'b', 'production')
  expect(api2.apiUrl).toEqual('https://link.nash.io/api/v1/sandbox')

  const api3 = new NashLinkApi('sandbox', 'a', 'b', 'dev4')
  expect(api3.apiUrl).toEqual('https://link.dev4.nash.io/api/v1/sandbox')

  const api4 = new NashLinkApi('production', 'a', 'b')
  expect(api4.apiUrl).toEqual('https://link.nash.io/api/v1/prod')

  const api5 = new NashLinkApi('production', 'a', 'b', 'production')
  expect(api5.apiUrl).toEqual('https://link.nash.io/api/v1/prod')

  const api6 = new NashLinkApi('production', 'a', 'b', 'dev4')
  expect(api6.apiUrl).toEqual('https://link.dev4.nash.io/api/v1/prod')
})
