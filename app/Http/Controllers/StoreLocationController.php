<?php

namespace App\Http\Controllers;

use App\Models\StoreLocation;
use Illuminate\Http\Request;

class StoreLocationController extends Controller
{
    /**
     * Display a listing of all store locations.
     *
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/store-locations",
     *     summary="Get all store locations",
     *     tags={"Store Locations"},
     *     @OA\Response(
     *         response=200,
     *         description="List of store locations",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="M-Mart Main Store"),
     *                     @OA\Property(property="address", type="string", example="123 Main Street"),
     *                     @OA\Property(property="city", type="string", example="San Francisco"),
     *                     @OA\Property(property="state", type="string", example="CA"),
     *                     @OA\Property(property="zip_code", type="string", example="94105"),
     *                     @OA\Property(property="phone", type="string", example="(415) 555-1234"),
     *                     @OA\Property(property="email", type="string", format="email", example="mainstore@mmart.com"),
     *                     @OA\Property(property="description", type="string", example="Our flagship store in downtown San Francisco."),
     *                     @OA\Property(property="opening_hours", type="object"),
     *                     @OA\Property(property="pickup_instructions", type="string"),
     *                     @OA\Property(property="pickup_available", type="boolean", example=true),
     *                     @OA\Property(property="latitude", type="number", format="float", example=37.7749),
     *                     @OA\Property(property="longitude", type="number", format="float", example=-122.4194),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $storeLocations = StoreLocation::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $storeLocations
        ]);
    }
    
    /**
     * Display a listing of pickup-available store locations.
     *
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/store-locations/pickup",
     *     summary="Get pickup-available store locations",
     *     tags={"Store Locations"},
     *     @OA\Response(
     *         response=200,
     *         description="List of pickup-available store locations",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="M-Mart Main Store"),
     *                     @OA\Property(property="pickup_available", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getPickupLocations()
    {
        $pickupLocations = StoreLocation::where('pickup_available', true)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $pickupLocations
        ]);
    }
    
    /**
     * Store a newly created store location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/admin/store-locations",
     *     summary="Create a new store location",
     *     tags={"Store Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "address", "city", "state", "zip_code"},
     *             @OA\Property(property="name", type="string", example="M-Mart New Store"),
     *             @OA\Property(property="address", type="string", example="456 New Street"),
     *             @OA\Property(property="city", type="string", example="San Francisco"),
     *             @OA\Property(property="state", type="string", example="CA"),
     *             @OA\Property(property="zip_code", type="string", example="94105"),
     *             @OA\Property(property="phone", type="string", example="(415) 555-5678"),
     *             @OA\Property(property="email", type="string", format="email", example="newstore@mmart.com"),
     *             @OA\Property(property="description", type="string", example="Our newest store location"),
     *             @OA\Property(property="opening_hours", type="object"),
     *             @OA\Property(property="pickup_instructions", type="string", example="Come to the back door for pickup"),
     *             @OA\Property(property="pickup_available", type="boolean", example=true),
     *             @OA\Property(property="latitude", type="number", format="float", example=37.7749),
     *             @OA\Property(property="longitude", type="number", format="float", example=-122.4194)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Store location created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Store location created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'pickup_instructions' => 'nullable|string',
            'pickup_available' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        $storeLocation = StoreLocation::create($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Store location created successfully',
            'data' => $storeLocation
        ], 201);
    }
    
    /**
     * Display the specified store location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/store-locations/{id}",
     *     summary="Get a store location by ID",
     *     tags={"Store Locations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store location ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store location details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store location not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\StoreLocation] 1")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $storeLocation = StoreLocation::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $storeLocation
        ]);
    }
    
    /**
     * Update the specified store location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Put(
     *     path="/api/admin/store-locations/{id}",
     *     summary="Update a store location",
     *     tags={"Store Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store location ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Store Name"),
     *             @OA\Property(property="address", type="string", example="Updated Address"),
     *             @OA\Property(property="city", type="string", example="Updated City"),
     *             @OA\Property(property="state", type="string", example="CA"),
     *             @OA\Property(property="zip_code", type="string", example="94106"),
     *             @OA\Property(property="pickup_available", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store location updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Store location updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store location not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\StoreLocation] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string|max:255',
            'address' => 'string|max:255',
            'city' => 'string|max:255',
            'state' => 'string|max:255',
            'zip_code' => 'string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'pickup_instructions' => 'nullable|string',
            'pickup_available' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        $storeLocation = StoreLocation::findOrFail($id);
        $storeLocation->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Store location updated successfully',
            'data' => $storeLocation
        ]);
    }
    
    /**
     * Remove the specified store location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/admin/store-locations/{id}",
     *     summary="Delete a store location",
     *     tags={"Store Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store location ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store location deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Store location deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store location not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\StoreLocation] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $storeLocation = StoreLocation::findOrFail($id);
        $storeLocation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Store location deleted successfully'
        ]);
    }
    
    /**
     * Toggle the pickup availability of a store location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Patch(
     *     path="/api/admin/store-locations/{id}/toggle-pickup",
     *     summary="Toggle pickup availability for a store location",
     *     tags={"Store Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store location ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pickup availability toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Pickup availability toggled successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="pickup_available", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store location not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\StoreLocation] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function togglePickupAvailability($id)
    {
        $storeLocation = StoreLocation::findOrFail($id);
        $storeLocation->pickup_available = !$storeLocation->pickup_available;
        $storeLocation->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Pickup availability toggled successfully',
            'data' => [
                'id' => $storeLocation->id,
                'pickup_available' => $storeLocation->pickup_available
            ]
        ]);
    }
    
    /**
     * Get nearby store locations based on latitude and longitude.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/store-locations/nearby",
     *     summary="Get nearby store locations",
     *     description="Get store locations within a specified radius from the given coordinates",
     *     tags={"Store Locations"},
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         required=true,
     *         description="Latitude coordinate",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         required=true,
     *         description="Longitude coordinate",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="radius",
     *         in="query",
     *         required=false,
     *         description="Radius in kilometers (default: 10)",
     *         @OA\Schema(type="number", format="float", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of nearby store locations",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/StoreLocation")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="latitude", type="array", @OA\Items(type="string", example="The latitude field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function getNearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|min:0'
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 10); // Default radius 10km
        
        // Earth's radius in kilometers
        $earthRadius = 6371;
        
        // Find locations within the specified radius using the Haversine formula
        $locations = StoreLocation::selectRaw("
            id, name, address, city, state, zip_code, phone, email, description, 
            opening_hours, pickup_instructions, pickup_available, latitude, longitude,
            ($earthRadius * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
            [$latitude, $longitude, $latitude]
        )
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }
}
