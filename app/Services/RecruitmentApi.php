<?php
namespace App\Services;

use Exception, stdClass;
use Illuminate\Support\Facades\{Cache, Http, Log};

class RecruitmentApi
{
    protected $base_uri = 'https://www.itccompliance.co.uk/recruitment-webservice/api/';

    /**
     * Return list of products from external API
     * @return array $products
     * @throws Exception
     */
    public function list(): array
    {
        $response = $this->apiRequest('list');
        return $this->sanitiseProductList($response);
    }

    /**
     * Return details of product from external API
     * @var string $id
     * @return stdClass $product
     * @throws Exception
     */
    public function show($id): stdClass
    {
        $response = $this->apiRequest('info', ['id' => $id]);
        return $this->sanitiseProduct($response, $id);
    }

    protected function apiRequest($path, $params = [])
    {
        $data = [];
        $url = $this->base_uri.$path;
        if ($qs = http_build_query($params)) {
            $url .= '?'.$qs;
        }
        Log::info("GET request to ".$url);
        // We use 2 different retry methods, as api can return error as 2xx response
        for($attempt = 1; $attempt <= 3; $attempt++) {
            $response = Http::acceptJson()
                ->retry(3, 1000)
                ->get($url);
            Log::info('Attempt #'.$attempt);

            if ($response->successful()) {
                if ($data = $response->json()) {
                    if (!isset($data['error'])) {
                        Log::info('Got non-error 2xx response');
                        // Got a non-error 2xx response, no more attempts needed
                        break;
                    }
                }
            }
            // @todo could return recent cached response if data is fairly static
            sleep(2); // wait before next attempt
        }

        $response->throw(); // If final attempt gave non-2xx response, throw exception
        if (isset($data['error'])) {
            $code = strpos($data['error'], 'try again') ? 503 : 500;
            throw new Exception('Error response: '.$data['error'], $code);
        }
        return $data;
    }

    protected function sanitiseProductList(array $data): array
    {
        $products = [];
        if (!empty($data['products'])) {
            foreach($data['products'] as $id => $name) {
                $products[] = (object)[
                    'id' => self::sanitiseString($id),
                    'name' => self::sanitiseString($name),
                ];
            }
        }
        return $products;
    }

    protected function sanitiseProduct(array $data, $id): \stdClass
    {
        $product = $data[$id] ?? null;
        if (!$product || !isset($product['name'])) {
            throw new Exception('Unexpected response: '.json_encode($data));
        }
        return (object)[
            'name' => self::sanitiseString($product['name']),
            'description' => self::sanitiseString($product['description'] ?? ''),
            'type' => self::sanitiseString($product['type'] ?? ''),
            'suppliers' => self::sanitiseString(implode(', ',($product['suppliers'] ?? []))),
        ];

        return $data;
    }

    protected static function sanitiseString($text): string
    {
        $string = preg_replace('/>\w*</', '><', $text); // remove tag content
        $string = strip_tags($string); // then the tags themselves
        // Now remove unicode characters
        $string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
        return $string;
    }
}
