<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Shopify\Clients\Rest;

class ProductController extends Controller
{
    public function ProductsSync(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        $this->syncProducts($shop);
    }
    
    public function syncProducts($session, $nextPage = null)
    {
        $client = new Rest($session->shop, $session->access_token);
        $result = $client->get('products', [], [
            'limit' => 250,
            'page_info' => $nextPage,
        ]);
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
        $product = json_decode(json_encode($product), false);

        // Retrieve existing product or create new instance
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

        // Assign or update the remaining fields
        $p->featured_image = $product->images ? $product->images[0]->src : '';
        $p->options = json_encode($product->options);
        $p->save();
        // dd($p);

        // Process variants
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

                // Handle variant images
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
