<?php

namespace App\Http\Controllers;

use App\Http\Traits\ZalandoAPI;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductError;
use Session;
use \Validator;
use Carbon\Carbon;

class ProductController extends Controller
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

  public $filePath = 'public/uploads/';
  public function editProduct($id)
  {
    ini_set('max_execution_time', '300');
    $data['detail'] = Product::where('id', $id)->first();
    $data['variants'] = Product::where("parent_id", $id)->get();
    $data['sessions'] = $this->getSeasonCode();
    $data['materials'] = $this->getMaterials();
    $data['brand_codes'] = $this->getBrandCode();
    $data['target_genders'] = $this->getTargetGender();
    $data['target_age_groups'] = $this->getTargetAgeGroup();
    $data['color_code'] = $this->getColorCode();
    $data['outlines'] = $this->getOutlines();
    $data['size_group'] = $this->getSizeGroup();
    return view('edit-product', $data);
  }
  public function addProduct()
  {
    ini_set('max_execution_time', '300');
    $data['variants'] = [];
    $data['sessions'] = $this->getSeasonCode();
    $data['materials'] = $this->getMaterials();
    $data['brand_codes'] = $this->getBrandCode();
    $data['target_genders'] = $this->getTargetGender();
    $data['target_age_groups'] = $this->getTargetAgeGroup();
    $data['color_code'] = $this->getColorCode();
    $data['size_group'] = $this->getSizeGroup();
    $data['outlines'] = $this->getOutlines();
    return view('edit-product', $data);
  }

  public function ReSubmit($id, Request $r)
  {

    ini_set('max_execution_time', '300');
    $token = $this->getToken();
    $products = Product::where("id", $id)->orWhere("parent_id", $id)->get();

    $responseTxt = "";
    foreach ($products as $p) {
      $already = false;
      foreach ($p->zalando_sizes as $key => $v) {
        $already = $this->checkProductExists($v);
      }

      if ($already) {
        $response = $this->editProductFieldData($p, $token);
      } else {
        $response = $this->productSumission($p, $token);
      }

      $failed = $this->updateProductStock($p, $response, $already);

      if (count($failed)) {
        $responseTxt .= "Stock not pushed for the following products" . implode(", ", $failed);
        continue;
      }
    }
    dd($response);
    if (empty($responseTxt)) {
      return redirect()->to('products?message=Product has been pushed successfully');
    }
    return redirect()->to('products?errorMessage=' . $responseTxt);
  }

  private function updateProductStock($p, $response, $isUpdate)
  {
    $failed = [];
    if (!isset($response->code) && empty($response->code)) {
      if ($isUpdate) {
        $p->imported = 1;
        $p->save();
        ProductError::where('shopify_products_id', $p->id)->delete();

        $stock = array();
        if (!empty($p->zalando_sizes)) {
          foreach ($p->zalando_sizes as $key => $v) {
            $ean = $v['ean'];
            foreach (config('channels') as $cid => $cdata) {
              if ($cdata['status'] === true) {
                $stock[] = '{
                      "ean": "' . $ean . '",
                      "sales_channel_id": "' . $cdata['id'] . '",
                      "quantity": ' . ($v['quantity_' . $cdata['country']] ?? 0) . '
                    }';
              }
            }
          }
        }

        $response = $this->postProductStock($stock);

        if (isset($response->results) && count($response->results)) {
        } else {
          $failed[] = $p->id;
        }
      } else {
        $products = Product::where("id", $p->id)->orWhere("parent_id", $p->id)->get();
        foreach ($products as $p) {
          $p->imported = 1;
          $p->save();
          ProductError::where('shopify_products_id', $p->id)->delete();

          $stock = array();
          if (!empty($p->zalando_sizes)) {
            foreach ($p->zalando_sizes as $key => $v) {
              $ean = $v['ean'];
              foreach (config('channels') as $cid => $cdata) {
                if ($cdata['status'] === true) {
                  $stock[] = '{
                        "ean": "' . $ean . '",
                        "sales_channel_id": "' . $cdata['id'] . '",
                        "quantity": ' . ($v['quantity_' . $cdata['country']] ?? 0) . '
                      }';
                }
              }
            }
          }

          $response = $this->postProductStock($stock);

          if (isset($response->results) && count($response->results)) {
          } else {
            $failed[] = $p->id;
          }
        }
      }
    }
    return $failed;
  }

  public function saveProduct(Request $r)
  {
    ini_set('max_execution_time', '300');

    $parent_id = null;
    $parent_model_id = null;

    $channels = config('channels');
    $filteredChannels = collect($channels)->filter(function ($channel) {
      return $channel['status'];
    });
    $countries = $filteredChannels->unique('country');

    if (is_array($r->merchant_product_config_id)) {
      for ($ind = 0; $ind < count($r->merchant_product_config_id); $ind++) {
        $size = array();

        foreach ($r->size_ean[$ind] as $key => $ean) {

          $size[$key]['ean'] = $ean;
          $size[$key]['sku'] = $r->size_sku[$ind][$key];
          $size[$key]['title'] = $r->size_title[$ind][$key];
          $size[$key]['promotionPrice'] = $r->promotionPrice[$ind][$key]??0;

          foreach ($countries as $channel) {
            $size[$key][$channel['country'] . '_price'] = $r->{$channel['country'] . '_price'}[$ind][$key];
            $size[$key]['quantity_' . $channel['country']] = $r->{'quantity_' . $channel['country']}[$ind][$key];

            if (!empty($r->{'pro_' . $channel['country'] . '_price'}[$ind][$key])) {
              $size[$key]['pro_' . $channel['country'] . '_price'] = $r->{'pro_' . $channel['country'] . '_price'}[$ind][$key];
            }
          }

          // New Logic for promotion
          if (!empty($r->start_date[$ind][$key])) {
            $startDateInput = trim($r->start_date[$ind][$key]);
            $startDate = Carbon::parse(str_replace('/', '-', $startDateInput));


            if ($startDate->isToday() || $startDate->isPast()) {
              $startDate = Carbon::now()->addMinutes(125)->format('Y-m-d\TH:i:s.00\Z');
            } else {
              $startDate = $startDate->addMinutes(125)->format('Y-m-d\T00:00:00.00\Z');
            }

            $size[$key]['start_date'] = $startDate;
          }

          if (!empty($r->end_date[$ind][$key])) {
            $endDateInput = trim($r->end_date[$ind][$key]);
            $endDate = Carbon::parse(str_replace('/', '-', $endDateInput));

            $endDate = $endDate->startOfDay()->format('Y-m-d\T00:00:00.00\Z');
            $size[$key]['end_date'] = $endDate;
          }
        }

        $material = array();
        foreach ($r->material_code[$ind] as $key => $code) {
          $material[$key]['material_code'] = $code;
          $material[$key]['material_percentage'] = $r->material_percentage[$ind][$key];
        }

        $images = array();
        if (!empty($r->image_sort[$ind])) {

          foreach ($r->image_sort[$ind] as $key => $image_sort) {

            if (isset($r->image[$ind][$key]) && !empty($r->image[$ind][$key])) {
              $image = $r->image[$ind][$key];
              $filename = $image->getClientOriginalName();
              $newimageFilename = $fileNewName = $this->Guid() . "-" . preg_replace('/\s/', '-', $filename);

              $moved = $image->move($this->filePath, $fileNewName);

              $images[$key]['media_path'] = url('public/uploads/') . '/' . $newimageFilename;
              $images[$key]['media_sort_key'] = $image_sort;
            } elseif (isset($r->old_image[$ind][$key]) && !empty($r->old_image[$ind][$key])) {
              $images[$key]['media_path'] = $r->old_image[$ind][$key];
              $images[$key]['media_sort_key'] = $image_sort;
            }
          }
        }

        if (isset($r->id[$ind]) && !is_null($r->id[$ind])) {
          $product = Product::where('id', $r->id[$ind])->first();
        } else {
          $product = new Product;
        }
        $product->parent_id = $parent_id;
        $product->merchant_product_model_id = !is_null($parent_model_id) ? $parent_model_id : (!empty($r->merchant_product_model_id) ? $r->merchant_product_model_id : rand(00000000000, 99999999999));
        $product->merchant_product_config_id = !empty($r->merchant_product_config_id[$ind]) ? $r->merchant_product_config_id[$ind] : rand(00000000000, 99999999999);
        $product->size_group = $r->size_group;
        $product->outline = $r->outline;
        $product->title = $r->title;
        $product->tags = $r->tags;
        $product->season_code = $r->session_code[$ind];
        $product->brand_code = $r->brand_code;
        $product->color_code = $r->color_code[$ind];

        $product->target_genders = $r->target_genders;
        $product->target_age_groups = $r->target_age_groups;

        /*$product->ean = $r->ean;*/
        $product->supplier_color = $r->supplier_color[$ind];
        $product->zalando_sizes = $size;

        if (!empty($images) && count($images) > 0) {
          $product->zalando_images = $images;
        }
        $product->material = $material;
        $product->body_html = $r->body[$ind];


        $saved = $product->save();
        if ($ind == 0) {
          $parent_id = $product->id;
          $parent_model_id = $product->merchant_product_model_id;
        }
      }
    }
    if ($saved) {
      if ($r->push == 1) {
        return redirect()->to('/resubmit-product/' . $parent_id . '?edit=yes');
      } else {
        return redirect()->to('products?message=Product Saved Successfully');
      }
    } else {
      return redirect()->back();
    }
  }

  function Guid()
  {
    $s = strtoupper(md5(uniqid(rand(), true)));
    $guidText =
      substr($s, 0, 8) . '-' .
      substr($s, 8, 4) . '-' .
      substr($s, 12, 4) . '-' .
      substr($s, 16, 4) . '-' .
      substr($s, 20);
    return $guidText;
  }
  public function productSumission($p, $token)
  {

    $fields = '{
                "outline":"' . $p->outline . '",
                "product_model":{
                  "merchant_product_model_id":"' . $p->merchant_product_model_id . '",
                  "product_model_attributes":{
                    "name": "' . $p->title . '",
                    "brand_code":"' . $p->brand_code . '",
                    "size_group":{
                      "size":"' . $p->size_group . '"
                    },
                    "target_genders":[';
    if (!empty($p->target_genders)) {
      foreach ($p->target_genders as $key => $gender) {
        $fields .= '"' . $gender . '",';
      }
      $fields = rtrim($fields, ',');
    }

    $fields .= '],
                                            "target_age_groups":[';
    if (!empty($p->target_age_groups)) {
      foreach ($p->target_age_groups as $key => $age) {
        $fields .= '"' . $age . '",';
      }
      $fields = rtrim($fields, ',');
    }
    $jsonArr = [];
    $jsonArr[] = $this->generateProductConfig($p);
    foreach ($p->product_variants as $vari) {
      $jsonArr[] = $this->generateProductConfig($vari);
    }
    $json = implode(",", $jsonArr);
    $fields .= ']
                  },
                  "product_configs":[
                    ' . $json . '
                  ]
                }
              }';

    $checkresponse = $this->postZalandoProduct($fields);

    if (isset($checkresponse->code) && !empty($checkresponse->code)) {
      $error = new ProductError;
      $error->ean = '*';
      $error->status_code = $checkresponse->code;
      $error->message = $checkresponse->detail;
      $error->detail = $checkresponse->detail;
      $error->type = $checkresponse->title;
      $error->shopify_products_id = $p->id;
      $error->save();
    }

    return $checkresponse;
  }

  public function generateProductConfig($p)
  {
    $body_html = str_replace("\r", "", $p->body_html);
    $body_html = str_replace("\n", "", $body_html);
    $body_html = str_replace("\t", "", $body_html);
    $body_html = str_replace('"', "", $body_html);
    $body_html = $this->sanitize_output($body_html);

    $json = '{
      "merchant_product_config_id":"' . $p->merchant_product_config_id . '",
      "product_config_attributes":{
        "color_code.primary":"' . $p->color_code . '",
        "description":{
          "en": "<div>' . $body_html . '</div>",
          "de": "<div>' . $body_html . '</div>"
        },
        "material.upper_material_clothing":[';
    if (!empty($p->material)) {

      foreach ($p->material as $key => $m) {
        $json .= ' {
                  "material_code":"' . $m["material_code"] . '",
                  "material_percentage":' . ($m["material_percentage"] ?? 0) . '
                  },';
      }
      $json = rtrim($json, ',');
    }
    $json .= '    ],
              "media":[
              ';
    if (!empty($p->zalando_images)) {

      foreach ($p->zalando_images as $key => $i) {
        $json .= '  {
                    "media_path":"' . $i["media_path"] . '?version=' . rand(111, 999) . '",
                    "media_sort_key": ' . $i["media_sort_key"] . '
                  },';
      }
      $json = rtrim($json, ',');
    } else {
      foreach ($p->images as $key => $i) {
        $key = $key + 1;
        $json .= '  {
                  "media_path":"' . $i["src"] . '?version=' . rand(111, 999) . '",
                  "media_sort_key": ' . $key . '
                },';
      }
      $json = rtrim($json, ',');
    }
    $json .= '],';
    $json .= '
        "season_code":"' . (!empty($p->season_code) ? $p->season_code : $season_code) . '",
        "supplier_color":"' . (!empty($p->supplier_color) ? $p->supplier_color : $supplier_color) . '"
      },
      "product_simples":[
      ';
    if (!empty($p->zalando_sizes)) {

      foreach ($p->zalando_sizes as $key => $v) {
        $sku = $v['sku'];
        $ean = $v['ean'];
        $title = $v['title'];
        $json .= '{
            "merchant_product_simple_id":"' . $sku . '",
            "product_simple_attributes":{
              "ean":"' . $ean . '",
              "size_codes":{
                "size":"' . $title . '"
              }
            }
          },';
      }
    }
    $json = rtrim($json, ',');
    $json .= '
        
      ]
    }';
    return $json;
  }
  public function sanitize_output($buffer)
  {

    $search = array(
      '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
      '/[^\S ]+\</s',     // strip whitespaces before tags, except space
      '/(\s)+/s',         // shorten multiple whitespace sequences
      '/<!--(.|\s)*?-->/' // Remove HTML comments
    );

    $replace = array(
      '>',
      '<',
      '\\1',
      ''
    );

    $buffer = preg_replace($search, $replace, $buffer);

    return $buffer;
  }
  public function editProductFieldData($p, $token)
  {

    $sizes = $p->zalando_sizes;
    foreach ($sizes as $key => $v) {
      $sku = $v['sku'];
      $fields = '{
        "merchant_product_simple_id": "' . $sku . '",
        "merchant_product_config_id": "' . $p->merchant_product_config_id . '",
        "merchant_product_model_id": "' . $p->merchant_product_model_id . '"
      }';

      $ean = !empty($v['ean']) ? $v['ean'] : (!empty($v["id"]) ? str_replace('.', '', round($v["id"])) : '');

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/products/identifiers/" . $ean,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $fields,

        CURLOPT_HTTPHEADER => array(
          "Authorization: Bearer " . $token,
          "Content-Type: application/json"
        ),
      ));
    }

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
  }
  public function deleteImage($slug)
  {

    if (file_exists('public/uploads/' . $slug)) {
      unlink('public/uploads/' . $slug);
    }
    return true;
  }
  public function deleteProduct($id)
  {
    $product = Product::where('id', $id)->first();
    if (!is_null($product) && !empty($product->zalando_images)) {
      foreach ($product->zalando_images as $i) {
        $path = str_replace(url('public/uploads'), '', $i['media_path']);
        if (file_exists('public/uploads/' . $path)) {
          unlink('public/uploads/' . $path);
        }
      }
    }

    ProductError::where('shopify_products_id', $id)->delete();
    $deleted = $product->delete();

    return redirect()->to('products?message=Product Delete Successfully');
  }
  public function pushPrices($id)
  {
    $products = Product::where("id", $id)->orWhere("parent_id", $id)->get();
    $eans = array();
    $data = array();
    foreach ($products as $p) {
      if (!empty($p->zalando_sizes)) {
        foreach ($p->zalando_sizes as $key => $v) {
          $ean = $v['ean'];
          $eans[] = $ean;

          foreach (config('channels') as $cdata) {
            if ($cdata['status'] === true) { // Ensure 'status' is properly checked
              $priceKey = $cdata['country'] . "_price";
              $promoKey = "pro_" . $cdata['country'] . "_price";

              if (!empty($v[$priceKey])) {
                $data[] = $this->getPriceJson(
                  $ean,
                  $v[$priceKey],
                  $v[$promoKey] ?? '',
                  $v["start_date"] ?? '',
                  $v["end_date"] ?? '',
                  $cdata['currency'],
                  $cdata['id']
                );
              }
            }
          }
        }
      }
    }

    $fields = array("product_prices" => $data);

    // dd(json_encode($fields));
    $isSave = $this->postPrice(json_encode($fields), $p);
    // dd($response);
    if ($isSave === true) {
      $response = $this->postPriceReporting($eans);
      //   dd($response);
      if (isset($response->items) &&  count($response->items)) {
        //   dd($response->items[0]);
        ProductError::where('shopify_products_id', $p->id)->delete();
        foreach ($response->items as $item) {
          // if($item->sales_channel_id == '7ce94f55-7a4d-4416-95c1-bf34193a47e8'){
          // dd($item);
          // }
          //   $error = new ProductError;
          //   $error->ean = $item->ean;
          //   $error->status_code = "ZABLO_13";
          //   $error->message = "Price Rejected";
          //   $error->detail = isset($item->base_price->status_transitions[0]->messages[0])?$item->base_price->status_transitions[0]->messages[0]->message:"";
          //   $error->type = "Price Issue";
          //   $error->shopify_products_id = $p->id;
          //   $error->save();
        }
      }

      $p->imported = 4;
      $p->save();
      return redirect()->to('products?message=Prices  Pushed Successfully');
    } else {
      return redirect()->to('products?errorMessage=' . $isSave);
    }
  }

  public function getPriceJson($ean, $price, $proprice, $startdate, $end_date, $currency, $channel)
  {
    // Parse the startdate using Carbon
    $startDateTime = Carbon::parse($startdate);
    $currentDateTime = Carbon::now();

    // Check if the startdate is less than the current date and time plus 120 minutes
    if ($startDateTime->lessThan($currentDateTime->copy()->addMinutes(120))) {
      // Set startdate to the current date and time plus 125 minutes
      $startDateTime = $currentDateTime->addMinutes(125);
    }

    // Format the startdate to the required format
    $formattedStartDate = $startDateTime->format('Y-m-d\TH:i:s.00\Z');

    if (empty($proprice)) {
      $data = [
        "ean" => $ean,
        "sales_channel_id" => $channel,
        "regular_price" => [
          "amount" => $price,
          "currency" => $currency
        ],
        "ignore_warnings" => false
      ];
    } else {
      $data = [
        "ean" => $ean,
        "sales_channel_id" => $channel,
        "regular_price" => [
          "amount" => $price,
          "currency" => $currency
        ],
        "scheduled_prices" => [
          "0" => [
            "regular_price" => [
              "amount" => $price,
              "currency" => $currency
            ],
            "promotional_price" => [
              "amount" => $proprice,
              "currency" => $currency
            ],
            "start_time" => $formattedStartDate,
            "end_time" => $end_date
          ]
        ],
        "ignore_warnings" => false
      ];
    }

    return $data;
  }

  public function postPrice($fields, $p)
  {
    $response = $this->postZalandoPrice($fields);
    // dd($response);
    if (isset($response->results)) {
      ProductError::where('shopify_products_id', $p->id)->delete();
      foreach ($response->results as $r) {
        if ($r->status != "ACCEPTED" && strpos($r->status, "Merchant is not active in Sales Channel SalesChannelId") === FALSE) {
          $error = new ProductError;
          $error->ean = $r->product_price->ean;
          $error->status_code = $r->code;
          $error->message = "Not Accepted";
          $error->detail = isset($r->product_price->scheduled_prices[0]) ? $r->product_price->scheduled_prices[0]->description : $r->description;
          $error->type = "Automatic";
          $error->shopify_products_id = $p->id;
          $error->save();
        }
      }
      return true;
    } else {

      return !is_null($response) ? $response->detail : "There is an Error";
    }
  }
}
