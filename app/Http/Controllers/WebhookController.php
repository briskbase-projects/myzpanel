<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\WooCommerceStockService;

class WebhookController extends Controller
{
    protected $wooCommerceStockService;

    public function __construct(WooCommerceStockService $wooCommerceStockService)
    {
        $this->wooCommerceStockService = $wooCommerceStockService;
    }

    public function handleWooCommerceWebhook(Request $request)
    {

        $secret = env('WC_WEBHOOK_SECRET');
        $signature = $request->header('X-WC-Webhook-Signature');
        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        if ($signature !== $expectedSignature) {
            Log::warning('WooCommerce Webhook verification failed.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->all();
        $eventType = $request->header('X-WC-Webhook-Topic');

        if (!isset($data['id'], $data['status'], $data['line_items'])) {
            Log::warning("Invalid WooCommerce webhook payload.");
            return response()->json(['error' => 'Invalid Payload'], 400);
        }

        $orderId = $data['id'];
        $orderStatus = $data['status'];
        $lineItems = $data['line_items'];

        $existingRecord = DB::table('woocommerce_orders')->where('order_id', $orderId)->first();


        if ($eventType === 'order.created') {
            if (!$existingRecord) {
                DB::table('woocommerce_orders')->insert([
                    'order_id' => $orderId,
                    'status' => 'created'
                ]);
            }
        } elseif ($eventType === 'order.updated') {

            if ($orderStatus === 'processed') {
                if (!$existingRecord || $existingRecord->status !== 'processed') {
                    $this->wooCommerceStockService->handleWebhookStockUpdate($lineItems, 'decrease');
                    DB::table('woocommerce_orders')->updateOrInsert(
                        ['order_id' => $orderId],
                        ['status' => 'processed']
                    );
                }
            } elseif ($orderStatus === 'cancelled') {
                if (!$existingRecord || $existingRecord->status === 'created') {

                    DB::table('woocommerce_orders')->updateOrInsert(
                        ['order_id' => $orderId],
                        ['status' => 'cancelled']
                    );
                } elseif ($existingRecord->status === 'processed') {
                    $this->wooCommerceStockService->handleWebhookStockUpdate($lineItems, 'increase');
                    DB::table('woocommerce_orders')->where('order_id', $orderId)->update(['status' => 'cancelled']);
                }
            } elseif ($orderStatus === 'refunded') {
                if ($existingRecord && $existingRecord->status !== 'refunded') {
                    $this->wooCommerceStockService->handleWebhookStockUpdate($lineItems, 'increase');
                    DB::table('woocommerce_orders')->where('order_id', $orderId)->update(['status' => 'refunded']);
                }
            }
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    public function testWoocommerce()
    {
        dd($this->wooCommerceStockService->addWooCommerceStock('test-variation', 10));
    }
}

// $this->wooCommerceStockService->handleWebhookStockUpdate($request->all());
