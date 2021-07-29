<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\RecruitmentApi as Api;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Api $api)
    {
        $error = $can_retry = false;
        try {
            $products = $api->list();
        } catch (Exception $e) {
            $products = [];
            $error = true;
            $can_retry = $e->getCode() == 503;
        }
        return view('products', [
            'products' => $products,
            'error' => $error,
            'can_retry' => $can_retry,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Api $api)
    {
        $error = $can_retry = false;
        try {
            $product = $api->show($id);
        } catch (Exception $e) {
            $product = null;
            $error = true;
            $can_retry = $e->getCode() == 503;
        }
        return view('product', [
            'product' => $product,
            'error' => $error,
            'can_retry' => $can_retry,
        ]);

    }

}
