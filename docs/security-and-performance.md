# Security and Performance Enhancements for M-Mart+

This document outlines security and performance best practices for the M-Mart+ application in production.

## Security Enhancements

### API Security

1. **API Rate Limiting**

   Add rate limiting to protect against brute force attacks and API abuse:

   ```php
   // In RouteServiceProvider.php
   protected function configureRateLimiting()
   {
       RateLimiter::for('api', function (Request $request) {
           return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
       });
   }
   ```

2. **API Authentication Timeout**

   Set a reasonable timeout for API tokens:

   ```php
   // config/sanctum.php
   'expiration' => 60 * 24, // 24 hours in minutes
   ```

3. **CORS Hardening**

   Ensure CORS is properly configured (already implemented in the updated CORS configuration).

### Database Security

1. **Prepared Statements**

   Always use Laravel's query builder or Eloquent, which uses prepared statements by default to prevent SQL injection:

   ```php
   // Good - Uses prepared statements
   $users = DB::table('users')->where('email', $email)->get();
   
   // Also good - Eloquent
   $users = User::where('email', $email)->get();
   ```

2. **Database User Privileges**

   Create a dedicated database user with limited privileges:

   ```sql
   CREATE USER 'mmartuser'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON mmartplus.* TO 'mmartuser'@'localhost';
   ```

### General Security

1. **Content Security Policy**

   Implement a Content Security Policy by adding the following headers in your Nginx configuration:

   ```nginx
   add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google-analytics.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https://api.mmartplus.com;";
   ```

2. **HTTPS Only**

   Force HTTPS by redirecting all HTTP traffic:

   ```nginx
   server {
       listen 80;
       server_name api.mmartplus.com;
       return 301 https://$host$request_uri;
   }
   ```

3. **Secure File Uploads**

   Validate all file uploads and store them outside the web root:

   ```php
   $validated = $request->validate([
       'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
   ]);
   
   $path = $request->file('image')->store('uploads', 'private');
   ```

## Performance Enhancements

### Database Optimization

1. **Indexing**

   Add indexes to frequently queried columns:

   ```php
   // In a migration file
   $table->index('user_id');
   $table->index(['status', 'created_at']);
   ```

2. **Query Optimization**

   Use eager loading to prevent N+1 query problems:

   ```php
   // Bad - N+1 query problem
   $orders = Order::all();
   foreach ($orders as $order) {
       $user = $order->user; // Additional query for each order
   }
   
   // Good - Eager loading
   $orders = Order::with('user')->get();
   ```

### Caching

1. **Response Caching**

   Implement response caching for frequently accessed, rarely changed data:

   ```php
   public function index()
   {
       return Cache::remember('products', 3600, function () {
           return Product::all();
       });
   }
   ```

2. **Database Query Caching**

   Cache expensive queries:

   ```php
   $reports = Cache::remember('monthly_reports', 86400, function () {
       return DB::table('orders')
           ->select(DB::raw('MONTH(created_at) as month, SUM(total) as revenue'))
           ->whereYear('created_at', date('Y'))
           ->groupBy('month')
           ->get();
   });
   ```

3. **OPCache Configuration**

   Enable OPCache for PHP in production by adding the following to `php.ini`:

   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=4000
   opcache.revalidate_freq=60
   opcache.fast_shutdown=1
   opcache.enable_cli=1
   ```

### Server Configuration

1. **Nginx Worker Configuration**

   Optimize Nginx worker processes:

   ```nginx
   worker_processes auto;
   worker_rlimit_nofile 65535;
   
   events {
       worker_connections 4096;
       multi_accept on;
       use epoll;
   }
   ```

2. **Gzip Compression**

   Enable Gzip compression in Nginx:

   ```nginx
   gzip on;
   gzip_vary on;
   gzip_proxied any;
   gzip_comp_level 6;
   gzip_buffers 16 8k;
   gzip_http_version 1.1;
   gzip_min_length 256;
   gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript application/vnd.ms-fontobject application/x-font-ttf font/opentype image/svg+xml image/x-icon;
   ```

3. **Browser Caching**

   Configure browser caching for static assets:

   ```nginx
   location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
       expires 7d;
       add_header Cache-Control "public, max-age=604800";
   }
   ```

## Monitoring and Logging

1. **Laravel Telescope**

   Consider installing Laravel Telescope in development for debugging:

   ```bash
   composer require laravel/telescope --dev
   php artisan telescope:install
   php artisan migrate
   ```

2. **Centralized Logging**

   Set up a centralized logging solution:

   ```php
   // config/logging.php
   'channels' => [
       'stack' => [
           'driver' => 'stack',
           'channels' => ['single', 'slack'],
       ],
       'slack' => [
           'driver' => 'slack',
           'url' => env('LOG_SLACK_WEBHOOK_URL'),
           'username' => 'Laravel Log',
           'emoji' => ':boom:',
           'level' => 'critical',
       ],
   ],
   ```

3. **Health Checks**

   Implement health checks endpoint for monitoring:

   ```php
   Route::get('/health', function () {
       $services = [
           'database' => DB::connection()->getPdo() ? true : false,
           'redis' => Cache::store('redis')->has('health-check-ping') !== false,
           'storage' => Storage::disk('local')->exists('.gitignore'),
       ];
       
       $status = !in_array(false, $services) ? 200 : 500;
       
       return response()->json([
           'status' => $status === 200 ? 'ok' : 'error',
           'services' => $services
       ], $status);
   });
   ```

## Scalability

1. **Queue System**

   Use Laravel's queue system for time-consuming tasks:

   ```php
   // Instead of processing emails directly
   Mail::to($user)->send(new WelcomeEmail($user));
   
   // Use a queue
   Mail::to($user)->queue(new WelcomeEmail($user));
   ```

2. **Horizontal Scaling**

   Design your application to be stateless for horizontal scaling:
   - Store session data in a database or Redis
   - Use a load balancer with sticky sessions
   - Implement a distributed caching solution

3. **CDN Integration**

   Use a CDN for static assets to reduce server load and improve loading times:

   ```
   ASSET_URL=https://cdn.mmartplus.com
   ```
