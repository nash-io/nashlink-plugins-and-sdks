# Nash Link checkout for Magento 2
  
This is a checkout plugin for magento 2. Get your online store ready to offer payments in crypto, while you receive in fiat.    
  
## Install
  
You need access to you magento instance for ssh and/or ftp.  
  
As first step you need to copy Nashlink/ plugin directory to magento code path. Most common ways are via FTP or SSH.   
After that, connects via ssh or machine console to run magento install commands.
  
### Copy plugin files over FTP
  
Access your magento instance via ftp and copy Nashlink/ directory to <MAGENTO_BASE_PATH>/app/code/
  
### Copy plugin files over SSH
  
$ scp -r Nashlink <your_user>@<your_magento_ip>:<MAGENTO_BASE_PATH>/app/code/
  
### Install
  
Get terminal access to your magento instance via ssh:
  
$ ssh <your_user>@<your_magento_ip> 
  
After login, run the following commands inside magento instance:  
  
$ bin/magento setup:upgrade  
$ bin/magento module:enable Nashlink_NPCheckout  
$ bin/magento setup:static-content:deploy -f  
$ bin/magento cache:flush  
  
## Active Plugin
  
You can now activate Nashlink checkout plugin inside admin interface via  Stores->Configuration->Sales->Payment Methods  
  
Follow the instructions on plugin configuration interface to get your store ready for Nash Link.  
  

