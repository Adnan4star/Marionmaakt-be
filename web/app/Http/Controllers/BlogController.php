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
use Exception;
use Facade\FlareClient\Stacktrace\File;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use PHPUnit\Framework\Constraint\Count;
use Shopify\Clients\Rest;
use Shopify\Rest\Admin2022_04\Shop;

class BlogController extends Controller
{
    public function index(Request $request){
        $shop = getShop($request->get('shopifySession'));
        if(!$shop){
            return response()->json([
                'success' => false,
                'message' => 'Shop not found.'
            ], 404);
        }
        try {
            $blogs = BlogArticle::where('shop_id',$shop->id)->orderBy('id', 'desc')->paginate(20);
            return response()->json($blogs);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function blogssSync(Request $request) {
        $shop = getShop($request->get('shopifySession'));
        if (!$shop) {
            return response()->json([
                'success' => true,
                'message' => 'Shop not found.'
            ]);
        }
        try {
            $this->ssyncBlogss($shop);
            return response()->json([
                'success' => true,
                'message' => 'Blogs synchronization successfull'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function ssyncBlogss($session, $nextPage=null) {
        $client = new Rest($session->shop, $session->access_token);
        $response = $client->get('blogs',[],[
            'limit' => 250,
            'pageInfo' => $nextPage
        ]);

        if ($nextPage == null) {
            $blog_ids = [];
        }
        $blogs = $response->getDecodedBody()['blogs'];
        // dd($blogs);
        foreach ($blogs as $blog) {
            if ($blog['id'] == 82187550810) {
                array_push($blog_ids, $blog['id']);
                $this->ccreateUppdateBlog($blog, $session);
                break;
            }else {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog id doesnt exist'
                ]);
            }
        }

        if(isset($response)){
            if ($response->getPageInfo() ? true : false){
                $nextUrl = $response->getPageInfo()->getNextPageUrl();
                if (isset($nextUrl)) {
                    $arr = explode('page_info=', $response->getPageInfo()->getNextPageUrl());
                    $this->syncBlogs($arr[count($arr) - 1]);
                }
            }
        }
        Blog::whereNotIn('shopify_id', $blog_ids)->delete();
    }

    public function ccreateUppdateBlog($blogData, $shop) {
        // $blog = json_decode(json_encode($blogData),false);
        $blog = (object) $blogData;
        $tags = is_array($blog->tags) ? json_encode($blog->tags) : $blog->tags;
        $b = Blog::updateOrCreate(
            [
                'shopify_id' => $blog->id,
                'shop_id' => $shop->id
            ],
            [
                'title' => $blog->title,
                'handle' => $blog->handle,
                'tags' => $blog->tags,
                'commentable' => $blog->commentable,
                'feedburner' => $blog->feedburner,
                'feedburner_location' => $blog->feedburner_location,
            ]
        );

        $client = new Rest($shop->shop, $shop->access_token);
        $result = $client->get('/admin/api/2023-07/blogs/'.$blog->id.'/articles.json');
        // dd($result);
        $articles = $result->getDecodedBody()['articles'];
        // dd($articles);
        $blog_article_ids = [];
        foreach ($articles as $articleData) {
            $article = (object) $articleData;
            $a = BlogArticle::updateOrCreate(
                [
                    'shopify_id' => $article->id,
                    'shop_id' => $shop->id
                ],
                [
                    'title' => $article->title,
                    'body_html' => $article->body_html,
                    'summary_html' => $article->summary_html,
                    'shopify_blog_id' => $article->blog_id,
                    'author' => $article->author,
                    'user_id' => $article->user_id,
                    'handle' => $article->handle,
                    'tags' => $article->tags,
                    'published_at' => $article->published_at,
                    'blog_id' => $b->id,
                ]
            );
            
            $imageSrc = null;
            if (isset($article->image) && is_array($article->image) && isset($article->image['src'])) {
                $imageSrc = $article->image['src'];
            }
            $a->image = $imageSrc;
            $a->save();
            $blog_article_ids[] = $article->id;
        }
        BlogArticle::where('blog_id',$b->id)->whereNotIn('shopify_id',$blog_article_ids)->delete();
    }

    public function CreateBlog(Request $request){
        try {
            $shop = getShop($request->get('shopifySession'));
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shop found.'
                ], 404);
            }

            $products = Product::all();
            $filters = Filter::with('FilterValues')->get();

            return response()->json([
                'products' => $products,
                'filters' => $filters,
                'success' => true,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function saveReceipe(Request $request){
        $blog = Blog::first();
        $session = Session::first();
        $client = new Rest($session->shop, $session->access_token);
        
        if ($request->published_at == 'hidden'){
            $published_at = false;
        }else{
            $published_at = now();
        }

        if ($request->hasFile('featured_image')){
            $file = $request->file('featured_image');
            $imagePath = 'images/';
            $filename1 = now()->format('YmdHi'). str_replace([' ', '(', ')'], '-', $file->getClientOriginalName());
            $file->move($imagePath, $filename1);
            $filename1 = (asset('images/' . $filename1));
        }
        
        $article = $client->post('admin/api/2023-10/blogs/'. $blog->shopify_id .'/articles.json',[
            'article' => array(
                'title' => $request->title,
                'author' => 'Ahmad Naeem',
                'body_html' => $request->body_html,
                'summary_html' => $request->summary_html,
                'published_at' => $published_at,
                'image' => [
                    'src' => 'https://6c819239693cc4960b69-cc9b957bf963b53239339d3141093094.lmsin.net/1000011025602-Blue-Blue-1000011025602_01-1200.jpg'
                ]
            )
        ]);
        $response = $article->getDecodedBody();
        // dd($response);
        if (isset($response) && !isset($response['errors'])){
            $response = $response['article'];
            $blog_article = new BlogArticle();
            $blog_article->shop_id = $session->id;
            $blog_article->blog_id = $blog->id;
            $blog_article->shopify_id = $response['id'];
            $blog_article->title = $response['title'];
            $blog_article->body_html = $response['body_html'];
            $blog_article->summary_html = $response['summary_html'];
            $blog_article->shopify_blog_id = $response['blog_id'];
            $blog_article->author = $response['author'];
            $blog_article->user_id = $response['user_id'];
            $blog_article->handle = $response['handle'];
            $blog_article->published_at = $response['published_at'];
            if (isset($response['image'])){
                $blog_article->image = $response['image']['src'];
            }
            $blog_article->usage = $request->usage;
            $blog_article->preparation = $request->preparation;
            $blog_article->total_time = $request->total_time;
            $blog_article->recipe_by = $request->recipe_by;
            $blog_article->level = $request->level;
            $blog_article->shelf_life = $request->shelf_life;
            $blog_article->no_of_ingredients = $request->no_of_ingredients;
            $blog_article->save();

            if (isset($request->ingredient_products)) {
                foreach ($request->ingredient_products as $ingredient_product) {
                    $product = Product::find($ingredient_product['id']);

                    $article_ingredient_product = new ArticleIngredientProduct();
                    $article_ingredient_product->article_id = $blog_article['id'];
                    $article_ingredient_product->product_id = $product->id;
                    $article_ingredient_product->grams = $request['grams'];
                    $article_ingredient_product->percentage = $request['percentage'];
                    $article_ingredient_product->phase = $request['phase'];
                    $article_ingredient_product->save();
                    // dd($article_ingredient_product);
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

            if (isset($request->filters)){
                foreach ($request->filters as $filter) {
                    $article_filter = new ArticleFilter();
                    $article_filter->article_id = $blog_article->id;
                    $article_filter->filter_id = $filter['id'];
                    $article_filter->filter_value_id = $filter['value_id'];
                    $article_filter->save();
                }
            }

            if (isset($request->instructions)) {
                foreach ($request->instructions as $instruction) {
                    $article_instruction = new ArticleInstruction();
                    $article_instruction->article_id = $blog_article->id;
                    $article_instruction->instructions = $instruction;
                    $article_instruction->save();
                }
            }

            if (isset($request->descriptions)){
                foreach ($request->descriptions as $description) {
                    $article_description = new ArticleDescription();
                    $article_description->article_id  = $blog_article->id;
                    $article_description->name = $description['name'];
                    $article_description->description  = $description['description'];
                    $article_description->save();
                }
            }

            if (isset($request->usage)) {
                $blog_article->usage = $request->usage;
                $blog_article->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Receipe created successfully'
            ]);

        }else {
            return response()->json([
                'success' => false,
                'message' => 'Error occured!'
            ]);
        }
    }

    public function SaveBlog(Request $request){
        $blog = Blog::first();
        $session = Session::first();
        $client = new Rest($session->shop, $session->access_token);

        if ($request->published_at == 'hidden'){
            $published_at = false;
        }else{
            $published_at = now();
        }

        if ($request->hasFile('featured_image')){
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
        
        if (isset($response) && !isset($response['errors'])) {
            $response = $response['article'];
            $blog_article = new BlogArticle();
            $blog_article->shop_id = $session->id;
            $blog_article->blog_id = $blog->id;
            $blog_article->shopify_id = $response['id'];
            $blog_article->title=$response['title'];
            $blog_article->body_html=$response['body_html'];
            $blog_article->summary_html=$response['summary_html'];
            $blog_article->published_at=$response['published_at'];
            $blog_article->shopify_blog_id=$response['blog_id'];
            $blog_article->author=$response['author'];
            $blog_article->user_id=$response['user_id'];
            $blog_article->handle=$response['handle'];
            if ($response['image']){
                $blog_article->image = $response['image']['src'];
            }
            $blog_article->preparation=$request->preparation;
            $blog_article->total_time=$request->total_time;
            $blog_article->recipe_by=$request->recipe_by;
            $blog_article->level=$request->level;
            $blog_article->shelf_life=$request->shelf_life;
            $blog_article->no_of_ingredients=$request->no_of_ingredients;
            $blog_article->save();

            if (isset($request->ingredient_products)) {
                foreach ($request->ingredient_products as $ingredient_product) {
                    
                    $product = Product::find($ingredient_product['id']);

                    $article_ingredient_product = new ArticleIngredientProduct();
                    $article_ingredient_product->article_id = $blog_article['id'];
                    $article_ingredient_product->product_id = $product->id;
                    $article_ingredient_product->grams = $request['grams'];
                    $article_ingredient_product->percentage = $request['percentage'];
                    $article_ingredient_product->phase = $request['phase'];
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

        $blog = Blog::first();
        $session = Session::first();
        $blog_article = BlogArticle::find($request->id);
        $client = new Rest($session->shop, $session->access_token);

        if ($request->published_at == 'hidden'){
            $published_at = false;
        }else{
            $published_at = now();
        }

        if ($request->hasFile('featured_image')){
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
        
        if (isset($response) && !isset($response['errors'])) {
            $response = $response['article'];
            $blog_article = new BlogArticle();
            $blog_article->shop_id = $session->id;
            $blog_article->blog_id = $blog->id;
            $blog_article->shopify_id = $response['id'];
            $blog_article->title=$response['title'];
            $blog_article->body_html=$response['body_html'];
            $blog_article->summary_html=$response['summary_html'];
            $blog_article->published_at=$response['published_at'];
            $blog_article->shopify_blog_id=$response['blog_id'];
            $blog_article->author=$response['author'];
            $blog_article->user_id=$response['user_id'];
            $blog_article->handle=$response['handle'];
            if ($response['image']){
                $blog_article->image = $response['image']['src'];
            }
            $blog_article->preparation=$request->preparation;
            $blog_article->total_time=$request->total_time;
            $blog_article->recipe_by=$request->recipe_by;
            $blog_article->level=$request->level;
            $blog_article->shelf_life=$request->shelf_life;
            $blog_article->no_of_ingredients=$request->no_of_ingredients;
            $blog_article->save();

            if (isset($request->ingredient_products)) {
                foreach ($request->ingredient_products as $ingredient_product) {
                    
                    $product = Product::find($ingredient_product['id']);

                    $article_ingredient_product = new ArticleIngredientProduct();
                    $article_ingredient_product->article_id = $blog_article['id'];
                    $article_ingredient_product->product_id = $product->id;
                    $article_ingredient_product->grams = $request['grams'];
                    $article_ingredient_product->percentage = $request['percentage'];
                    $article_ingredient_product->phase = $request['phase'];
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
                'message' => 'Blog article updated successfully',
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
                    // dd($ingredient_product);
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
