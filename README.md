# Jeeb.E-Commerce
# Using the Jeeb plugin for WordPress (WP) eCommerce

## Prerequisites

* Last Cart Version Tested: Wordpress 4.0 WP e-commerce 3.13.1

You must have a Jeeb merchant account to use this plugin.  It's free to [sign-up for a Jeeb merchant account](https://jeeb.io).


## Installation of Wordpress eCommerce Plugin

- Download WP eCommerce plugin from the WordPress Plugin Directory: https://wordpress.org/plugins/wp-e-commerce/. 
- Extract the contents of the zip file to the [wordpress main directory]/wp-content/plugins/ directory.
- Log in to your Wordpress and navigate to the Admin dashboard -> Plugins -> Installed Plugins
- Activate WP eCommerce plugin

## Installation of the Jeeb plugin for WordPress (WP) eCommerce

- Clone the repo:

```bash
$ git clone https://github.com/gdhar67/Jeeb.E-Commerce.git
$ cd Jeeb.E-Commerce
```
Copy the files inside the folder to your_site/wp-content/plugins/wp-e-commerce/wpsc-merchants.

## Configuration

* Log into the WordPress admin panel, click Settings > Store > Payments (assuming you've already installed WP eCommerce plugin).

* Check the Jeeb payment option to activate it and click Save Changes below.

* Click Settings below the Jeeb payment option.

* Edit Display Name if desired.

* Select between Test/Live Environment.

* Enter the signature provided by Jeeb.

* Select the currency of your store as Basecoin.

* Select the crypto-currencies that should be availabe for users in the payment page as Targetcoin(You can select multiple options).

* Select the language of the payment page.

* Input a URL to redirect customers after they have paid the invoice (Transaction Results page, Your Account page, etc.)

* Click Update below.

## Usage

- Once the configuration is done, whenever a buyer selects Bitcoins as their payment method an invoice is generated at jeeb.io.
- Then they will be redirected to a payment page where there can pay the invoice.
