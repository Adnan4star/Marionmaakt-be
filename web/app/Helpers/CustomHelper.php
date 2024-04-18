<?php

function getShop($session)
{

    $shop = null;
    if ($session) {
        $shop = \App\Models\Session::where('shop', $session->getShop())->first();
    }
   
    if ($shop == null) {
        $shop = \App\Models\Session::first();
        // dd($shop);
    }
   
    return $shop;
}
function getClient($session)
{
    $client = new \Shopify\Clients\Rest($session->getShop(), $session->getAccessToken());
    return $client;
}
function sendResponse($data = null, $status = 200)
{
    return response()->json(["errors" => false, "data" => $data], $status);
}
function sendError($data = null, $status = 400)
{
    return response()->json(["errors" => true, "data" => $data], $status);
}
