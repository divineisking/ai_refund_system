<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now()->startOfDay();

        $orders = [
            [
                'order_id' => 'ORD-8001',
                'customer_id' => 'CUST-1001',
                'item_name' => 'Wireless Noise-Canceling Earbuds',
                'category' => 'Electronics',
                'price' => 79.99,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8001-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 4,
            ],
            [
                'order_id' => 'ORD-8002',
                'customer_id' => 'CUST-1002',
                'item_name' => 'End-of-Season Merino Wool Sweater',
                'category' => 'Apparel',
                'price' => 89.50,
                'is_final_sale' => true,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8002-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 10,
            ],
            [
                'order_id' => 'ORD-8003',
                'customer_id' => 'CUST-1003',
                'item_name' => 'UltraSharp 34" Curved OLED Monitor',
                'category' => 'Electronics',
                'price' => 1249.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8003-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 9,
            ],
            [
                'order_id' => 'ORD-8004',
                'customer_id' => 'CUST-1004',
                'item_name' => 'Pro Blender & Food Processor 1200W',
                'category' => 'Home & Kitchen',
                'price' => 139.99,
                'is_final_sale' => false,
                'is_damaged' => true,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8004-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 3,
            ],
            [
                'order_id' => 'ORD-8005',
                'customer_id' => 'CUST-1005',
                'item_name' => 'Waterproof Mountain Hiking Boots',
                'category' => 'Footwear',
                'price' => 165.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8005-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 48,
            ],
            [
                'order_id' => 'ORD-8006',
                'customer_id' => 'CUST-1006',
                'item_name' => 'Classic Leather Bomber Jacket',
                'category' => 'Apparel',
                'price' => 280.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8006-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 2,
            ],
            [
                'order_id' => 'ORD-8007',
                'customer_id' => 'CUST-1007',
                'item_name' => 'Handcrafted Italian Leather Handbag',
                'category' => 'Luxury',
                'price' => 850.00,
                'is_final_sale' => false,
                'is_damaged' => true,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8007-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 5,
            ],
            [
                'order_id' => 'ORD-8008',
                'customer_id' => 'CUST-1008',
                'item_name' => 'Artisan Ceramic Dinnerware Set',
                'category' => 'Home & Kitchen',
                'price' => 75.00,
                'is_final_sale' => true,
                'is_damaged' => true,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8008-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 6,
            ],
            [
                'order_id' => 'ORD-8009',
                'customer_id' => 'CUST-1009',
                'item_name' => 'Aromatherapy Essential Oil Diffuser',
                'category' => 'Home',
                'price' => 42.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8009-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 30,
            ],
            [
                'order_id' => 'ORD-8010',
                'customer_id' => 'CUST-1010',
                'item_name' => 'Espresso Machine with Steamer',
                'category' => 'Appliances',
                'price' => 219.99,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8010-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 31,
            ],
            [
                'order_id' => 'ORD-8011',
                'customer_id' => 'CUST-1011',
                'item_name' => 'Smart Fitness Watch with GPS',
                'category' => 'Electronics',
                'price' => 199.99,
                'is_final_sale' => true,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8011-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 8,
            ],
            [
                'order_id' => 'ORD-8012',
                'customer_id' => 'CUST-1012',
                'item_name' => 'Acoustic Guitar & Hardcase Bundle',
                'category' => 'Musical Instruments',
                'price' => 500.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8012-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 6,
            ],
            [
                'order_id' => 'ORD-8013',
                'customer_id' => 'CUST-1013',
                'item_name' => 'Ergonomic Mesh Office Chair',
                'category' => 'Furniture',
                'price' => 349.50,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8013-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 13,
            ],
            [
                'order_id' => 'ORD-8014',
                'customer_id' => 'CUST-1014',
                'item_name' => 'Plush Cashmere Throw Blanket',
                'category' => 'Home',
                'price' => 185.00,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8014-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 53,
            ],
            [
                'order_id' => 'ORD-8015',
                'customer_id' => 'CUST-1015',
                'item_name' => 'Rugged IP67 Bluetooth Speaker',
                'category' => 'Electronics',
                'price' => 129.99,
                'is_final_sale' => false,
                'is_damaged' => false,
                'return_window_days' => 30,
                'tracking_number' => 'TRK-8015-US',
                'status' => 'DELIVERED',
                'days_elapsed' => 4,
            ],
        ];

        foreach ($orders as $data) {
            $days = $data['days_elapsed'];
            unset($data['days_elapsed']);

            $deliveredDate = (clone $now)->subDays($days);
            $orderDate = (clone $deliveredDate)->subDays(3);

            $data['delivered_date'] = $deliveredDate->format('Y-m-d');
            $data['order_date'] = $orderDate->format('Y-m-d');

            Order::updateOrCreate(
                ['order_id' => $data['order_id']],
                $data
            );
        }
    }
}
