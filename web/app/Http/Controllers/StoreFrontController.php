<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ArticleFilter;
use App\Models\BlogArticle;
use App\Models\FilterValue;
use Illuminate\Http\Request;

class StoreFrontController extends Controller
{
    public function SearchBlog(Request $request){
        if ($request->input('value')) {
            $blog_article=BlogArticle::query();
            $blog_article = $blog_article->where('title', 'like', '%' . $request->value . '%')->get();
            $data = [
                'success'=>true,
                'data' => $blog_article,
            ];

        }else{
            $data = [
                'success'=>false,
                'message' => 'Not Found',
            ];
        }
        return response()->json($data);
    }

    public function FilterBlog(Request $request){
        if ($request->input('tags')) {
                $tags=explode(',',$request->tags);

                $filters_values=FilterValue::whereIn('value',$tags)->pluck('id')->toArray();
                $article_filter=ArticleFilter::whereIn('filter_value_id',$filters_values)->pluck('article_id')->toArray();
            $unique_article_filter = array_unique($article_filter);

            $blog_article=BlogArticle::whereIn('id',$unique_article_filter)->get();

            $data = [
                'success'=>true,
                'data' => $blog_article,
            ];

        }else{

            $data = [
                'success'=>false,
                'message' => 'Not Found',
            ];
        }
        return response()->json($data);
    }
}
