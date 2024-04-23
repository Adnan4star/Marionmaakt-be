<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Shopify\Clients\Rest;

class ProductController extends Controller
{
    public function ProductsSync(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        // dd($shop);
        $this->syncProducts($shop);
    }

    public function syncProducts($session, $nextPage = null)
    {
        $client = new Rest($session->shop, $session->access_token);
        $result = $client->get('products', [], [
            'limit' => 250,
            'page_info' => $nextPage,
        ]);
        // dd($result);
        $products = $result->getDecodedBody()['products'];
        // dd($products);
        foreach ($products as $product) {
            $this->createUpdateProduct($product, $session);
        }
        
        if (isset($result) && ($nextPageInfo = $result->getPageInfo()) && $nextPageInfo->hasNextPage()) {
            $nextUrl = $nextPageInfo->getNextPageUrl();
            if ($nextUrl) {
                $arr = explode('page_info=', $nextUrl);
                $this->syncProducts($session, $arr[count($arr) - 1]);
            }
        }
    }

    public function createUpdateProduct($product, $shop)
    {
        // dd($product);
        $product = json_decode(json_encode($product), false);
        $p = Product::firstOrCreate([
            'shop_id' => $shop->id,
            'shopify_id' => $product->id
        ], [
            'title' => $product->title,
            'description' => $product->body_html,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'type' => $product->product_type,
            'tags' => $product->tags,
            'status' => $product->status,
            'published_at' => $product->published_at
        ]);
            
        foreach ($product->images as $imageData) {
            // dd($product->images);
       
            $image = ProductImage::updateOrCreate(
                [
                    'product_id' => $p->id,
                    'shopify_id' => $imageData->id
                ],
                [
                    'alt' => $imageData->alt,
                    'position' => $imageData->position,
                    'width' => $imageData->width,
                    'height' => $imageData->height,
                    'src' => $imageData->src,
                ]
            );
        }

        $p->featured_image = $product->images ? $product->images[0]->src : '';
        $p->options = json_encode($product->options);
        $p->save();
        // dd($p);

        if (!empty($product->variants)) {
            foreach ($product->variants as $variant) {
                $v = ProductVariant::firstOrCreate([
                    'shopify_id' => $variant->id
                ], [
                    'shop_id' => $shop->id,
                    'shopify_product_id' => $variant->product_id,
                    'title' => $variant->title,
                    'option1' => $variant->option1,
                    'option2' => $variant->option2,
                    'option3' => $variant->option3,
                    'sku' => $variant->sku,
                    'requires_shipping' => $variant->requires_shipping,
                    'fulfillment_service' => $variant->fulfillment_service,
                    'taxable' => $variant->taxable,
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'weight' => $variant->weight,
                    'grams' => $variant->grams,
                    'weight_unit' => $variant->weight_unit,
                    'inventory_item_id' => $variant->inventory_item_id,
                    'inventory_management' => $variant->inventory_management,
                    'inventory_quantity' => $variant->inventory_quantity,
                    'inventory_policy' => $variant->inventory_policy
                ]);

                $v->image = '';
                if (!empty($product->images)) {
                    foreach ($product->images as $image) {
                        if (isset($variant->image_id) && $image->id == $variant->image_id) {
                            $v->image = $image->src;
                            break;
                        }
                    }
                }
                $v->save();
                // dd($v);
            }
        }
    }

    public function showProduct(Request $request){
        $shop = getShop($request->get('shopifySession'));
        if(!$shop){
            return response()->json([
                'success' => false,
                'message' => 'Shop not found.'
            ], 404);
        }

        try {
            $products = Product::with('ProductVariant')->with('images')
                    ->where('shop_id', $shop->id)
                    ->orderBy('id', 'desc')
                    ->paginate(20);

            // dd($products);
            return response()->json($products);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function DeleteProduct($product, $shop)
    {
        $prod = Product::where('shopify_id', $product->id)->where('shop_id',$shop->id)->first();
        if(isset($prod)){
            $variants = ProductVariant::where('shopify_product_id', $prod->id)->get();
            if($variants->count()){
                foreach ($variants as $variant){
                    $variant->delete();
                }
            }
            $prod->delete();
        }

    }
}
