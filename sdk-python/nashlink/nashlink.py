import requests
import hmac
import hashlib
import json

SERVER_URI = "https://link.nash.io"
SERVER_API = "/api/v1/"

class Api():
    """Nash Link API

    Usage::

        >>> import nashlink
        >>> nashlink_api = nashlink.Api('sandbox', '<YOUR_API_KEY>', '<YOUR_API_SECRET_KEY>')
        >>> invoice = {'price': 1, 'currency': 'EUR'}
        >>> response = nashlink_api.create_invoice(invoice)
    """
    def __init__(self, environment, api_key, secret_key):
        self.server_uri = SERVER_URI
        self.server_api = SERVER_API

        self.api_key = api_key
        self.secret_key = secret_key
        self.environment = environment

    def create_invoice(self, data):
        # process invoice request data
        endpoint = ''.join([self.server_uri,
                            self.server_api,
                            self.environment,
                            '/invoices'])
        signature = self.sign_request(''.join([endpoint,
                                               str(json.dumps(data))]))
        request_headers = self.get_request_header(signature)
        # make nash link request
        r = requests.post(url=endpoint, headers=request_headers, json=data) 
        return self.handle_response_data(r.json(), r.status_code)

    def get_invoice(self, id):
        # process invoice request data
        endpoint = ''.join([self.server_uri,
                            self.server_api,
                            self.environment,
                            '/invoices/', 
                            id])
        signature = self.sign_request(endpoint)
        request_headers = self.get_request_header(signature)
        # make nash link request
        r = requests.get(url=endpoint, headers=request_headers)
        return self.handle_response_data(r.json(), r.status_code)

    def sign_request(self, payload):
        signature = hmac.new(self.secret_key.encode('utf-8'), 
                             msg=payload.encode('utf-8'),
                             digestmod=hashlib.sha256)
        return signature.hexdigest().upper()

    def get_request_header(self, signature):
        return {
            'X-NashLink-Api-Info': '1.0.0',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'x-identity': self.api_key,
            'x-signature': signature
        }

    def handle_response_data(self, response, status_code):
        data = response
        if 'data' not in response:
            data['error'] = True
            data['status_code'] = status_code
            data['message'] = response
        if status_code != 200:
            if 'error' not in response:
                # no error message from server?
                data['error'] = True
                data['message'] = ''.join(['Error: ', response])
            data['status_code'] = status_code
        else:
            # no errors
            data['error'] = False
        return data

    def get_server_uri(self):
        return self.server_uri

    def get_server_api(self):
        return self.server_api