# Cart api.
Please organize, design, test, document, and deploy your code as if it were
going into production.

## Description

***an api that can price a cart of products, accept multiple products, combine offers, and display a total detailed bill in different currencies (based on user selection).***

Available catalog products and their price in USD:

* T-shirt $10.99
* Pants $14.99
* Jacket $19.99
* Shoes $24.99

The api can handle some special offers, which affect the pricing.

Available offers:

* Shoes are on 10% off.
* Buy two t-shirts and get a jacket half its price.

The api accepts a list of products, outputs the detailed bill of the subtotal, tax, and discounts if applicable, bill can be displayed in various currencies.

*There is a 14% tax (before discounts) applied to all products.*
*Api only accepts products where first character is capital.

E.g.:

Adding the following products:

```
T-shirt
T-shirt
Shoes
Jacket
```

Outputs the following bill, the user selected the USD bill:

```
Subtotal: $66.96
Taxes: $9.37
Discounts:
	10% off shoes: -$2.499
	50% off jacket: -$9.995
Total: $63.8404
```

Another, e.g., If none of the offers are eligible, the user selected the EGP bill:

```
T-shirt
Pants
```

Outputs the following bill:

```
Subtotal: 409 e£
Taxes: 57 e£
Total: 467 e£
```
  
## Requirements
1. PHP >=7.3
1. Any http client to test GET & POST requests.
1. Composer to install phpunit if want to do code testing(optional for devlopment).
  

## Apache server configuration
```
<VirtualHost *:80>
  ServerName localhost
  ServerAlias localhost
  DocumentRoot "path/to/public/folder"
  <Directory "path/to/public/folder">
    Options +Indexes +Includes +FollowSymLinks +MultiViews
    DirectoryIndex index.html index.cgi index.php
    AllowOverride All
    Require local
  </Directory>
</VirtualHost>
```

## Endpoints
GET '/cart'
GET '/cart?currency=EGP'
GET '/cart?product=T-shirt'
GET '/cart?product=T-shirt&currency=EGP'
POST '/cart'

```
GET '/cart'
- Fetches an object of catalog products with their prices and in which currency.
- Request Arguments: None
- Returns: An object with keys that represent products with prices and Currency represents that prices are in dollar.
{
	"Products":{"T-shirt":10.99, "Pants":14.99, "Jacket":19.99, "Shoes":24.99}, 
	"Currency":"USD"
}

GET '/cart?currency=EGP'
- Fetches an object of catalog products with their prices in a specific currency.
- Request Arguments: currency
- Returns: An object with keys that represent products with prices and Currency represents that prices are in Egyptian Pound.
{
	"Products":{"T-shirt":172.65, "Pants":235.49, "Jacket":314.04, "Shoes":392.59}, 
	"Currency":"EGP"
}

GET '/cart?product=T-shirt'
- Fetches an object of a specific catalog product with its price in a dollar currency.
- Request Arguments: product
- Returns: An object with keys that represent a product with its price and the default dollar currency.
{
	"T-shirt":10.99,
	"Currency":"USD".
}

GET '/cart?product=T-shirt&currency=EGP'
- Fetches an object of a specific catalog product with its price in an egyptian pound currency.
- Request Arguments: product and currency
- Returns: An object with keys that represent a product with its price and the requested currency.
{
	"T-shirt":10.99,
	"Currency":"USD".
}

POST '/cart'
- creates a cart that has keys for the subtotal, taxes, discounts, and total values.
- Request Arguments: the following body structure as json object: 
{ 
	"products": "T-shirt T-shirt Shoes Jacket",
	"currency": "USD"
}.
- Returns: An object with a single key: success for indicating that request is handled correctly if true and false if otherwise.
{
    "Subtotal": 1051.942,
    "Taxes": 147.27,
    "Discounts": [
        "10% off shoes: --39.25929EGP",
        "50% off jacket: --157.02145EGP"
    ],
    "Total": 1002.933,
    "Currency": "EGP"
}


Errors:

in case of a request sent to any of the api endpoints and it's not exist, it will respond with a 404 status code and body with the following:
null

in case of a request sent to any of the api endpoints and it's not processable on the server, it will respond with a 422 status code and body with the following json object:
{
	'error' => 'Invalid input'
}
```



## Testing
To run the tests, from inside the project directory run
```
path/to/phpunit tests

```
