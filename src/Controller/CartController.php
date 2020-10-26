<?php

namespace Src\Controller;

require __DIR__ . "/../../config.php";

class CartController {

    private $requestMethod;
    private $test;
    private $currency;
    private $products;

    public function __construct($requestMethod, $test = FALSE)
    {
        $this->requestMethod = $requestMethod;
        $this->test = $test;
    }

    private function getCurrency(){
        return $this->currency;
    }

    private function setCurrency($currency){
        if (isset($currency)) {
         $this->currency = $currency;
        }else{
            $this->currency = "USD";
        }
    }

    private function getProducts(){
        return $this->products;
    }

    private function setProducts(string $products){
        if (isset($products)) {
            $products = explode(" ", $products);
            $this->products = $products;
        } else {
            $this->products = [];
        }
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if (isset($_GET["product"])) {
                    $response = $this->getProductPrice($_GET["product"]);
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
        if(!$this->test) {
            header($response['status_code_header']);
            if ($response['body']) {
                echo $response['body'];
            }
        } else {
            return $response;
        }
    }

    public function getAllProducts()
    {
        $result = PRODUCTS;
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function getProductPrice($product)
    {
        if(isset(PRODUCTS[$product])){
            $result = PRODUCTS[$product];
            $response['status_code_header'] = 'HTTP/1.1 200 OK';
            $response['body'] = json_encode($result);
        }else{
            $response = $this->notFoundResponse();
        }
        return $response;
    }

    private function getBody(){
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        return $input;
    }

    public function getCart($input =[])
    {   if(!$this->test){
            $input = $this->getBody();
        }
        if (!$this->validateInput($input)) {
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
                    $discounts_value += SHOES_DISCOUNT;
                    $discounts[] = "10% off shoes: -$2.499";
                    break;
                case 'T-shirt'://apply 2 t-shirts & jacket offer
                    $sold_tshirts++;
                    break;
                case 'Jacket':
                    $sold_jackets++;
                    if(floor($sold_tshirts/2) >= $sold_jackets){
                        $discounts_value += TWO_TSHIRTS_OFFERS["Jacket"];
                        $discounts[] = "50% off jacket: -$9.995";
                    }
                    break;
            }
        }

        $discounts = array_unique($discounts);
        $total = ($subtotal * (1+TAX)) + $discounts_value;
        $cart = [
                    "Subtotal" => round($subtotal, 3),
                    "Taxes"=> round($subtotal * TAX, 2),
                    "Discounts" => $discounts,
                    "Total" => round($total , 3),
                    "Currency" => $currency,
                ]; 
                
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = json_encode($cart);
        return $response;
    }

    private function validateInput($input)
    {
        //check if products exist & valid
        if (!isset($input['products']) || !is_string($input['products'])) {
            return false;
        }else{
            $products = $input['products'];
            $products = explode(" ", $products);
            $defined_products = array_keys(PRODUCTS);
            foreach ($products as $key => $product) {
                if(!in_array($product, $defined_products)){
                    return false;
                }
            }
        }
        
        //check if currency supported and valid
        if (isset($input['currency'])) {
            $currency = $input['currency'];
            $defined_currencies = array_keys(CURRENCIES); 
            if(!in_array($currency, $defined_currencies)){
                return false;
            }
        }

        return true;
    }

    private function unprocessableEntityResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => 'Invalid input'
        ]);
        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}
