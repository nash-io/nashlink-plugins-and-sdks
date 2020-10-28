# NashLinkApi Install

Just copy the module directory into your project and import nashlink module. 
  
## Usage

Create an invoice:  
  
```python
from nashlink import nashlink

# instantiate api
# use 'sandbox' for integration tests
# use 'prod' for final production environment
nashlink_api = nashlink.Api('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>')
# setup order data for the invoice
invoice = {'price': 1, 'currency': 'EUR'}
# create the invoice
response = nashlink_api.create_invoice(invoice)

# check for errors
if response['error']:
    print(response['message'])
    print(response['status_code'])
    exit()

# created invoice data
print(str(response['data']))
```
  
Get and invoice:  

```python
from nashlink import nashlink

# instantiate api
# use 'sandbox' for integration tests
# use 'prod' for final production environment
nashlink_api = nashlink.Api('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>')
# get a invoice
response = nashlink_api.get_invoice('<INVOICE_ID>')

# check for errors
if response['error']:
    print(response['message'])
    print(response['status_code'])
    exit()

# invoice data
print(str(response['data']))
```
