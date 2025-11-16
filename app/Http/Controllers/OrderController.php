<?php

namespace App\Http\Controllers;

use Session;
use App\Models\Status;
use App\Models\Product;
use App\Models\ProductError;
use Illuminate\Http\Request;
use App\Http\Traits\ZalandoAPI;
use Illuminate\Support\Facades\Response;
use App\Services\WooCommerceStockService;

class OrderController extends Controller
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
    public function index(Request $request)
    {
        $maindata = array();
        $data = array();

        $filters = [
            "order_number" => !empty($request->get("order_number")) ? $request->order_number : "",
            "order_status" => !empty($request->get("order_status")) ? $request->order_status : "",
            "sales_channel_id" => !empty($request->get("sales_channel_id")) ? $request->sales_channel_id : "",
            "created_after" => !empty($request->get("created_after")) ? $request->created_after . "T00:00:00Z" : "",
        ];

        $data = $this->getZalandoOrders($filters);

        $maindata['orders'] = $data->data ?? [];
        $maindata['included'] = $data->included ?? [];
        $maindata['meta'] = $data->meta ?? [];
        $maindata['links'] = $data->links ?? [];
        return view('orders.index', $maindata);
    }
    public function detail($id)
    {
        $maindata = array();

        $response = $this->getZOrderDetail($id);

        $sizes = Product::pluck("zalando_sizes");

        $maindata['detail'] = $response->data;
        $maindata['sizes'] = $sizes;
        $maindata['included'] = $response->included;
        return view('orders.detail', $maindata);
    }


    public function printDeliveryNote($lang, $id)
    {
        $maindata = array();

        $response = $this->getZOrderDetail($id);
        $sizes = Product::pluck("zalando_sizes");

        if (is_null($response)) {
            return response("Unable to get zalando order detail.Please try again later.", 401);
        }

        $maindata['detail'] = $response->data;
        $country = explode("-", $maindata['detail']->attributes->locale)[1];
        $maindata['country'] = $country;

        $maindata['detail'] = $response->data;
        $maindata['sizes'] = $sizes;
        $maindata['included'] = $response->included;

        $html = view('pdf.' . $lang . '.delivery_note', $maindata)->render();


        $pdf = \App::make('dompdf.wrapper');
        $invPDF = $pdf->loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
        return $pdf->download('delivery_note-' . $maindata['detail']->attributes->order_number . '.pdf');
    }


    // public function printBill($lang, $id)
    // {
    //   $maindata = array();

    //   $response = $this->getZOrderDetail($id);
    //   $sizes = Product::pluck("zalando_sizes");

    //   $maindata['detail'] = $response->data;
    //   $maindata['sizes'] = $sizes;
    //   $maindata['included'] = $response->included;

    //   $html = view('pdf.'.$lang.'.bill', $maindata)->render();
    //   $pdf = \App::make('dompdf.wrapper');
    //   $invPDF = $pdf->loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
    //   return $pdf->download('bill-'.$maindata['detail']->attributes->order_number.'.pdf');
    // }


    public function printReturnSlip($lang, $id)
    {
        $maindata = array();

        $response = $this->getZOrderDetail($id);
        $sizes = Product::pluck("zalando_sizes");

        if (is_null($response)) {
            return response("Unable to get zalando order detail.Please try again later.", 401);
        }

        $maindata['detail'] = $response->data;
        $maindata['sizes'] = $sizes;
        $maindata['included'] = $response->included;

        $html = view('pdf.' . $lang . '.return_slip', $maindata)->render();
        $pdf = \App::make('dompdf.wrapper');
        $invPDF = $pdf->loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
        return $pdf->download('return_slip-' . $maindata['detail']->attributes->order_number . '.pdf');
    }


    public function printInvoice($lang, $id)
    {
        $maindata = array();

        $response = $this->getZOrderDetail($id);

        $sizes = Product::pluck("zalando_sizes");
        if (is_null($response)) {
            return response("Unable to get zalando order detail.Please try again later.", 401);
        }
        $maindata['detail'] = $response->data;
        $country = explode("-", $maindata['detail']->attributes->locale)[1];
        $maindata['country'] = $country;
        $maindata['sizes'] = $sizes;
        $maindata['included'] = $response->included;
        if (isset(config("taxes")[$country])) {
            $maindata['tax'] = config("taxes")[$country] / 100;
        } else {
            $maindata['tax'] = 0.21;
        }

        $html = view('pdf.' . $lang . '.bill', $maindata)->render();

        $pdf = \App::make('dompdf.wrapper');
        $invPDF = $pdf->loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
        return $pdf->download('bill-' . $lang . '-' . $maindata['detail']->attributes->order_number . '.pdf');
    }

    public function returnFlyer($lang)
    {
        $pdfPath = resource_path('views/pdf/' . $lang . '/return-flyer.pdf');

        return response()->file($pdfPath, ['Content-Type' => 'application/pdf']);
    }

    public function addTracking($id, Request $request)
    {
        $response = $this->addTrackingZdirect($id, $request->tracking_id, $request->return_tracking_id);
        if (isset($response->errors)) {

            Session::flash('success-msg', $response->errors[0]->detail);
            return redirect()->back();
        }
        Session::flash('success-msg', "Tracking updated successfull");
        return redirect()->back();
    }

    public function updateLineStatus($orderId, $itemId, $lineId, $status)
    {
        $response = $this->updateLineStatusZdirect($orderId, $itemId, $lineId, $status);

        if (isset($response->errors)) {
            Session::flash('success-msg', $response->errors[0]->detail);
            return redirect()->back();
        }
        if ($status === 'shipped') {
            Session::flash('success-msg', "Status updated successfully");
        }

        $order = $response = $this->getZOrderDetail($orderId);
        $sku = $this->getSkuFromOrderID($order->included, $itemId);

        // Retrieve product with matching SKU
        $product = \DB::table('shopify_products')->whereJsonContains('zalando_sizes', [['sku' => $sku]])->first();
        if ($product) {
            $zalandoSizes = json_decode($product->zalando_sizes, true);
            foreach ($zalandoSizes as &$size) {
                if (strtolower($size['sku']) === strtolower($sku)) {
                    if ($status === 'shipped') {
                    } elseif ($status === 'returned') {
                        $wooCommerceService = app(WooCommerceStockService::class);
                        $wooCommerceService->addWooCommerceStock($sku, 1);
                        foreach (config('channels') as $cid => $cdata) {
                            $size['quantity_' . $cdata['country']] += 1;
                        }
                    } elseif ($status === 'cancelled') {
                        $wooCommerceService = app(WooCommerceStockService::class);
                        $wooCommerceService->addWooCommerceStock($sku, 1);
                        foreach (config('channels') as $cid => $cdata) {
                            $size['quantity_' . $cdata['country']] += 1;
                        }
                    }
                    break;
                }
            }

            // Update the product's zalando_sizes in the database
            \DB::table('shopify_products')
                ->where('id', $product->id)
                ->update(['zalando_sizes' => json_encode($zalandoSizes)]);

            // Prepare stock data to push to Zalando
            $stock = [];
            foreach ($zalandoSizes as $size) {
                foreach (config('channels') as $cid => $cdata) {
                    if ($cdata['status'] === true) {
                        $stock[] = [
                            'ean' => $size['ean'],
                            'sales_channel_id' => $cdata['id'],
                            'quantity' => $size['quantity_' . $cdata['country']] ?? 0,
                        ];
                    }
                }
            }

            // Push stock to Zalando
            $this->postProductStock($stock);

            Session::flash('success-msg', "Status updated successfully");
        } else {
            Session::flash('success-msg', "Product with SKU {$sku} not found");
        }

        return redirect()->back();
    }

    public function getSkuFromOrderID($items, $itemId)
    {
        $sku = "";
        foreach ($items as $item) {
            if ($item->type == "OrderItem" && $item->attributes->order_item_id == $itemId) {
                $sku = $item->attributes->external_id;
            }
        }
        return $sku;
    }


    //   public function updateLineStatus($orderId, $itemId, $lineId, $status)
    //   {
    //       $response = $this->updateLineStatusZdirect($orderId, $itemId, $lineId, $status);


    //       if(isset($response->errors)){

    //           Session::flash('success-msg', $response->errors[0]->detail);
    //             return redirect()->back();
    //       }
    //       Session::flash('success-msg', "Status updated successfull");
    //       return redirect()->back();
    //   }

}
