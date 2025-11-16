<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductError;
use App\Models\Status;
use Illuminate\Http\Request;
use App\Http\Traits\ZalandoAPI;
use Session;

class HomeController extends Controller
{
    use ZalandoAPI;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        
        return view('home');
    }
    public function products()
    {
        if(!Session::has("update-env")){
            $this->getBPID();   
            Session::put('update-env', true);
        }
        $data['products'] = Product::with('errors')->whereNull("parent_id")->orderBy("id", "DESC")->paginate(20);
        
        return view('products',$data);
    }
    public function checkStatus()
    {
        $token = $this->getToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => env("ZALANDO_URL")."/graphql",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS =>"{\"query\":\"{\\r\\n  psr {\\r\\n    product_models(\\r\\n      input:\\r\\n      { merchant_ids: [\\\"".env("ZALANDO_MERCHANT_ID")."\\\"]\\r\\n      , status_clusters: [REJECTED]\\r\\n      , season_codes: []\\r\\n      , brand_codes: []\\r\\n      , country_codes: []\\r\\n      , limit: 100\\r\\n      , search_value: \\\"\\\"\\r\\n      }) {\\r\\n      items {\\r\\n        product_configs {\\r\\n          product_simples {\\r\\n            ean\\r\\n            status { status_detail_code }\\r\\n          }\\r\\n        }\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}\",\"variables\":{}}",
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/json"
        ),
      ));

        $response = curl_exec($curl);
        $response = json_decode($response);
        $products = Product::all();
        foreach ($response->data->psr->product_models->items as $key => $item) {
            foreach ($item->product_configs as $key => $pConfig) {
                foreach ($pConfig->product_simples as $key => $ps) {
                    
                    foreach ($products as $key => $p) {
                        if(!empty($p->zalando_sizes)){
                            $sizes = $p->zalando_sizes;
                        }else{
                            $sizes = $p->variants;
                        }

                        foreach ($sizes as $key => $v) {
                            $check =  "";
                            if(isset($v['ean']) && !empty($v['ean'])){
                                $check = $v['ean'];
                            }elseif(isset($v['id']) && !empty($v['id'])){
                                $check = $v['id'];
                            }
                            //$check = !empty($v['ean'])?$v['ean']:$v['id'];
                            if ($check == $ps->ean) {
                                $p->imported = 0;
                                $p->save();
                                ProductError::where('shopify_products_id',$p->id)->delete();
                                foreach ($ps->status as $key => $status) {
                                  if ($status->status_detail_code == "ZAPRO_05") {
                                    $p->imported = 3;
                                    $p->save();
                                    continue;
                                  }
                                    $statusDetail = Status::where('code',$status->status_detail_code)->first();
                                
                                    $error = new ProductError;
                                    $error->ean = $ps->ean;
                                    $error->status_code = $status->status_detail_code;
                                    $error->message = $statusDetail->short_description;
                                    $error->detail = $statusDetail->detailed_description;
                                    $error->type = $statusDetail->type;
                                    $error->shopify_products_id = $p->id;
                                    $error->save();
                                }
                            }
                        }
                    }
                }
            }
        }
       return redirect()->to('/home?message=Error Status Updated');
    }
     public function checkquantity()
    {
        $token = $this->getToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => env("ZALANDO_URL")."/zfs/item-quantity-snapshots/".env("ZALANDO_MERCHANT_ID"),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_POSTFIELDS =>"",
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/json"
        ),
      ));

        $response = curl_exec($curl);
        
        $response = json_decode($response);
      
        $products = Product::all();
        foreach ($response->item_quantity_snapshot->item_quantities as $key => $item) {
           foreach ($products as $key => $p) {
                        if(!empty($p->zalando_sizes)){
                            $sizes = $p->zalando_sizes;
                        }else{
                            $sizes = $p->variants;
                        }
                        $count = 0;
                        $updateSizes = array();
                        foreach ($sizes as $key => $v) {
                            $check =  "";
                            if(isset($v['ean']) && !empty($v['ean'])){
                                $check = $v['ean'];
                            }elseif(isset($v['id']) && !empty($v['id'])){
                                $check = $v['id'];
                            }
                             $updateSizes[$key] = $v;
                            //$check = !empty($v['ean'])?$v['ean']:$v['id'];
                            if ($check == $item->ean) {
                             
                              $updateSizes[$key]['quantity'] = $item->total_quantity;
                              
                            }
                        }

                         if(!empty($p->zalando_sizes)){
                             $p->zalando_sizes = $updateSizes;
                        }else{
                            $p->variants = $updateSizes;
                        }
                        $p->save();
                    }
        }
        
      
       return redirect()->to('/home?message=Stock Updated Successfully');
    }
    public function checkLive()
    {
        $token = $this->getToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => env("ZALANDO_URL")."/graphql",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS =>"{\"query\":\"{\\r\\n  psr {\\r\\n    product_models(\\r\\n      input:\\r\\n      { merchant_ids: [\\\"".env("ZALANDO_MERCHANT_ID")."\\\"]\\r\\n      , status_clusters: [LIVE]\\r\\n      , season_codes: []\\r\\n      , brand_codes: []\\r\\n      , country_codes: []\\r\\n      , limit: 100\\r\\n      , search_value: \\\"\\\"\\r\\n      }) {\\r\\n      items {\\r\\n        product_configs {\\r\\n          product_simples {\\r\\n            ean\\r\\n            status { status_detail_code status_cluster }\\r\\n          }\\r\\n        }\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}\",\"variables\":{}}",
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response);
        $products = Product::all();
        foreach ($response->data->psr->product_models->items as $key => $item) {
            foreach ($item->product_configs as $key => $pConfig) {
                foreach ($pConfig->product_simples as $key => $ps) {
                    
                    foreach ($products as $key => $p) {
                        if(!empty($p->zalando_sizes)){
                            $sizes = $p->zalando_sizes;
                        }else{
                            $sizes = $p->variants;
                        }

                        foreach ($sizes as $key => $v) {
                            $check =  "";
                            if(isset($v['ean']) && !empty($v['ean'])){
                                $check = $v['ean'];
                            }elseif(isset($v['id']) && !empty($v['id'])){
                                $check = $v['id'];
                            }
                            if ($check == $ps->ean) {
                                // if ($v->status_detail_code == "ZAPRO_05") {
                                //   $p->imported = 3;
                                //   $p->save();
                                //   continue;
                                // }
                                $p->imported = 2;
                                $p->save();
                            }
                        }
                    }
                }
            }
        }
       return redirect()->to('/home?message=Live Product Status Updated');
    }
}
