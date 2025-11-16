<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

trait ZalandoAPI
{
  public function getToken()
  {
    if (!Session::has('LAST_ACTIVITY') || (time() - Session::get('LAST_ACTIVITY') > 6200)) {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => env('ZALANDO_URL') . "/auth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "scope=access_token_only&grant_type=client_credentials",
        CURLOPT_HTTPHEADER => array(
          "Authorization: Basic " . base64_encode(env("ZALANDO_CLIENT_ID") . ":" . env("ZALANDO_CLIENT_SECRET")),
          "Content-Type: application/x-www-form-urlencoded"
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      $response = (array)json_decode($response);
      if (!empty($response["access_token"])) {
        $token = $response["access_token"];
        Session::put('token', $token);
        Session::put('LAST_ACTIVITY', time());
        return  $token;
      }
    }

    $token = Session::get('token');
    return $token;
  }

  public function getBPID()
  {
    $token = $this->getToken();

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/auth/me",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);

    $this->envUpdate("ZALANDO_MERCHANT_ID", $response->bpids[0]);
  }

  public function envUpdate($key, $value)
  {
    file_put_contents(app()->environmentFilePath(), str_replace(
      $key . '=' . env($key),
      $key . '=' . $value,
      file_get_contents(app()->environmentFilePath())
    ));

    \Artisan::call("config:clear");
  }

  public function getSeasonCode()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/season_code/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);
    return $response;
  }

  public function getOutlines()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/outlines?limit=500",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);

    return $response;
  }

  public function getMaterials()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/material_code/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function getSizeGroup()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/size/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function getBrandCode()
  {
    $token = $this->getToken();
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/brand_code/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);
    return $response;
  }

  public function getTargetGender()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/target_genders/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function getTargetAgeGroup()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/target_age_groups/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function getColorCode()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/attribute-types/color_code/attributes",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function checkProductExists($v)
  {
    $token = $this->getToken();
    $curl = curl_init();
    $ean = !empty($v['ean']) ? $v['ean'] : (!empty($v['id']) ? $v['id'] : time());
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/products/identifiers/" . $ean,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    if (!empty($response->items) && count($response->items) > 0) {
      return true;
    } else {
      return false;
    }
  }

  public function getZalandoOrders($filters)
  {
    $token = $this->getToken();
    $curl = curl_init();
    $queryString = $this->buildQueryString($filters);
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env("ZALANDO_MERCHANT_ID") . "/orders?include=order_items,order_lines,order_transitions,order_lines.order_line_transitions,order_items.order_lines,order_items.order_lines.order_line_transitions&page[size]=100" . $queryString,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response);

    return $response;
  }

  public function getZalandoExportOrders()
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env("ZALANDO_MERCHANT_ID") . "/orders?order_status=approved&exported=false",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response);

    return $response;
  }

  public function exportZalandoOrder($orderId, $shopifyOrderId = "")
  {
    $orderData = [
      'data' => [
        'type' => 'Order',
        'id' => $orderId,
        'attributes' => [
          'merchant_order_id' => $shopifyOrderId,
        ],
      ],
    ];

    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/orders/" . $orderId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'PATCH',
      CURLOPT_POSTFIELDS => json_encode($orderData),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/vnd.api+json'
      ),
    ));

    $response = curl_exec($curl);

    // Check if the request was successful (204 status code)
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatus === 204) {
      // Order marked as exported
      return ["exported" =>  true];
    } else {
      Log::error("Export Data: " . json_encode($orderData));
      return $httpStatus;
    }
  }

  public function getZOrderDetail($id)
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env("ZALANDO_MERCHANT_ID") . "/orders/" . $id . "?include=order_items,order_lines",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response);
    return $response;
  }

  public function postProductStock($fields)
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/stocks",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => '{"items": [' . implode(",", $fields) . ']}',
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);
    return $response;
  }

  public function postZalandoPrice($fields)
  {
    $token = $this->getToken();
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/prices",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $fields,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    return $response;
  }

  public function postZalandoProduct($fields)
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/product-submissions",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $fields,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $checkresponse = json_decode($response);
    return $checkresponse;
  }

  public function postPriceReporting($eans = array())
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/price-attempts",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => '{
        "eans": [
          "' . implode("\",\"", $eans) . '"
        ],
        "start": "' . date("Y-m-d", strtotime("-1 day")) . 'T00:00:00.00Z",
        "end": "' . date("Y-m-d", strtotime("5 day")) . 'T00:00:00.00Z",
        "page_size": 200
      }',
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);
  }

  public function addTrackingZdirect($orderId, $tracking, $returnTracking)
  {
    $data = '{
          "data":{
            "type":"Order",
            "id":"' . $orderId . '",
            "attributes":{
              "tracking_number":"' . $tracking . '",
              "return_tracking_number":"' . $returnTracking . '"
            }
          }
        }';

    $token = $this->getToken();
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/orders/" . $orderId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PATCH",
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/vnd.api+json",
        "Accept: application/vnd.api+json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $checkresponse = json_decode($response);
    return $checkresponse;
  }

  public function updateLineStatusZdirect($orderId, $itemId, $lineId, $status)
  {
    $data = '{
          "data":{
            "id":"' . $lineId . '",
            "type":"OrderLine",
            "attributes":{
              "status":"' . $status . '"
            }
          }
        }';

    $token = $this->getToken();
    $curl = curl_init();
    curl_setopt_array($curl, array(

      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/orders/" . $orderId . '/items/' . $itemId . '/lines/' . $lineId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PATCH",
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/vnd.api+json",
        "Accept: application/vnd.api+json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $checkresponse = json_decode($response);
    return $checkresponse;
  }

  private function buildQueryString($filters)
  {
    // Filter out empty values
    $filteredFilters = array_filter($filters, function ($value) {
      return $value !== null && $value !== '';
    });

    // Build the query string
    $queryString = http_build_query($filteredFilters);

    return $queryString ? '&' . $queryString : '';
  }

  public function getCountryByChannelId($id)
  {
    $channels = config('channels');

    foreach ($channels as $key => $channel) {
      if ($channel['id'] === $id) {
        return $channel['country'];
      }
    }

    return null;
  }

  /**
   * Get transitions for an order line to extract cancellation/return reasons
   *
   * @param string $orderId
   * @param string $itemId
   * @param string $lineId
   * @return object|null Transitions response from Zalando API
   */
  public function getOrderLineTransitions($orderId, $itemId, $lineId)
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') .
                     "/orders/" . $orderId . '/items/' . $itemId . '/lines/' . $lineId . '/transitions',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response);
  }

  /**
   * Extract reason from transitions for a specific target status
   * Looks for the latest transition where "to" matches target status (returned/canceled/refunded)
   *
   * @param object $transitions Transitions response from API
   * @param string $targetStatus Status to look for (returned, canceled, refunded)
   * @return array ['reason_code' => string, 'reason_description' => string]
   */
  public function extractTransitionReason($transitions, $targetStatus)
  {
    $result = [
      'reason_code' => null,
      'reason_description' => ''
    ];

    // Check if transitions data exists
    if (!$transitions || !isset($transitions->data)) {
      return $result;
    }

    // Loop through transitions to find the target status transition
    // Transitions are usually ordered, but we'll check all to be safe
    foreach ($transitions->data as $transition) {
      // Check if this transition's "final_status" matches our target
      if (isset($transition->attributes->final_status) && $transition->attributes->final_status === $targetStatus) {
        // Extract reason if available (field is "transition_reason" not "reason")
        if (isset($transition->attributes->transition_reason)) {
          $reason = $transition->attributes->transition_reason;

          // Extract code and description
          $result['reason_code'] = $reason->code ?? null;
          $result['reason_description'] = $reason->description ?? '';

          // We found a match, return immediately
          // (If multiple transitions exist, this gets the first one found)
          return $result;
        }
      }
    }

    return $result;
  }

  /**
   * Get outline-specific details including required attributes
   *
   * @param string $outlineLabel The outline label code
   * @return object|null Outline details from Zalando API
   */
  public function getOutlineDetails($outlineLabel)
  {
    $token = $this->getToken();
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => env('ZALANDO_URL') . "/merchants/" . env('ZALANDO_MERCHANT_ID') . "/outlines/" . $outlineLabel,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response);
    return $response;
  }
}
