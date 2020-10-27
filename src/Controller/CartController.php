<?php
/**
 * @file
 * Cart Controller Calss to handle business logic.
 */

namespace Src\Controller;

require_once __DIR__ . "/../../config.php";
/**
 * Cart Controller Calss to handle business logic.
 */
class CartController
{
    private $_requestMethod;
    private $_test;
    private $_currency;
    private $_products;

    /**
     * Construct a new instance for cart class controller.
     */
    public function __construct($requestMethod, $test = false)
    {
        $this->_requestMethod = $requestMethod;
        $this->_test = $test;
    }
    /**
     * Gets cart currency.
     * 
     * @return string currency abbreviation.
     */
    private function getCurrency()
    {
        return $this->_currency;
    }
    /**
     * Sets cart currency.
     * 
     * @param string $currency
     *   A currency abbreviation.
     */
    public function setCurrency(string $currency)
    {
        if (isset($currency) && $this->isValidCurrency($currency)) {
            $this->_currency = $currency;
        } else {
            $this->_currency = "USD";
        }
    }

    /**
     * Sets cart products.
     * 
     * @return array
     *   Returns array of products.
     */
    private function getProducts()
    {
        return $this->_products;
    }

    /**
     * Sets products from user into cart.
     * 
     * @param string $products
     *   A string of products separated with spaces.
     */
    private function setProducts(string $products)
    {
        if (isset($products)) {
            $products = explode(" ", $products);
            $this->_products = $products;
        } else {
            $this->_products = [];
        }
    }

    /**
     * Handles http requests.
     * 
     * @return array $response
     *   Contains http response.
     */
    public function processRequest()
    {
        
        switch ($this->_requestMethod) {
        case 'GET':
            $this->setCurrency(isset($_GET["currency"]) ? $_GET["currency"]: "USD" );

            if (isset($_GET["product"])) {
                $response = $this->getSingleProductPrice($_GET["product"]);
            } else {
                $response = $this->getAllProducts();
            }
            break;
        case 'POST':
            $response = $this->getCart();
            break;
        default:
            $response = $this->notFoundResponse();
            break;
        }
        if (!$this->_test) {
            header($response['status_code_header']);
            if ($response['body']) {
                echo $response['body'];
            }
        } else {
            return $response;
        }
    }
    /**
     * Gets all products info.
     * 
     * @return array $response
     *   that contains the http response.
     */
    public function getAllProducts()
    {
        $currency = $this->getCurrency();
        $result = ["Products" => array_map([$this, "map_price_currency"], PRODUCTS), "Currency"=> $currency];
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function map_price_currency(string $price)
    {
        $currency = $this->getCurrency();
        return round($price * CURRENCIES[$currency], 2);
    }
    /**
     * Gets single product price value.
     *
     * @param string $product
     *   Product string value.
     * 
     * @return array $response
     *   A response array that contains the http response.
     */
    private function getSingleProductPrice(string $product)
    {
        $currency = $this->getCurrency();
        if (isset(PRODUCTS[$product])) {
            $result = [$product => round(PRODUCTS[$product]* CURRENCIES[$currency], 2), "Currency"=>$currency];
            $response['status_code_header'] = 'HTTP/1.1 200 OK';
            $response['body'] = json_encode($result);
        } else {
            $response = $this->notFoundResponse();
        }
        return $response;
    }

    /**
     * Gets post body value.
     * 
     * @return array $input
     *   An array that represnts the request body.
     */
    private function getBody()
    {
        $input = (array) json_decode(file_get_contents('php://input'), true);
        return $input;
    }

    /**
     * Calculate cart values.
     * 
     * @param array $input
     *  An optional parameter used for test purpose instead of post request.
     * 
     * @return array $response
     *   A response array that contians the cart details.
     */
    public function getCart(array $input =[])
    {
        if (!$this->_test) {
            $input = $this->getBody();
        }
        if (!$this->isValidCurrency($input["currency"]) || !$this->isValidProducts($input["products"])) {
            return $this->unprocessableEntityResponse();
        }
        //set currency
        $this->setCurrency($input['currency']);
        $currency = $this->getCurrency();
        //set products array
        $this->setProducts($input["products"]);
        $products = $this->getProducts();
        //initialize some helper variables
        $subtotal = 0;
        $discounts = [];
        $discounts_value = 0;
        $sold_tshirts = 0;
        $sold_jackets = 0;
        foreach ($products as $key => $product) {
            $product = trim($product);
            //apply currency change
            $unit_price = PRODUCTS[$product] * CURRENCIES[$currency];
            //calc subtotal alone
            $subtotal += $unit_price;
            //apply discount 10% off shoes
            switch ($product) {
            case 'Shoes':
                $shoes_discount_value = SHOES_DISCOUNT * CURRENCIES[$currency];
                $discounts_value += $shoes_discount_value;
                $discounts[] = "10% off shoes: -" . $shoes_discount_value . $currency;
                break;
            case 'T-shirt'://apply 2 t-shirts & jacket offer
                $sold_tshirts++;
                break;
            case 'Jacket':
                $sold_jackets++;
                if (floor($sold_tshirts/2) >= $sold_jackets) {
                    $jacket_discount_value = TWO_TSHIRTS_OFFERS["Jacket"] * CURRENCIES[$currency];
                    $discounts_value += $jacket_discount_value;
                    $discounts[] = "50% off jacket: -" . $jacket_discount_value . $currency;
                }
                break;
            }
        }
        //remove duplicates discounts if exists
        $discounts = array_unique($discounts);
        //calculate total
        $total = ($subtotal * (1 + TAX)) + $discounts_value;
        $cart = [
                    "Subtotal" => round($subtotal, 3),
                    "Taxes"=> round($subtotal * TAX, 2),
                    "Discounts" => $discounts,
                    "Total" => round($total, 3),
                    "Currency" => $currency,
                ]; 
                
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = json_encode($cart);
        return $response;
    }

    /**
     * Validates user input.
     * 
     * @param array $input
     * User input from request body.
     * 
     * @return bool 
     * Indicates if the user request/input is valid or not.
     */
    private function isValidCurrency(string $currency)
    {
        //check if currency supported and valid
        if (isset($currency)) {
            $defined_currencies = array_keys(CURRENCIES);
            if (in_array($currency, $defined_currencies)) {
                return true;
            }
        }
        return false;
    }

    private function isValidProducts(string $products)
    {
        //check if products exist & valid
        if (!isset($products) || !is_string($products)) {
            return false;
        } else {
            $products = explode(" ", $products);
            $defined_products = array_keys(PRODUCTS);
            foreach ($products as $key => $product) {
                if (!in_array($product, $defined_products)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Return error for incorrect input.
     * 
     * @return array $response 
     *   Structures 422 error response for bad inputs.
     */
    private function unprocessableEntityResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode(
            [
            'error' => 'Invalid input'
            ]
        );
        return $response;
    }
    /**
     * Return error for not found input.
     * 
     * @return array $response 
     * Structures 404 error response for not found inputs.
     */
    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}
