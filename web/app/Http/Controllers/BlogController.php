<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ArticleDescription;
use App\Models\ArticleFilter;
use App\Models\ArticleIngredientProduct;
use App\Models\ArticleInstruction;
use App\Models\ArticleToolAccessory;
use App\Models\Blog;
use App\Models\BlogArticle;
use App\Models\Filter;
use App\Models\FilterValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Session;
use Illuminate\Http\Request;
use Shopify\Clients\Rest;

class BlogController extends Controller
{

    public function index(Request $request){
        $shop = getShop($request->get('shopifySession'));
        try {
            if ($shop) {
                $blogs=BlogArticle::query();

                $blogs=$blogs->where('shop_id',$shop->id)->orderBy('id', 'Desc')->paginate(20);
                return response()->json($blogs);
            }
        }catch (\Exception $exception){

        }
    }
    public function BlogsSync(Request $request){
        $shop = getShop($request->get('shopifySession'));
        $this->syncBlogs($shop);
        $data = [
            'success'=>true,
            'message' => 'Blogs Sync Successfully',
        ];
        return response()->json($data);
    }

    public function syncBlogs($session, $nextPage = null)
    {
        $client = new Rest($session->shop, $session->access_token);
        $result = $client->get('blogs', [], [
            'limit' => 250,
            'page_info' => $nextPage,
        ]);

        if($nextPage==null){
            $blog_ids=[];
        }
        $blogs = $result->getDecodedBody()['blogs'];
        // dd($blogs);
        foreach ($blogs as $blog) {
            // if($blog['id']==110005387586) {
                array_push($blog_ids, $blog['id']);
                $this->createUpdateBlog($blog, $session);
            // }
        }

        if (isset($result)) {
            if ($result->getPageInfo() ? true : false) {
                $nextUrl = $result->getPageInfo()->getNextPageUrl();
                if (isset($nextUrl)) {
                    $arr = explode('page_info=', $result->getPageInfo()->getNextPageUrl());
                    $this->syncBlogs($arr[count($arr) - 1]);
                }
            }
        }
        Blog::whereNotIn('shopify_id',$blog_ids)->delete();
    }

    public function createUpdateBlog($blog, $shop)
    {
        $blog = json_decode(json_encode($blog), false);
        $b = Blog::where([
            'shop_id' => $shop->id,
            'shopify_id' => $blog->id
        ])->first();
        if ($b === null) {
            $b = new Blog();
        }

        $b->shopify_id = $blog->id;
        $b->shop_id = $shop->id;
        $b->title = $blog->title;
        $b->handle = $blog->handle;
        $b->tags = $blog->tags;
        $b->commentable = $blog->commentable;
        $b->feedburner = $blog->feedburner;
        $b->feedburner_location = $blog->feedburner_location;
        $b->save();

        $client = new Rest($shop->shop, $shop->access_token);
        $articles = $client->get('/admin/api/2023-07/blogs/' . $blog->id . '/articles.json');


        $articles = $articles->getDecodedBody()['articles'];

        $blog_article_ids=[];
        foreach ($articles as $article) {
            $article = json_decode(json_encode($article), false);
            array_push($blog_article_ids,$article->id);
            $a = BlogArticle::where([
                'shop_id' => $shop->id,
                'shopify_id' => $article->id
            ])->first();
            if ($a === null) {
                $a = new BlogArticle();
            }

            $a->shopify_id = $article->id;
            $a->shop_id = $shop->id;
            $a->title = $article->title;
            $a->handle = $article->handle;
            $a->tags = $article->tags;
            $a->published_at = $article->published_at;
            $a->shopify_blog_id = $article->blog_id;
            $a->blog_id = $b->id;
            $a->author = $article->author;
            $a->user_id = $article->user_id;
            $a->body_html = $article->body_html;
            $a->summary_html = $article->summary_html;
            if(isset($article->image)) {
                $a->image = $article->image->src;
            }
            $a->save();
        }

        BlogArticle::where('blog_id',$b->id)->whereNotIn('shopify_id',$blog_article_ids)->delete();

    }

    public function CreateBlog(Request $request){
        try {
            $shop = getShop($request->get('shopifySession'));
            // dd($shop);
            if($shop){
                $products=Product::all();
                $filters=Filter::with('FilterValues')->get();
                $data = [
                    'products' => $products,
                    'filters' => $filters,
                    'success'=>true
                ];
            }
        }catch (\Exception $exception){
            $data=[
                'error'=>$exception->getMessage(),
                'success'=>false
            ];
        }
        return response()->json($data);

    }

    public function SaveBlog(Request $request){

        $blog=Blog::first();

        $session=Session::first();
        $client = new Rest($session->shop, $session->access_token);
        // dd($client);

        if($request->published_at=='hidden'){
            $published_at=false;
            $db_published_at=null;
        }else{
            $published_at=now();
            $db_published_at=now();
        }


        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $destinationPath = 'images/';
            $filename1 = now()->format('YmdHi') . str_replace([' ', '(', ')'], '-', $file->getClientOriginalName());
            $file->move($destinationPath, $filename1);
            $filename1 = (asset('images/' . $filename1));

        }


        $article=$client->post('/admin/api/2023-10/blogs/'.$blog->shopify_id.'/articles.json',[
            'article'=>array(
                'title' => $request->title,
                'body_html' => $request->body_html,
                'published_at' => $published_at,
                'author' => 'Ahmad Naeem',
                'summary_html'=>$request->summary_html,
                'image' => [
                    'src' => 'https://6c819239693cc4960b69-cc9b957bf963b53239339d3141093094.lmsin.net/1000011025602-Blue-Blue-1000011025602_01-1200.jpg'
                ]
            )
        ]);
        $response = $article->getDecodedBody();
        // dd($response);
        if (isset($response) && !isset($response['errors'])) {

            $response=$response['article'];
            $blog_article=new BlogArticle();
            $blog_article->shop_id=$session->id;
            $blog_article->blog_id=$blog->id;
            $blog_article->shopify_id=$response['id'];
            $blog_article->title=$response['title'];
            $blog_article->body_html=$response['body_html'];
            $blog_article->summary_html=$response['summary_html'];
            $blog_article->published_at=$response['published_at'];
            $blog_article->shopify_blog_id=$response['blog_id'];
            $blog_article->author=$response['author'];
            $blog_article->user_id=$response['user_id'];
            $blog_article->handle=$response['handle'];
            if($response['image']) {
                $blog_article->image = $response['image']['src'];
            }
            $blog_article->preparation=$request->preparation;
            $blog_article->total_time=$request->total_time;
            $blog_article->recipe_by=$request->recipe_by;
            $blog_article->level=$request->level;
            $blog_article->shelf_life=$request->shelf_life;
            $blog_article->no_of_ingredients=$request->no_of_ingredients;
            $blog_article->save();
            // dd($blog_article);
            if(isset($request->ingredient_products)) {

                foreach ($request->ingredient_products as $ingredient_product) {

                    $product = Product::find($ingredient_product['id']);

                            $article_ingredient_product = new ArticleIngredientProduct();
                            $article_ingredient_product->article_id = $blog_article->id;
                            $article_ingredient_product->product_id = $product->id;
                            $article_ingredient_product->grams = $ingredient_product['grams'];
                            $article_ingredient_product->percentage = $ingredient_product['percentage'];
                            $article_ingredient_product->phase = $ingredient_product['phase'];
                            $article_ingredient_product->save();
                }
            }

            if(isset($request->tool_accessories)) {

                foreach ($request->tool_accessories as $tool_accessory) {

                    $product = Product::find($tool_accessory['id']);
                    $article_tool_accessory = new ArticleToolAccessory();
                    $article_tool_accessory->article_id = $blog_article->id;
                    $article_tool_accessory->product_id = $product->id;
                    $article_tool_accessory->save();
                }
            }

            if(isset($request->instructions)) {

                foreach ($request->instructions as $instruction) {
                    $article_instruction = new ArticleInstruction();
                    $article_instruction->article_id = $blog_article->id;
                    $article_instruction->instructions = $instruction;
                    $article_instruction->save();
                }
            }

            if(isset($request->usage)){
                $blog_article->usage=$request->usage;
                $blog_article->save();
            }

            if(isset($request->filters)){
                foreach ($request->filters as $filter) {
                    $filter_article=new ArticleFilter() ;
                    $filter_article->article_id = $blog_article->id;
                    $filter_article->filter_id=$filter['id'];
                    $filter_article->filter_value_id=$filter['value_id'];
                    $filter_article->save();

                }
            }

            if(isset($request->additional_details)){
                foreach ($request->additional_details as $additional_detail) {
                    $article_description=new ArticleDescription() ;
                    $article_description->article_id = $blog_article->id;
                    $article_description->name=$additional_detail['name'];
                    $article_description->description=$additional_detail['description'];
                    $article_description->save();

                }
            }

            $this->CreateUpdateMetafield($blog_article,$session);
            $data = [
                'success'=>true,
                'message' => 'Blog Article created Successfully',
            ];
        }else{
            $data = [
                'success'=>false,
                'message' => 'Error occured',
            ];
        }
        return response()->json($data);
    }
    public function UpdateBlog(Request $request){

        $blog=Blog::first();
        // dd($blog);
        $session=Session::first();
        // dd($session);
        $client = new Rest($session->shop, $session->access_token);
        // dd($client);
        $blog_article=BlogArticle::find($request->id);
        // dd($blog_article);
        if($request->published_at=='hidden'){
            $published_at=false;
            $db_published_at=null;
        }else{
            $published_at=now();
            $db_published_at=now();
        }


        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $destinationPath = 'images/';
            $filename1 = now()->format('YmdHi') . str_replace([' ', '(', ')'], '-', $file->getClientOriginalName());
            $file->move($destinationPath, $filename1);
            $filename1 = (asset('images/' . $filename1));

        }


        $article=$client->put('/admin/api/2023-10/blogs/'.$blog->shopify_id.'/articles/'.$blog_article->shopify_id.'.json',[
            'article'=>array(
                'title' => $request->title,
                'body_html' => $request->body_html,
                'published_at' => $published_at,
                'author' => 'Ahmad Naeem',
                'summary_html'=>$request->summary_html,
                'image' => [
                    'src' => 'https://6c819239693cc4960b69-cc9b957bf963b53239339d3141093094.lmsin.net/1000011025602-Blue-Blue-1000011025602_01-1200.jpg'
                ]
            )
        ]);
        $response = $article->getDecodedBody();
        // dd($response);
        if (isset($response) && !isset($response['errors'])) {

            $response=$response['article'];
            $blog_article->shop_id=$session->id;
            $blog_article->blog_id=$blog->id;
            $blog_article->shopify_id=$response['id'];
            $blog_article->title=$response['title'];
            $blog_article->body_html=$response['body_html'];
            $blog_article->summary_html=$response['summary_html'];
            $blog_article->published_at=$response['published_at'];
            $blog_article->shopify_blog_id=$response['blog_id'];
            $blog_article->author=$response['author'];
            $blog_article->user_id=$response['user_id'];
            $blog_article->handle=$response['handle'];
            if($response['image']) {
                $blog_article->image = $response['image']['src'];
            }
            $blog_article->preparation=$request->preparation;
            $blog_article->total_time=$request->total_time;
            $blog_article->recipe_by=$request->recipe_by;
            $blog_article->level=$request->level;
            $blog_article->shelf_life=$request->shelf_life;
            $blog_article->no_of_ingredients=$request->no_of_ingredients;
            $blog_article->save();

            // dd($blog_article);
            if(isset($request->ingredient_products)) {
                ArticleIngredientProduct::where('article_id',$request->id)->delete();
                foreach ($request->ingredient_products as $ingredient_product) {

                    $product = Product::find($ingredient_product['id']);

                            $article_ingredient_product = new ArticleIngredientProduct();
                            $article_ingredient_product->article_id = $blog_article->id;
                            $article_ingredient_product->product_id = $product->id;
                            $article_ingredient_product->grams = $ingredient_product['grams'];
                            $article_ingredient_product->percentage = $ingredient_product['percentage'];
                            $article_ingredient_product->phase = $ingredient_product['phase'];
                            $article_ingredient_product->save();
                }
                // dd($article_ingredient_product);
            }

            if(isset($request->tool_accessories)) {
                ArticleToolAccessory::where('article_id',$request->id)->delete();
                foreach ($request->tool_accessories as $tool_accessory) {

                    $product = Product::find($tool_accessory['id']);
                    $article_tool_accessory = new ArticleToolAccessory();
                    $article_tool_accessory->article_id = $blog_article->id;
                    $article_tool_accessory->product_id = $product->id;
                    $article_tool_accessory->save();
                }
                // dd($article_tool_accessory);
            }

            if(isset($request->instructions)) {
                ArticleInstruction::where('article_id',$request->id)->delete();
                foreach ($request->instructions as $instruction) {
                    $article_instruction = new ArticleInstruction();
                    $article_instruction->article_id = $blog_article->id;
                    $article_instruction->instructions = $instruction;
                    $article_instruction->save();
                }
                // dd($article_instruction);
            }

            if(isset($request->usage)){
                $blog_article->usage=$request->usage;
                $blog_article->save();
            }

            if(isset($request->filters)){
                ArticleFilter::where('article_id',$request->id)->delete();
                foreach ($request->filters as $filter) {
                    $filter_article=new ArticleFilter() ;
                    $filter_article->article_id = $blog_article->id;
                    $filter_article->filter_id=$filter['id'];
                    $filter_article->filter_value_id=$filter['value_id'];
                    $filter_article->save();

                }
            }

            if(isset($request->additional_details)){
                ArticleDescription::where('article_id',$request->id)->delete();
                foreach ($request->additional_details as $additional_detail) {
                    $article_description=new ArticleDescription() ;
                    $article_description->article_id = $blog_article->id;
                    $article_description->name=$additional_detail['name'];
                    $article_description->description=$additional_detail['description'];
                    $article_description->save();

                }
            }
            $this->CreateUpdateMetafield($blog_article,$session);
            $data = [
                'success'=>true,
                'message' => 'Blog Article updated Successfully',
            ];
        }else{
            $data = [
                'success'=>false,
                'message' => 'Error occured',
            ];
        }
        return response()->json($data);
    }

    public function CreateUpdateMetafield($blog_article,$session){

        $client = new Rest($session->shop, $session->access_token);



        $getdata=array();
        $ingredient_product=array();
        $tool_accessory=array();
        $instructions=array();
        $additonal_details=array();

        if(count($blog_article->ArticleIngredientProduct) > 0 ){
            foreach ($blog_article->ArticleIngredientProduct as $ar_ingredient_product){

             $data_ingredient['handle']=$ar_ingredient_product->ProductRecord->handle;
             $data_ingredient['grams']=$ar_ingredient_product->grams;
             $data_ingredient['percentage']=$ar_ingredient_product->percentage;
             $data_ingredient['phase']=$ar_ingredient_product->phase;
             array_push($ingredient_product,$data_ingredient);
            }
        }

        if(count($blog_article->ArticleToolAccessory) > 0 ){
            foreach ($blog_article->ArticleToolAccessory as $ar_tool_accessory){
                array_push($tool_accessory,$ar_tool_accessory->ProductRecord->handle);
            }
        }

        if(count($blog_article->ArticleInstructions) > 0 ){
            foreach ($blog_article->ArticleInstructions as $ar_instruction){
                array_push($instructions,$ar_instruction->instructions);
            }
        }


        $data_array = [];
        $article_filters = ArticleFilter::with('FilterValues')
            ->where('article_id', $blog_article->id)
            ->get();

// Temporary array to hold values for each label
        $temporary_array = [];

        foreach ($article_filters as $filter) {

            // Group filter values for each article filter
            $grouped_values = $filter->filterValues->groupBy('filter_id');

            // Iterate over each group of values
            foreach ($grouped_values as $filter_id => $values) {
                // Create an array to hold the values
                $value_array = [];
                foreach ($values as $value) {
                    $value_array[] = $value->value;
                }
                $filter_l = Filter::where('id', $filter_id)->first();

                // Check if label already exists in the temporary array
                if (isset($temporary_array[$filter_l->label])) {
                    // If exists, merge the values
                    $temporary_array[$filter_l->label]['value'] = array_merge($temporary_array[$filter_l->label]['value'], $value_array);
                } else {
                    // If not exists, add a new entry
                    $temporary_array[$filter_l->label] = [
                        'label' => $filter_l->label,
                        'value' => $value_array,
                    ];
                }
            }
        }

// Convert the temporary array to final $data_array
        $data_array = array_values($temporary_array);



        if(count($blog_article->ArticleDescriptions) > 0 ){
            foreach ($blog_article->ArticleDescriptions as $articleDescription){

                $additional_data['name']=$articleDescription->name;
                $additional_data['description']=$articleDescription->description;
                array_push($additonal_details,$additional_data);
            }
        }


        $getdata['preparation']=$blog_article->preparation;
        $getdata['total_time']=$blog_article->total_time;
        $getdata['recipe_by']=$blog_article->recipe_by;
        $getdata['level']=$blog_article->level;
        $getdata['shelf_life']=$blog_article->shelf_life;
        $getdata['no_of_ingredients']=$blog_article->no_of_ingredients;
        $getdata['ingredient_product']=$ingredient_product;
        $getdata['tools_accessories']=$tool_accessory;
        $getdata['instructions']=$instructions;
        $getdata['additional_details']=$additonal_details;
        $getdata['usage']=$blog_article->usage;
        $getdata['filters']=$data_array;





        $metafield_data = [
            "metafield" =>
                [
                    "key" => 'receipe_details',
                    "value" => json_encode($getdata),
                    "type" => "json_string",
                    "namespace" => "Marionmaakt",

                ]
        ];

        $article_metafield = $client->post('/articles/' . $blog_article->shopify_id . '/metafields.json', $metafield_data);

        $article_metafield = $article_metafield->getDecodedBody();

        if (isset($article_metafield) && !isset($article_metafield['errors'])) {

        }
    }

    public function DeleteBlog(Request $request){
        $shop = getShop($request->get('shopifySession'));
        $blog_article=BlogArticle::find($request->id);
        // dd($blog_article);
        if($blog_article){
            $blog=Blog::find($blog_article->blog_id);
        $client = new Rest($shop->shop, $shop->access_token);
        $article=$client->delete('/admin/api/2023-10/blogs/'.$blog->shopify_id.'/articles/'.$blog_article->shopify_id.'.json');
        $article=$article->getDecodedBody();

            if (isset($article_metafield) && isset($article_metafield['errors'])) {
                $data=[
                    'error'=>$article_metafield['errors'],
                    'success'=>false
                ];

            }else{
            ArticleInstruction::where('article_id', $blog_article->id)->delete();
            ArticleToolAccessory::where('article_id', $blog_article->id)->delete();
            ArticleIngredientProduct::where('article_id', $blog_article->id)->delete();
            $blog_article->delete();

            $data = [
                'success'=>true,
                'message' => 'Blog Article deleted Successfully',
            ];
        }
        }

        return response()->json($data);
    }

    public function EditBlog(Request $request){
        $blog_article = BlogArticle::find($request->id);
        if($blog_article){

            $getdata=array();
            $ingredient_product=array();
            $tool_accessory=array();
            $instructions=array();
            $additonal_details=array();

            if(count($blog_article->ArticleIngredientProduct) > 0 ){
                foreach ($blog_article->ArticleIngredientProduct as $ar_ingredient_product){

                    $data_ingredient['handle']=$ar_ingredient_product->ProductRecord->handle;
                    $data_ingredient['grams']=$ar_ingredient_product->grams;
                    $data_ingredient['percentage']=$ar_ingredient_product->percentage;
                    $data_ingredient['phase']=$ar_ingredient_product->phase;
                    array_push($ingredient_product,$data_ingredient);
                }
            }

            if(count($blog_article->ArticleToolAccessory) > 0 ){
                foreach ($blog_article->ArticleToolAccessory as $ar_tool_accessory){
                    array_push($tool_accessory,$ar_tool_accessory->ProductRecord->handle);
                }
            }

            if(count($blog_article->ArticleInstructions) > 0 ){
                foreach ($blog_article->ArticleInstructions as $ar_instruction){
                    array_push($instructions,$ar_instruction->instructions);
                }
            }





            if(count($blog_article->ArticleDescriptions) > 0 ){
                foreach ($blog_article->ArticleDescriptions as $articleDescription){

                    $additional_data['name']=$articleDescription->name;
                    $additional_data['description']=$articleDescription->description;
                    array_push($additonal_details,$additional_data);
                }
            }

            $getdata['preparation']=$blog_article->preparation;
            $getdata['total_time']=$blog_article->total_time;
            $getdata['recipe_by']=$blog_article->recipe_by;
            $getdata['level']=$blog_article->level;
            $getdata['shelf_life']=$blog_article->shelf_life;
            $getdata['ingredient_product']=$ingredient_product;
            $getdata['tools_accessories']=$tool_accessory;
            $getdata['instructions']=$instructions;
            $getdata['additional_details']=$additonal_details;
            $getdata['usage']=$blog_article->usage;

            return response()->json($getdata);

        }

    }
}
