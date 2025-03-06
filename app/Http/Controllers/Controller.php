<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="M-Mart+ API",
 *     version="1.0.0",
 *     description="M-Mart+ Backend API Documentation",
 *     @OA\Contact(
 *         email="support@mmart.com",
 *         name="M-Mart Support"
 *     )
 * )
 *
 * @OA\Server(
 *     description="M-Mart+ API Server",
 *     url=L5_SWAGGER_CONST_HOST
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for User Authentication"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="API Endpoints for User Management"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for Admin Dashboard"
 * )
 *
 * @OA\Tag(
 *     name="Store Locations",
 *     description="API Endpoints for Store Locations Management"
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="API Endpoints for Product Management"
 * )
 *
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints for Category Management"
 * )
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="API Endpoints for Order Management"
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
