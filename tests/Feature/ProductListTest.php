<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    const URI = 'www.itccompliance.co.uk/recruitment-webservice/api';

    public function test_product_list_returned_ok()
    {
        $this->setupFakeApi('/list', $this->validList());
        $response = $this->get(route('products'));
        $response->assertSuccessful();
        $response->assertViewIs('products');
        $response->assertViewHasAll([
            'products' => [
                (object)['id'=>"fakenormal", 'name' => "Fake Normal"],
                (object)['id'=>"faketags", 'name' => "Fake tags"],
                (object)['id'=>"fakeunicode", 'name' => "Fake unicode"],
            ],
            'error' => false,
            'can_retry' => false,
        ]);

        $response->assertStatus(200);
    }

    public function test_product_list_try_again_response()
    {
        $this->setupFakeApi('/list', $this->errorResponse());
        $response = $this->get(route('products'));
        $response->assertSuccessful();
        $response->assertViewIs('products');
        $response->assertViewHasAll([
            'products' => null,
            'error' => true,
            'can_retry' => true,
        ]);

        $response->assertStatus(200);

    }

    public function test_product_list_other_error_response()
    {
        $this->setupFakeApi('/list', $this->otherErrorResponse());
        $response = $this->get(route('products'));
        $response->assertSuccessful();
        $response->assertViewIs('products');
        $response->assertViewHasAll([
            'products' => null,
            'error' => true,
            'can_retry' => false,
        ]);

        $response->assertStatus(200);

    }

    protected function validList()
    {
        return json_decode('{"products":{"fakenormal":"Fake Normal","faketags":"Fake tags<tag>12345<\/tag>","fakeunicode":"\\u0000Fake unicode\\u001e"}}', true);
    }

    protected function errorResponse()
    {
        return json_decode('{"error": "fake try again response"}', true);
    }

    protected function otherErrorResponse()
    {
        return json_decode('{"error": "some fake error"}', true);
    }

    protected function setupFakeApi($path, $response, $code=200)
    {
        $fake = [
            self::URI.$path => Http::response($response,$code),
        ];
        Http::fake($fake);
        return $this;
    }


}
