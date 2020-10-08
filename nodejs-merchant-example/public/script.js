const log = (msg) => {
  console.log(msg)
  // $('#log').append(`<p><small>[${new Date().toLocaleString()}]</small> ${msg}</p>`)
}

const startPayFlow = async () => {
  // return showPaymentWidget('1b9539bd-ce0d-49b0-98b0-cdac938ac9e2')

  const amount = $('#amountInput').val()
  log(`startPayFlow: amount=${amount}`)

  log('Creating invoice via merchant backend...')
  const response = await fetch('./create-invoice', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', },
    body: JSON.stringify({ amount })
  })

  // Error handling
  if (response.status !== 200) {
    const msg = await response.text()
    const errorMessage = `Error from merchant backend: ${response.status}, ${msg}`
    log(errorMessage)
    alert(errorMessage)
    return
  }

  // Retrieve the response
  const responseObj = await response.json()
  const { invoiceId, widgetUrl } = responseObj
  console.log(`Received invoice:`, responseObj)

  // Create the widget iframe
  const ifrm = document.createElement("iframe");
  ifrm.setAttribute("src", widgetUrl);
  ifrm.classList.add('payment-widget')

  $("#pay-step-1").hide()
  $("#pay-step-2").html(ifrm)
}
