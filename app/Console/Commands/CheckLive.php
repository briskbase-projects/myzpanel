<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductError;
use App\Models\Status;
use Session;
class CheckLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Live Checking';

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
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.merchants.zalando.com/auth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "scope=access_token_only&grant_type=client_credentials",
            CURLOPT_HTTPHEADER => array(
              "Authorization: Basic ZmE1NGFmNWFhN2ZiMzEzMGJmMjdjMGI2Mjk2M2I3NDA6ZDdjZjg2NjItOWI1Mi00ZTIzLTk4MWYtYzdkOGM3YzEzZTQ3",
              "Content-Type: application/x-www-form-urlencoded"
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response);
        $token = '';
        if (!empty($response->access_token)) {
         $token = $response->access_token;
         Session::put('token',$token);
       }else{
         $token = Session::get('token',$token);
       }

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
          CURLOPT_POSTFIELDS =>"{\"query\":\"{\\r\\n  psr {\\r\\n    product_models(\\r\\n      input:\\r\\n      { merchant_ids: [\\\"ec3d58d9-d2bc-431d-afed-58dca6bff404\\\"]\\r\\n      , status_clusters: [LIVE]\\r\\n      , season_codes: []\\r\\n      , brand_codes: []\\r\\n      , country_codes: []\\r\\n      , limit: 100\\r\\n      , search_value: \\\"\\\"\\r\\n      }) {\\r\\n      items {\\r\\n        product_configs {\\r\\n          product_simples {\\r\\n            ean\\r\\n            status { status_detail_code status_cluster }\\r\\n          }\\r\\n        }\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}\",\"variables\":{}}",
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
                                $p->imported = 2;
                                $p->save();
                            }
                        }
                    }
                }
            }
        }
         $this->info('Live Checked');
    }
}
