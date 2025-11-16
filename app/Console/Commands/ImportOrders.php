<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrder;
use App\Http\Traits\ZalandoAPI;
use Illuminate\Console\Command;

class ImportOrders extends Command
{
    use ZalandoAPI;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export orders from zalando and update stock';

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
        $orders = $this->getZalandoExportOrders();
        $orders = $orders->data ?? [];

        if (count($orders)) {
            foreach ($orders as $order) {
                $orderId = $order->id;
                ProcessOrder::dispatch($orderId);
            }
        }

        $this->info('Order Imported');
    }
}
