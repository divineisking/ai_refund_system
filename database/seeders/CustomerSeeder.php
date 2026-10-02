<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'customer_id' => 'CUST-1001',
                'name' => 'Alice Walker',
                'email' => 'alice.walker@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 5,
                'total_orders' => 14,
                'return_rate' => 0.07,
                'account_created_at' => '2025-01-15',
            ],
            [
                'customer_id' => 'CUST-1002',
                'name' => 'Brandon Vance',
                'email' => 'brandon.vance@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 12,
                'total_orders' => 8,
                'return_rate' => 0.00,
                'account_created_at' => '2025-03-20',
            ],
            [
                'customer_id' => 'CUST-1003',
                'name' => 'Catherine Chang',
                'email' => 'catherine.chang@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 8,
                'total_orders' => 32,
                'return_rate' => 0.06,
                'account_created_at' => '2024-06-10',
            ],
            [
                'customer_id' => 'CUST-1004',
                'name' => 'David Morales',
                'email' => 'david.morales@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 15,
                'total_orders' => 6,
                'return_rate' => 0.17,
                'account_created_at' => '2025-08-05',
            ],
            [
                'customer_id' => 'CUST-1005',
                'name' => 'Elena Rostova',
                'email' => 'elena.rostova@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 10,
                'total_orders' => 19,
                'return_rate' => 0.11,
                'account_created_at' => '2024-11-12',
            ],
            [
                'customer_id' => 'CUST-1006',
                'name' => 'Felix Sterling',
                'email' => 'felix.sterling@example.com',
                'risk_tier' => 'HIGH',
                'fraud_score' => 88,
                'total_orders' => 25,
                'return_rate' => 0.68,
                'account_created_at' => '2024-02-18',
            ],
            [
                'customer_id' => 'CUST-1007',
                'name' => 'Grace Hopper-Chen',
                'email' => 'grace.chen@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 4,
                'total_orders' => 11,
                'return_rate' => 0.09,
                'account_created_at' => '2025-05-14',
            ],
            [
                'customer_id' => 'CUST-1008',
                'name' => 'Hector Salazar',
                'email' => 'hector.salazar@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 18,
                'total_orders' => 5,
                'return_rate' => 0.20,
                'account_created_at' => '2025-07-22',
            ],
            [
                'customer_id' => 'CUST-1009',
                'name' => 'Isabelle Dubois',
                'email' => 'isabelle.dubois@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 7,
                'total_orders' => 9,
                'return_rate' => 0.11,
                'account_created_at' => '2025-04-03',
            ],
            [
                'customer_id' => 'CUST-1010',
                'name' => 'Jamal Washington',
                'email' => 'jamal.washington@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 14,
                'total_orders' => 15,
                'return_rate' => 0.13,
                'account_created_at' => '2024-10-30',
            ],
            [
                'customer_id' => 'CUST-1011',
                'name' => 'Kevin Patel',
                'email' => 'kevin.patel@example.com',
                'risk_tier' => 'MEDIUM',
                'fraud_score' => 45,
                'total_orders' => 4,
                'return_rate' => 0.25,
                'account_created_at' => '2025-09-01',
            ],
            [
                'customer_id' => 'CUST-1012',
                'name' => 'Laura Lin',
                'email' => 'laura.lin@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 2,
                'total_orders' => 18,
                'return_rate' => 0.06,
                'account_created_at' => '2024-12-01',
            ],
            [
                'customer_id' => 'CUST-1013',
                'name' => 'Marcus Brody',
                'email' => 'marcus.brody@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 0,
                'total_orders' => 42,
                'return_rate' => 0.00,
                'account_created_at' => '2023-11-15',
            ],
            [
                'customer_id' => 'CUST-1014',
                'name' => 'Nicole Kidman-Smyth',
                'email' => 'nicole.ks@example.com',
                'risk_tier' => 'LOW',
                'fraud_score' => 6,
                'total_orders' => 7,
                'return_rate' => 0.00,
                'account_created_at' => '2025-06-18',
            ],
            [
                'customer_id' => 'CUST-1015',
                'name' => 'Oscar Cruz',
                'email' => 'oscar.cruz@example.com',
                'risk_tier' => 'MEDIUM',
                'fraud_score' => 58,
                'total_orders' => 12,
                'return_rate' => 0.45,
                'account_created_at' => '2025-02-10',
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(
                ['customer_id' => $data['customer_id']],
                $data
            );
        }
    }
}
