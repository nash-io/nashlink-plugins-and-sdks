---
weight: 30
title: API Reference
---

# E-Commerce Plugins

This section describes the installation of our payment plugin in various e-commerce solutions.

## Magento 2

Download [Nashlink plugins and sdks](https://github.com/nash-io/nashlink-plugins-and-sdks/archive/main.zip) zip pack.

* Extract the downloaded zip pack
* The plugin is at unziped folder plugin-magento2-checkout/Nashlink/

You need access to your magento instance for ssh and/or ftp.

As first step you need to copy Nashlink/ plugin directory to magento code path. Most common ways are via FTP or SSH.

After that, connect via ssh or machine console to run magento install commands.

### Copy Magento 2 plugin over FTP

Access your magento instance via ftp and copy Nashlink/ plugin directory to <MAGENTO_BASE_PATH>/app/code/

### Copy Magento 2 plugin over SSH

`$ scp -r Nashlink <your_user>@<your_magento_ip>:<MAGENTO_BASE_PATH>/app/code/`

### Install Magento 2 plugin

Get terminal access to your magento instance via ssh:

`$ ssh <your_user>@<your_magento_ip>`

After login, run the following commands inside magento instance:


`$ bin/magento setup:upgrade`

`$ bin/magento module:enable Nashlink_NPCheckout`

`$ bin/magento setup:static-content:deploy -f`

`$ bin/magento cache:flush`


### Active Magento 2 plugin

You can now activate Nashlink checkout plugin inside admin interface via `Stores` -> `Configuration` -> `Sales`-> `Payment Methods`

Follow the instructions on plugin configuration interface to get your store ready for Nashlink.

## WooCommerce
  
Download [Nashlink plugins and sdks](https://github.com/nash-io/nashlink-plugins-and-sdks/archive/main.zip) zip pack.

* Extract the downloaded zip pack
* The plugin is at unziped folder plugin-woocommerce-checkout/nashlink/

You need access to your woocommerce wordpress instance for ssh and/or ftp.
  
### Manual Install
  
It requires you first installs and configure WooCommerce plugin on your wordpress instance.  
  
After get WooCommerce up and running all you need is to access your wordpress computer instance via ftp or ssh to copy the plugin directory.  
  
Manual installs only requires you to copy the nashlink/ checkout plugin directory to <wordpress_data_path>/wp-content/plugins/
  
After that, you can enable the plugin on admin interface to start with nash link.  
  
