<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Department;
use App\Models\Loan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            [
                'name' => 'IT Department',
                'description' => 'Information Technology department responsible for all technical equipment',
                'location' => 'Floor 2, East Wing',
                'contact_email' => 'it@example.com',
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing department for promotional activities',
                'location' => 'Floor 3, West Wing',
                'contact_email' => 'marketing@example.com',
            ],
            [
                'name' => 'Engineering',
                'description' => 'Engineering team handling all physical products',
                'location' => 'Floor 1, North Wing',
                'contact_email' => 'engineering@example.com',
            ],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Create users (one for each department)
        $users = [
            [
                'name' => 'IT Admin',
                'email' => 'it@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'department_id' => 1,
            ],
            [
                'name' => 'Marketing Manager',
                'email' => 'marketing@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'department_id' => 2,
            ],
            [
                'name' => 'Engineering Lead',
                'email' => 'engineering@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'department_id' => 3,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // Create categories
        $categories = [
            [
                'name' => 'Computers',
                'description' => 'Desktop and laptop computers',
                'color' => '#3498db',
            ],
            [
                'name' => 'Audio/Visual Equipment',
                'description' => 'Projectors, cameras, and other AV equipment',
                'color' => '#e74c3c',
            ],
            [
                'name' => 'Mobile Devices',
                'description' => 'Smartphones, tablets, and other mobile devices',
                'color' => '#2ecc71',
            ],
            [
                'name' => 'Tools',
                'description' => 'Hand tools and power tools',
                'color' => '#f39c12',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create items
        $items = [
            // Computers
            [
                'name' => 'MacBook Pro 16"',
                'description' => '16-inch MacBook Pro with M1 Pro chip',
                'serial_number' => 'MB' . rand(100000, 999999),
                'asset_tag' => 'MAC' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 2499.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 5,
                'category_id' => 1,
            ],
            [
                'name' => 'Dell XPS 15',
                'description' => '15-inch Dell XPS with 11th Gen Intel Core i7',
                'serial_number' => 'DL' . rand(100000, 999999),
                'asset_tag' => 'DEL' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 1899.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 3,
                'category_id' => 1,
            ],

            // Audio/Visual Equipment
            [
                'name' => 'Epson Projector',
                'description' => 'Epson PowerLite 1080p projector',
                'serial_number' => 'EP' . rand(100000, 999999),
                'asset_tag' => 'PRJ' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 699.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 2,
                'category_id' => 2,
            ],
            [
                'name' => 'Canon DSLR Camera',
                'description' => 'Canon EOS Rebel T7 DSLR Camera',
                'serial_number' => 'CN' . rand(100000, 999999),
                'asset_tag' => 'CAM' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 549.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 4,
                'category_id' => 2,
            ],

            // Mobile Devices
            [
                'name' => 'iPad Pro 12.9"',
                'description' => '12.9-inch iPad Pro with M1 chip',
                'serial_number' => 'IP' . rand(100000, 999999),
                'asset_tag' => 'IPAD' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 1099.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 6,
                'category_id' => 3,
            ],
            [
                'name' => 'iPhone 13 Pro',
                'description' => 'iPhone 13 Pro with 256GB storage',
                'serial_number' => 'IPH' . rand(100000, 999999),
                'asset_tag' => 'IPHN' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 999.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 10,
                'category_id' => 3,
            ],

            // Tools
            [
                'name' => 'DeWalt Drill Set',
                'description' => 'DeWalt 20V MAX cordless drill set',
                'serial_number' => 'DW' . rand(100000, 999999),
                'asset_tag' => 'TOOL' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 199.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 3,
                'category_id' => 4,
            ],
            [
                'name' => 'Hammer Set',
                'description' => 'Set of various hammers for different uses',
                'serial_number' => 'TL' . rand(100000, 999999),
                'asset_tag' => 'TOOL' . rand(1000, 9999),
                'purchase_date' => now()->subMonths(rand(1, 12)),
                'purchase_cost' => 89.99,
                'warranty_expiry' => now()->addYears(1),
                'status' => 'available',
                'total_quantity' => 5,
                'category_id' => 4,
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }

        // Create a sample loan with items
        $loan = Loan::create([
            'loan_number' => 'LOAN-' . rand(10000, 99999),
            'user_id' => 1, // IT Admin
            'department_id' => 1, // IT Department
            'is_guest' => false,
            'loan_date' => now()->subDays(5),
            'due_date' => now()->addDays(25),
            'status' => 'active',
            'notes' => 'Equipment borrowed for the quarterly IT maintenance',
        ]);

        // Attach items to the loan
        $loan->items()->attach([
            1 => [ // MacBook Pro
                'quantity' => 2,
                'serial_numbers' => json_encode(['MB123456', 'MB123457']),
                'condition_before' => 'Excellent condition, no scratches',
                'status' => 'loaned',
            ],
            3 => [ // Epson Projector
                'quantity' => 1,
                'serial_numbers' => null,
                'condition_before' => 'Good condition, slight wear on power cord',
                'status' => 'loaned',
            ],
        ]);

        // Create a guest loan
        $guestLoan = Loan::create([
            'loan_number' => 'GUEST-' . rand(10000, 99999),
            'user_id' => 2, // Marketing Manager (who created the loan)
            'department_id' => 2, // Marketing Department
            'is_guest' => true,
            'guest_name' => 'John Smith',
            'guest_email' => 'john.smith@external.com',
            'guest_phone' => '+1 (555) 123-4567',
            'guest_id' => 'EXT-12345',
            'loan_date' => now()->subDays(2),
            'due_date' => now()->addDays(12),
            'status' => 'active',
            'notes' => 'Equipment loaned to external photographer for marketing campaign',
        ]);

        // Attach items to the guest loan
        $guestLoan->items()->attach([
            4 => [ // Canon DSLR Camera
                'quantity' => 2,
                'serial_numbers' => json_encode(['CN123456', 'CN123457']),
                'condition_before' => 'Excellent condition, includes all accessories',
                'status' => 'loaned',
            ],
            6 => [ // iPhone 13 Pro
                'quantity' => 1,
                'serial_numbers' => json_encode(['IPH123456']),
                'condition_before' => 'New condition, still in plastic wrap',
                'status' => 'loaned',
            ],
        ]);
    }
}
