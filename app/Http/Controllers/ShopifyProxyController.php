<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShopifyProxyController extends Controller
{
    public function proxyRequest(Request $request, $store)
    {
        $storeConfig = config('shopify.stores')[$store];
        if (!$storeConfig) {
            return response('Store not found', 404);
        }
        $client = new Client([
            'base_uri' => $storeConfig['url'],
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $storeConfig['access_token'],
            ]
        ]);

        $pathSegments = explode('/', $request->path());
        $storePosition = array_search($store, $pathSegments);
        $relevantPathSegments = array_slice($pathSegments, $storePosition + 1);
        $relevantPath = implode('/', $relevantPathSegments);

        $options = [];

        if ($request->isMethod('get') && $request->query()) {
            $options['query'] = $request->query();
        } else {
            if ($request->isJson()) {
                $options['json'] = $request->json()->all();
            }
        }

        $response = $client->request($request->method(), $relevantPath, $options);

        return response($response->getBody(), $response->getStatusCode());
    }
}
