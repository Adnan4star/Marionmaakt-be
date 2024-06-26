<?php

function getShop($session)
{
    if ($session) {
        return \App\Models\Session::where('shop', $session->getShop())->first();
    } else {
        return \App\Models\Session::first();
    }
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
