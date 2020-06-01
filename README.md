# Online CSR generator

## Install

### Requirements
1. PHP version 7, http://php.net
2. OpenSSL, https://www.openssl.org/

### Steps
1. Copy script somewhere inside web root. 
2. Ensure PHP files can be launched from this place.
3. Ensure PHP can run ``openssl`` tool if launched from web-server.

## Use
1. Enter necessary data at block "Input data"
2. Generate CSR by click "Generate"
3. Copy or download private key and CSR request.
4. **IMPORTANT:** save private key somewhere in safe place. If you miss it, certificate (if paid) will be useless.

## Credits:
1. jQuery, http://jquery.com/
2. Clipboard, https://zenorocha.github.io/clipboard.js
3. OpenSSL, https://www.openssl.org/
