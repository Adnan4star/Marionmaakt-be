<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ArticleFilter;
use App\Models\BlogArticle;
use App\Models\FilterValue;
use Illuminate\Http\Request;

class StoreFrontController extends Controller
{
    public function SearchBlog(Request $request)
    {
        if ($request->input('value')) {
            $blog_article = BlogArticle::where('title', 'like', '%' . $request->input('value') . '%')->get();
            
            if ($blog_article->isEmpty()) {
                $data = [
                    'success' => false,
                    'message' => 'No records found against title',
                ];
            } else {
                $data = [
                    'success' => true,
                    'data' => $blog_article,
                ];
            }
        } else {
            $data = [
                'success' => false,
                'message' => 'No search value provided',
            ];
        }
        return response()->json($data);
    }

    public function FilterBlog(Request $request)
    {
        $tags = $request->input('tags');
        if(!$tags){
            return response()->json([
                'success' => false,
                'message' => 'tags input is reqired',
            ]);
        }
        $tagsArray = explode(',',$tags);

        $filterId = FilterValue::whereIn('value',$tagsArray)->pluck('id');

        if ($filterId->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No matching records found.'
            ]);
        }

        $articleId = ArticleFilter::whereIn('filter_value_id', $filterId)->pluck('article_id');

        if ($articleId->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No article found against given tag.'
            ]);
        }
        // dd($articleId);

        $article = BlogArticle::whereIn('id',$articleId)->get();

        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }
}
