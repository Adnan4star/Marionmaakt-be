<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Filter;
use App\Models\FilterValue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shopify\Clients\Rest;

class FilterController extends Controller
{
    public function index(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        try {
            $filters = Filter::query();

            if (isset($request->status)) {
                if ($request->status == 1) {
                    $filters->where('status', 1);
                } elseif ($request->status == 2) {
                    $filters->where('status', 0);
                }
            }

            $filters = $filters->with('FilterValues')
                            ->where('shop_id', $shop->id)
                            ->orderBy('id', 'desc')
                            ->paginate(20);

            return response()->json($filters);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function SaveFilter(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        try {
            DB::beginTransaction();

            $filter = Filter::find($request->filter_id) ?? new Filter();
            if ($request->has('filter_id')) {
                FilterValue::where('filter_id', $filter->id)->delete();
            }

            $filter->fill([
                'label' => $request->label,
                'status' => $request->status,
                'shop_id' => $shop->id
            ]);
            $filter->save();

            if ($request->values) {
                $values = explode(',', $request->values);
                foreach ($values as $value) {
                    $filter->filterValues()->create(['value' => $value]); // Using relationship to save data in FilterValue
                }
            }

            $this->CreateUpdateMetafield($shop);
            DB::commit();  

            return response()->json([
                'success' => true,
                'message' => 'Filter Saved Successfully',
                'data' => $filter
            ]);
        } catch (\Exception $exception) {
            DB::rollback();  
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
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

    public function UpdateFilterStatus(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $filter = Filter::find($request->filter_id);
        if (!$filter) {
            return response()->json(['success' => false, 'message' => 'Filter not found'], 404);
        }

        try {
            $filter->status = $request->status;
            $filter->save();

            $this->CreateUpdateMetafield($shop);

            return response()->json([
                'success' => true,
                'message' => 'Filter status updated successfully',
                'data' => $filter
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function DeleteFilter(Request $request)
    {
        $shop = getShop($request->get('shopifySession'));
        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $filter = Filter::find($request->filter_id);
        if (!$filter) {
            return response()->json(['success' => false, 'message' => 'Filter not found'], 404);
        }

        try {
            DB::transaction(function () use ($filter, $shop) {
                FilterValue::where('filter_id', $filter->id)->delete();
                $filter->delete();
                $this->CreateUpdateMetafield($shop);
            });

            return response()->json([
                'success' => true,
                'message' => 'Filter deleted successfully'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }
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
