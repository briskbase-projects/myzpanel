<?php

namespace App\Console\Commands;

use App\Http\Traits\ZalandoAPI;
use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductError;
use App\Models\Status;
use Session;
class CheckError extends Command
{
    use ZalandoAPI;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:error';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Error Checking';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $token = $this->getToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.merchants.zalando.com/graphql",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS =>"{\"query\":\"{\\r\\n  psr {\\r\\n    product_models(\\r\\n      input:\\r\\n      { merchant_ids: [\\\"".env('ZALANDO_MERCHANT_ID')."\\\"]\\r\\n      , status_clusters: [REJECTED]\\r\\n      , season_codes: []\\r\\n      , brand_codes: []\\r\\n      , country_codes: []\\r\\n      , limit: 100\\r\\n      , search_value: \\\"\\\"\\r\\n      }) {\\r\\n      items {\\r\\n        product_configs {\\r\\n          product_simples {\\r\\n            ean\\r\\n            status { status_detail_code }\\r\\n          }\\r\\n        }\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}\",\"variables\":{}}",
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/json"
        ),
      ));

        $response = curl_exec($curl);
        $response = json_decode($response);
      
        foreach ($response->data->psr->product_models->items as $key => $item) {
            foreach ($item->product_configs as $key => $pConfig) {
                foreach ($pConfig->product_simples as $key => $ps) {
                    $products = Product::all();
                    foreach ($products as $key => $p) {
                        if(!empty($p->zalando_sizes)){
                            $sizes = $p->zalando_sizes;
                        }else{
                            $sizes = $p->variants;
                        }

                        foreach ($sizes as $key => $v) {
                            $check = !empty($v['ean'])?$v['ean']:$v['id'];
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
         $this->info('Error Checked');
    }
}
