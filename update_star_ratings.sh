#!/bin/bash

# Script to update mmartplus-backend with star rating feature
# To be run on Digital Ocean server

# Set error handling
set -e

echo "===== Starting deployment of star rating feature ====="

# Navigate to the project directory
cd /var/www/mmartplus

echo "===== Moving files to correct locations ====="

# Check for migration file in root directory
if [ -f ./2025_03_07_000002_add_ratings_to_products_table.php ]; then
    echo "Moving migration file to database/migrations/"
    mkdir -p database/migrations
    mv ./2025_03_07_000002_add_ratings_to_products_table.php database/migrations/
fi

# Move seeder file if it exists in root
if [ -f ./DefaultProductRatingsSeeder.php ]; then
    echo "Moving seeder file to database/seeders/"
    mkdir -p database/seeders
    mv ./DefaultProductRatingsSeeder.php database/seeders/
fi

# Move ProductRating model if it exists in root
if [ -f ./ProductRating.php ]; then
    echo "Moving ProductRating model to app/Models/"
    mkdir -p app/Models
    mv ./ProductRating.php app/Models/
fi

# Move ProductRatingController if it exists in root
if [ -f ./ProductRatingController.php ]; then
    echo "Moving ProductRatingController to app/Http/Controllers/"
    mkdir -p app/Http/Controllers
    mv ./ProductRatingController.php app/Http/Controllers/
fi

# Update Product model to include ratings relationship if not already added
if ! grep -q "public function ratings" app/Models/Product.php; then
    echo "===== Updating Product model to include ratings relationship ====="
    # Find the position after the category relationship method to add the ratings relationship
    sed -i '/public function category/,/}/!b;/}/a\
\
    \/**\
     \* Get the ratings for the product.\
     \*/\
    public function ratings()\
    {\
        return $this->hasMany(ProductRating::class);\
    }\
' app/Models/Product.php
fi

# Update routes to add ProductRating routes if not already added
if ! grep -q "ProductRatingController" routes/api.php; then
    echo "===== Updating routes to include product rating endpoints ====="
    # Add the controller import
    sed -i '/use App\\Http\\Controllers\\PasswordResetController;/a use App\\Http\\Controllers\\ProductRatingController;' routes/api.php
    
    # Add the routes in the auth middleware group
    sed -i '/Route::get(.\"\\\/email\\\/status\"/,/});/s/});/});\
\
    \/\/ Product Ratings (Requires Authentication)\
    Route::get(\"\/products\/{productId}\/ratings\", [ProductRatingController::class, \"getProductRatings\"]);\
    Route::post(\"\/products\/{productId}\/ratings\", [ProductRatingController::class, \"rateProduct\"]);\
    Route::delete(\"\/ratings\/{ratingId}\", [ProductRatingController::class, \"deleteRating\"]);/' routes/api.php
fi

echo "===== Running migrations ====="
php artisan migrate

echo "===== Running default ratings seeder ====="
php artisan db:seed --class=DefaultProductRatingsSeeder

echo "===== Clearing cache ====="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

echo "===== Star rating feature successfully deployed ====="
echo "Remember to test product ratings in both admin panel and frontend."
