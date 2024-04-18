<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Filter;
use App\Models\FilterValue;
use Exception;
use Illuminate\Http\Request;
use Shopify\Clients\Rest;

class FilterController extends Controller
{
    public function index(Request $request){
        $shop = getShop($request->get('shopifySession'));
        try {
            if ($shop) {
                $filters=Filter::query();
                if($request->status==0) {

                }else if($request->status==1){
                    $filters = $filters->where('status',1);
                }else if($request->status==2){
                    $filters = $filters->where('status',0);
                }

                $filters=$filters->with('FilterValues')->where('shop_id',$shop->id)->orderBy('id', 'Desc')->paginate(20);
                return response()->json($filters);
            }
        }catch (\Exception $exception){

        }
    }

    public function SaveFilter(Request $request){
        $shop = getShop($request->get('shopifySession'));

        try {
            if($shop) {
                if($request->filter_id){
                    $filter=Filter::find($request->filter_id);
                    FilterValue::where('filter_id',$filter->id)->delete();
                }else{
                    $filter=new Filter();
                }

                $filter->label=$request->label;
                $filter->status=$request->status;
                $filter->shop_id=$shop->id;
                $filter->save();

                if($request->values){

                    $values=explode(',',$request->values);
                    foreach ($values as $value){
                        $filter_value=new FilterValue();
                        $filter_value->filter_id=$filter->id;
                        $filter_value->value=$value;
                        $filter_value->save();
                    }

                }

                $this->CreateUpdateMetafield($shop);
                $data = [
                    'success'=>true,
                    'message' => 'Filter Saved Successfully',
                    'data' => $filter
                ];
            }
        }catch (\Exception $exception){
            $data=[
                'success'=>false,
                'message' => $exception->getMessage(),

            ];
        }
        return response()->json($data);

    }

    public function EditFilter(Request $request){

        $filter = Filter::with('FilterValues')->find($request->id);
        if($filter){

            $data = [
                'success'=>true,
                'data' => $filter
            ];
        }else{
            $data=[
                'success'=>false,
                'message' => 'Filter Not FOund',

            ];
        }
        return response()->json($data);
    }

    public function UpdateFilterStatus(Request $request){
        $shop = getShop($request->get('shopifySession'));
        try {
            if ($shop) {
                $filter=Filter::find($request->filter_id);
                if($filter){
                    $filter->status=$request->status;
                    $filter->save();
                    $data = [
                        'success'=>true,
                        'message' => 'Filter status updated successfully',
                        'data' => $filter
                    ];
                }else{
                    $data = [
                        'success'=>false,
                        'message' => 'Filter Not Found',

                    ];
                }
                $this->CreateUpdateMetafield($shop);

            }
        }catch (\Exception $exception){
            $data=[
                'success'=>false,
                'message' => $exception->getMessage(),

            ];
        }
        return response()->json($data);
    }

    public function DeleteFilter(Request $request){
        $shop = getShop($request->get('shopifySession'));
        try {
            if ($shop) {
                $filter=Filter::find($request->filter_id);
                if($filter){
                    FilterValue::where('filter_id',$filter->id)->delete();
                    $filter->delete();

                    $data = [
                        'success'=>true,
                        'message' => 'Filter deleted successfully',
                    ];
                }else{
                    $data = [
                        'success'=>false,
                        'message' => 'Filter Not Found',
                    ];
                }
                $this->CreateUpdateMetafield($shop);
            }
        }catch (\Exception $exception){
            $data=[
                'success'=>false,
                'message' => $exception->getMessage(),

            ];
        }
        return response()->json($data);
    }

    public function CreateUpdateMetafield($session)
    {

        $client = new Rest($session->shop, $session->access_token);

        $filters=Filter::with('FilterValues')->where('status',1)->get();

        $data_array=[];
        foreach ($filters as $filter){

            $value_array=[];
            if(count($filter->FilterValues) > 0){

                foreach ($filter->FilterValues as $filter_value){
                    array_push($value_array,$filter_value->value);
                }
            }
            $data['label']=$filter->label;
            $data['value']=$value_array;

            array_push($data_array,$data);
        }

        if ($session->metafield_id == null) {
            $shop_metafield = $client->post('/metafields.json', [
                "metafield" => array(
                    "key" => 'filters',
                    "value" => json_encode($data_array),
                    "type" => "json_string",
                    "namespace" => "Marionmaakt"
                )
            ]);

        } else {
            $shop_metafield = $client->put('/metafields/' . $session->metafield_id . '.json', [
                "metafield" => [
                    "value" => json_encode($data_array)
                ]
            ]);

        }

        $response = $shop_metafield->getDecodedBody();
        if (isset($response) && !isset($response['errors'])) {
            $session->metafield_id = $response['metafield']['id'];
            $session->save();
        }


    }
}
