<?php 
require_once __DIR__ . "/../Src/Controller/CartController.php";

use PHPUnit\Framework\TestCase;
use Src\Controller\CartController;

final class CartControllerTest extends TestCase
{
    public function testValidInstance(): void
    {
        $instance = new CartController("GET", TRUE);
        $this->assertInstanceOf(
            CartController::class,
            $instance
        );
    }

    public function testProcessRequest(): void
    {
        $instance = new CartController("GET", TRUE);
        $response = $instance->processRequest();
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 200 OK',
            $response["status_code_header"]
        );
        $expected = ["Products" => PRODUCTS, "Currency"=>"USD"];
        $this->assertEquals(
            $expected,
            $body
        );
    }

    public function testGetAllProducts(): void
    {
        $instance = new CartController("GET", TRUE);
        $instance->setCurrency("USD");
        $response = $instance->getAllProducts();
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 200 OK',
            $response["status_code_header"]
        );
        $expected = ["Products" => PRODUCTS, "Currency"=>"USD"];
        $this->assertEquals(
            $expected,
            $body
        );
    }

    public function testGetCartSubtotal(): void
    {   $input = ["products"=> "T-shirt T-shirt", "currency" => "USD"];
        $instance = new CartController("POST", TRUE);
        $response = $instance->getCart($input);
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 201 Created',
            $response["status_code_header"]
        );
        $this->assertEquals(
            PRODUCTS["T-shirt"] * 2,
            $body['Subtotal'],
        );
    }
    

    public function testGetCartTotal(): void
    {   $input = ["products"=> "T-shirt T-shirt Shoes Jacket", "currency" => "USD"];
        $instance = new CartController("POST", TRUE);
        $response = $instance->getCart($input);
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 201 Created',
            $response["status_code_header"]
        );
        $this->assertEquals(
            63.84,
            $body['Total'],
        );
    }

    public function testGetCartSubtotalWithWrongProduct(): void
    {   $input = ["products"=> "x-shirt z-shirt", "currency" => "USD"];
        $instance = new CartController("POST", TRUE);
        $response = $instance->getCart($input);
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 422 Unprocessable Entity',
            $response["status_code_header"]
        );
    }

    public function testOtherHttpMethod(): void
    {   $input = ["products"=> "T-shirt T-shirt", "currency" => "USD"];
        $instance = new CartController("PUT", TRUE);
        $response = $instance->processRequest();
        $body = json_decode($response["body"], TRUE);
        $this->assertEquals(
            'HTTP/1.1 404 Not Found',
            $response["status_code_header"]
        );
    }

}



