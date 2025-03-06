<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\NotificationBar;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    /**
     * Get all banners
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBanners()
    {
        $banners = Banner::all();
        
        return response()->json([
            'status' => 'success',
            'banners' => $banners
        ]);
    }
    
    /**
     * Get only active banners
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveBanners()
    {
        $banners = Banner::where('active', true)->get();
        
        return response()->json([
            'status' => 'success',
            'banners' => $banners
        ]);
    }
    
    /**
     * Store a new banner
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:50',
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:255',
            'image' => 'required|string|max:255',
            'bgColor' => 'required|string|max:20',
            'imgBgColor' => 'required|string|max:20',
            'link' => 'required|string|max:255',
            'active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $banner = Banner::create($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner created successfully',
            'banner' => $banner
        ], 201);
    }
    
    /**
     * Update an existing banner
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        
        if (!$banner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Banner not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'label' => 'string|max:50',
            'title' => 'string|max:100',
            'description' => 'string|max:255',
            'image' => 'string|max:255',
            'bgColor' => 'string|max:20',
            'imgBgColor' => 'string|max:20',
            'link' => 'string|max:255',
            'active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $banner->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner updated successfully',
            'banner' => $banner
        ]);
    }
    
    /**
     * Delete a banner
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyBanner($id)
    {
        $banner = Banner::find($id);
        
        if (!$banner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Banner not found'
            ], 404);
        }
        
        $banner->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner deleted successfully'
        ]);
    }
    
    /**
     * Toggle banner status
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBannerStatus($id)
    {
        $banner = Banner::find($id);
        
        if (!$banner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Banner not found'
            ], 404);
        }
        
        $banner->active = !$banner->active;
        $banner->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner status toggled successfully',
            'banner' => $banner
        ]);
    }
    
    /**
     * Get notification bar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationBar()
    {
        $notificationBar = NotificationBar::first();
        
        if (!$notificationBar) {
            // Create a default notification bar if none exists
            $notificationBar = NotificationBar::create([
                'active' => false,
                'message' => 'Welcome to M-Mart+',
                'linkText' => 'Shop Now',
                'linkUrl' => '/products',
                'bgColor' => '#e25822'
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'notificationBar' => $notificationBar
        ]);
    }
    
    /**
     * Get active notification bar for public view
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveNotificationBar()
    {
        $notificationBar = NotificationBar::where('active', true)->first();
        
        if (!$notificationBar) {
            return response()->json([
                'status' => 'success',
                'notificationBar' => null
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'notificationBar' => $notificationBar
        ]);
    }
    
    /**
     * Update notification bar
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationBar(Request $request)
    {
        $notificationBar = NotificationBar::first();
        
        if (!$notificationBar) {
            // Create a new notification bar if none exists
            $notificationBar = new NotificationBar();
        }
        
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:255',
            'linkText' => 'required|string|max:50',
            'linkUrl' => 'required|string|max:255',
            'bgColor' => 'required|string|max:20',
            'active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $notificationBar->fill($request->all());
        $notificationBar->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notification bar updated successfully',
            'notificationBar' => $notificationBar
        ]);
    }
    
    /**
     * Toggle notification bar status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleNotificationBarStatus()
    {
        $notificationBar = NotificationBar::first();
        
        if (!$notificationBar) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification bar not found'
            ], 404);
        }
        
        $notificationBar->active = !$notificationBar->active;
        $notificationBar->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notification bar status toggled successfully',
            'notificationBar' => $notificationBar
        ]);
    }
}
