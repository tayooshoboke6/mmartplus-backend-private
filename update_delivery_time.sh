#!/bin/bash

# Script to update mmartplus-backend with delivery time feature
# To be run on Digital Ocean server

# Set error handling
set -e

echo "===== Starting deployment of delivery time feature ====="

# Navigate to the project directory
cd /var/www/mmartplus

echo "===== Moving files to correct locations ====="

# Check for migration file in root directory
if [ -f ./2025_03_07_000001_add_delivery_time_to_products_table.php ]; then
    echo "Moving migration file to database/migrations/"
    mkdir -p database/migrations
    mv ./2025_03_07_000001_add_delivery_time_to_products_table.php database/migrations/
elif [ ! -f database/migrations/2025_03_07_000001_add_delivery_time_to_products_table.php ]; then
    echo "===== Creating migration file ====="
    # Create the migration file if it doesn't exist
    mkdir -p database/migrations
    cat > database/migrations/2025_03_07_000001_add_delivery_time_to_products_table.php << 'EOL'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('delivery_time')->nullable()->after('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('delivery_time');
        });
    }
};
EOL
else
    echo "===== Migration file already exists ====="
fi

# Move seeder file if it exists in root
if [ -f ./UpdateProductDeliveryTimeSeeder.php ]; then
    echo "Moving seeder file to database/seeders/"
    mkdir -p database/seeders
    mv ./UpdateProductDeliveryTimeSeeder.php database/seeders/
elif [ ! -f database/seeders/UpdateProductDeliveryTimeSeeder.php ]; then
    echo "===== Creating seeder file ====="
    # Create the seeder file
    mkdir -p database/seeders
    cat > database/seeders/UpdateProductDeliveryTimeSeeder.php << 'EOL'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductDeliveryTimeSeeder extends Seeder
{
    /**
     * Run the database seeds to update existing products with random delivery times.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting to update products with random delivery times...');
        
        // Delivery time options from the frontend dropdown
        $deliveryTimeOptions = [
            'Delivery in minutes',
            'Delivery in 24 hrs',
            'Delivery in 48 hrs',
            'Delivery in 2-4 business days'
        ];
        
        // Get all products without a delivery time set
        $products = Product::all();
        $updateCount = 0;
        
        // Begin transaction to improve performance with many updates
        DB::beginTransaction();
        
        try {
            foreach ($products as $product) {
                // Assign a random delivery time
                $randomIndex = array_rand($deliveryTimeOptions);
                $product->delivery_time = $deliveryTimeOptions[$randomIndex];
                $product->save();
                $updateCount++;
            }
            
            DB::commit();
            $this->command->info("Successfully updated {$updateCount} products with random delivery times.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to update products: ' . $e->getMessage());
        }
    }
}
EOL
else
    echo "===== Seeder file already exists ====="
fi

# Update Product model if delivery_time is not in fillable
if ! grep -q "'delivery_time'" app/Models/Product.php; then
    echo "===== Updating Product model to include delivery_time ====="
    sed -i "/protected \$fillable/,/];/ s/];/'delivery_time',\n    ];/" app/Models/Product.php
else
    echo "===== Product model already has delivery_time field ====="
fi

# Update ProductController store method validation
if ! grep -q "'delivery_time'" app/Http/Controllers/ProductController.php; then
    echo "===== Updating ProductController to include delivery_time ====="
    # Add to validation rules
    sed -i "/expiry_date.*nullable/a\ \ \ \ \ \ \ \ \ \ \ \ 'delivery_time' => 'nullable|string|max:255'," app/Http/Controllers/ProductController.php
    
    # Add to create array
    sed -i "/expiry_date.*request->expiry_date/a\ \ \ \ \ \ \ \ \ \ \ \ 'delivery_time' => \$request->delivery_time," app/Http/Controllers/ProductController.php
    
    # Add to update array 
    sed -i "/expiry_date.*request->expiry_date/a\ \ \ \ \ \ \ \ \ \ \ \ 'delivery_time' => \$request->delivery_time," app/Http/Controllers/ProductController.php
else
    echo "===== ProductController already has delivery_time updates ====="
fi

echo "===== Running migrations ====="
php artisan migrate

echo "===== Running delivery time seeder ====="
php artisan db:seed --class=UpdateProductDeliveryTimeSeeder

echo "===== Clearing cache ====="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

echo "===== Delivery time feature successfully deployed ====="
echo "Remember to test by checking a product in the admin panel and frontend."
