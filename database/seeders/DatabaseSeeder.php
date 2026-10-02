<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CustomerSeeder::class,
            OrderSeeder::class,
            RefundRequestSeeder::class,
        ]);

        $customerCount = Customer::count();
        $orderCount = Order::count();

        if ($customerCount < 15) {
            throw new RuntimeException("Assertion failed: Expected at least 15 customers, found {$customerCount}.");
        }

        if ($orderCount < 15) {
            throw new RuntimeException("Assertion failed: Expected at least 15 orders, found {$orderCount}.");
        }

        $this->command->info("Seeding verified successfully: {$customerCount} customers and {$orderCount} orders seeded.");
    }
}
