<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductInfoTest extends TestCase
{
    const URI = 'www.itccompliance.co.uk/recruitment-webservice/api';

    public function test_product_info_returned_ok()
    {
        $this->setupFakeApi('/info?id=fakeid', $this->validInfo());
        $response = $this->get('product/fakeid');
        $response->assertSuccessful();
        $response->assertViewIs('product');
        $response->assertViewHasAll([
            'product' => (object)[
                "name" => "Fake name",
                "description" => "Fake desc ",
                "type" => "Fake type",
                "suppliers" => "fakesupplier1, fakesupplier2"
            ],
            'error' => false,
            'can_retry' => false,
        ]);

        $response->assertStatus(200);
    }

    public function test_product_info_try_again_response()
    {
        $this->setupFakeApi('/info?id=fakeid', $this->errorResponse());
        $response = $this->get('product/fakeid');
        $response->assertSuccessful();
        $response->assertViewIs('product');
        $response->assertViewHasAll([
            'product' => null,
            'error' => true,
            'can_retry' => true,
        ]);

        $response->assertStatus(200);

    }

    public function test_product_info_other_error_response()
    {
        $this->setupFakeApi('/info?id=fakeid', $this->otherErrorResponse());
        $response = $this->get('product/fakeid');
        $response->assertSuccessful();
        $response->assertViewIs('product');
        $response->assertViewHasAll([
            'product' => null,
            'error' => true,
            'can_retry' => false,
        ]);

        $response->assertStatus(200);

    }

    protected function validInfo()
    {
        return json_decode('{"fakeid":{"name":"Fake name","description":"Fake desc <tag>12345<\/tag>","type":"\\u0000Fake type\\u001e", "suppliers": ["fakesupplier1", "fakesupplier2"]}}', true);
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
            // '*' => Http::response('unexpected api call',$code),
        ];
        Http::fake($fake);
        return $this;
    }
}
