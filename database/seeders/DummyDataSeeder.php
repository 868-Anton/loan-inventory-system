<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Department;
use App\Models\Loan;
use App\Models\GuestBorrower;
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
                'description' => 'Information Technology Department',
                'contact_email' => 'it@example.com',
                'contact_phone' => '+1 (555) 100-1000',
                'location' => 'Main Campus, Building A, Floor 1',
            ],
            [
                'name' => 'Marketing Department',
                'description' => 'Marketing and Communications',
                'contact_email' => 'marketing@example.com',
                'contact_phone' => '+1 (555) 100-2000',
                'location' => 'Main Campus, Building B, Floor 2',
            ],
            [
                'name' => 'Engineering Department',
                'description' => 'Engineering and Development',
                'contact_email' => 'engineering@example.com',
                'contact_phone' => '+1 (555) 100-3000',
                'location' => 'Tech Campus, Building C, Floor 1',
            ],
            [
                'name' => 'Facilities Department',
                'description' => 'Facilities and Maintenance',
                'contact_email' => 'facilities@example.com',
                'contact_phone' => '+1 (555) 100-4000',
                'location' => 'Main Campus, Building D, Floor 1',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

        // Create users
        $users = [
            [
                'name' => 'IT Admin',
                'email' => 'it.admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'department_id' => 1, // IT Department
            ],
            [
                'name' => 'Marketing Manager',
                'email' => 'marketing.manager@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'department_id' => 2, // Marketing Department
            ],
            [
                'name' => 'Engineering Lead',
                'email' => 'engineering.lead@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'department_id' => 3, // Engineering Department
            ],
            [
                'name' => 'Facilities Coordinator',
                'email' => 'facilities.coordinator@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'department_id' => 4, // Facilities Department
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // Create categories
        $categories = [
            [
                'name' => 'Computers',
                'description' => 'Laptops, desktops, and tablets',
                'slug' => 'computers',
                'icon' => 'heroicon-o-computer-desktop',
                'color' => '#3498db',
            ],
            [
                'name' => 'Audio/Visual',
                'description' => 'Projectors, cameras, and recording equipment',
                'slug' => 'audio-visual',
                'icon' => 'heroicon-o-video-camera',
                'color' => '#9b59b6',
            ],
            [
                'name' => 'Mobile Devices',
                'description' => 'Smartphones, tablets, and other portable devices',
                'slug' => 'mobile-devices',
                'icon' => 'heroicon-o-device-phone-mobile',
                'color' => '#2ecc71',
            ],
            [
                'name' => 'Tools',
                'description' => 'Hand tools, power tools, and equipment',
                'slug' => 'tools',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'color' => '#e74c3c',
            ],
            [
                'name' => 'Software',
                'description' => 'Software licenses and access keys',
                'slug' => 'software',
                'icon' => 'heroicon-o-cog',
                'color' => '#f39c12',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Define item templates (used to generate individual items)
        $itemTemplates = [
            [
                'name' => 'MacBook Pro',
                'description' => '16-inch MacBook Pro with M2 Pro chip',
                'purchase_cost' => 2499.99,
                'warranty_expiry' => now()->addYears(3),
                'category_id' => 1,
                'serial_prefix' => 'MBP',
                'asset_prefix' => 'COMP',
                'count' => 5, // Number of individual items to create
            ],
            [
                'name' => 'Dell XPS 15',
                'description' => 'Dell XPS 15 with 11th Gen Intel i7',
                'purchase_cost' => 1799.99,
                'warranty_expiry' => now()->addYears(2),
                'category_id' => 1,
                'serial_prefix' => 'DELL',
                'asset_prefix' => 'COMP',
                'count' => 3,
            ],
            [
                'name' => 'Epson Projector',
                'description' => 'Epson PowerLite 1080p projector',
                'purchase_cost' => 799.99,
                'warranty_expiry' => now()->addYears(2),
                'category_id' => 2,
                'serial_prefix' => 'EPPJ',
                'asset_prefix' => 'PROJ',
                'count' => 2,
            ],
            [
                'name' => 'Canon DSLR Camera',
                'description' => 'Canon EOS 5D Mark IV DSLR Camera',
                'purchase_cost' => 2499.99,
                'warranty_expiry' => now()->addYears(1),
                'category_id' => 2,
                'serial_prefix' => 'CNDSLR',
                'asset_prefix' => 'CAM',
                'count' => 3,
            ],
            [
                'name' => 'iPad Pro',
                'description' => '12.9-inch iPad Pro with M2 chip',
                'purchase_cost' => 1099.99,
                'warranty_expiry' => now()->addYears(2),
                'category_id' => 3,
                'serial_prefix' => 'IPD',
                'asset_prefix' => 'TAB',
                'count' => 4,
            ],
            [
                'name' => 'iPhone 13 Pro',
                'description' => 'iPhone 13 Pro with 256GB storage',
                'purchase_cost' => 999.99,
                'warranty_expiry' => now()->addYears(1),
                'category_id' => 3,
                'serial_prefix' => 'IPH',
                'asset_prefix' => 'PHONE',
                'count' => 5,
            ],
            [
                'name' => 'DeWalt Drill Set',
                'description' => 'DeWalt 20V MAX cordless drill set',
                'purchase_cost' => 199.99,
                'warranty_expiry' => now()->addYears(1),
                'category_id' => 4,
                'serial_prefix' => 'DW',
                'asset_prefix' => 'TOOL',
                'count' => 3,
            ],
            [
                'name' => 'Hammer Set',
                'description' => 'Set of various hammers for different uses',
                'purchase_cost' => 89.99,
                'warranty_expiry' => now()->addYears(1),
                'category_id' => 4,
                'serial_prefix' => 'TL',
                'asset_prefix' => 'TOOL',
                'count' => 5,
            ],
        ];

        // Create individual items based on templates
        foreach ($itemTemplates as $template) {
            $count = $template['count'];

            for ($i = 1; $i <= $count; $i++) {
                $serialNumber = $template['serial_prefix'] . rand(100000, 999999);
                $assetTag = $template['asset_prefix'] . rand(1000, 9999);

                Item::create([
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'serial_number' => $serialNumber,
                    'asset_tag' => $assetTag,
                    'purchase_date' => now()->subMonths(rand(1, 12)),
                    'purchase_cost' => $template['purchase_cost'],
                    'warranty_expiry' => $template['warranty_expiry'],
                    'status' => 'available',
                    'deprecated_total_quantity' => 1, // Always 1 for individual items
                    'category_id' => $template['category_id'],
                ]);
            }
        }

        // Create guest borrowers
        $guestBorrower = GuestBorrower::create([
            'name' => 'John Smith',
            'email' => 'john.smith@external.com',
            'phone' => '+1 (555) 123-4567',
            'id_number' => 'EXT-12345',
            'organization' => 'Smith Photography',
            'notes' => 'Regular equipment borrower for marketing campaigns',
        ]);

        // Create a sample loan with items
        $loan = Loan::create([
            'loan_number' => 'LOAN-' . rand(10000, 99999),
            'borrower_type' => 'App\\Models\\User',
            'borrower_id' => 1, // IT Admin
            'department_id' => 1, // IT Department
            'loan_date' => now()->subDays(5),
            'due_date' => now()->addDays(25),
            'status' => 'active',
            'notes' => 'Equipment borrowed for the quarterly IT maintenance',
        ]);

        // Attach individual MacBook Pro items to the loan (let's say item IDs 1 and 2)
        $loan->items()->attach(1, [
            'deprecated_quantity' => 1, // Individual item, so quantity is always 1
            'serial_numbers' => null, // Serial number is already in the items table
            'condition_before' => 'Excellent condition, no scratches',
            'status' => 'loaned',
        ]);

        $loan->items()->attach(2, [
            'deprecated_quantity' => 1,
            'serial_numbers' => null,
            'condition_before' => 'Good condition, slight wear on keyboard',
            'status' => 'loaned',
        ]);

        // Attach an Epson Projector to the loan (item ID 11)
        $loan->items()->attach(11, [
            'deprecated_quantity' => 1,
            'serial_numbers' => null,
            'condition_before' => 'Good condition, slight wear on power cord',
            'status' => 'loaned',
        ]);

        // Create a guest loan
        $guestLoan = Loan::create([
            'loan_number' => 'GUEST-' . rand(10000, 99999),
            'borrower_type' => 'App\\Models\\GuestBorrower',
            'borrower_id' => $guestBorrower->id,
            'department_id' => 2, // Marketing Department
            'loan_date' => now()->subDays(2),
            'due_date' => now()->addDays(12),
            'status' => 'active',
            'notes' => 'Equipment loaned to external photographer for marketing campaign',
        ]);

        // Attach a Canon DSLR Camera to the guest loan (item IDs 13 and 14)
        $guestLoan->items()->attach(13, [
            'deprecated_quantity' => 1,
            'serial_numbers' => null,
            'condition_before' => 'Excellent condition, includes all accessories',
            'status' => 'loaned',
        ]);

        $guestLoan->items()->attach(14, [
            'deprecated_quantity' => 1,
            'serial_numbers' => null,
            'condition_before' => 'Good condition, minor scuffs on camera body',
            'status' => 'loaned',
        ]);

        // Attach an iPhone to the guest loan (item ID 22)
        $guestLoan->items()->attach(22, [
            'deprecated_quantity' => 1,
            'serial_numbers' => null,
            'condition_before' => 'New condition, still in plastic wrap',
            'status' => 'loaned',
        ]);
    }
}
