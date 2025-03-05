<?php

namespace App\Http\Controllers;

use App\Models\ProductSection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sections = ProductSection::orderBy('display_order', 'asc')->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product sections retrieved successfully',
            'data' => $sections
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:product_sections,title',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'background_color' => 'nullable|string|max:50',
            'text_color' => 'nullable|string|max:50',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'display_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the highest current display_order or default to 0
        $highestOrder = ProductSection::max('display_order') ?? 0;
        
        $section = ProductSection::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'background_color' => $request->background_color ?? '#f7f7f7',
            'text_color' => $request->text_color ?? '#333333',
            'product_ids' => $request->product_ids,
            'display_order' => $request->display_order ?? ($highestOrder + 1),
            'active' => $request->active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product section created successfully',
            'data' => $section
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $section = ProductSection::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product section retrieved successfully',
            'data' => $section
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $section = ProductSection::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255', Rule::unique('product_sections')->ignore($id)],
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'background_color' => 'nullable|string|max:50',
            'text_color' => 'nullable|string|max:50',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'display_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $section->update([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'background_color' => $request->background_color ?? $section->background_color,
            'text_color' => $request->text_color ?? $section->text_color,
            'product_ids' => $request->product_ids,
            'display_order' => $request->display_order ?? $section->display_order,
            'active' => $request->has('active') ? $request->active : $section->active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product section updated successfully',
            'data' => $section
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $section = ProductSection::findOrFail($id);
        $section->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product section deleted successfully'
        ]);
    }
    
    /**
     * Toggle the active status of the specified product section.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $section = ProductSection::findOrFail($id);
        $section->active = !$section->active;
        $section->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product section status toggled successfully',
            'data' => $section
        ]);
    }
    
    /**
     * Reorder product sections.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_ids' => 'required|array',
            'section_ids.*' => 'exists:product_sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update display_order for each section
        foreach ($request->section_ids as $index => $sectionId) {
            ProductSection::where('id', $sectionId)->update(['display_order' => $index]);
        }
        
        $sections = ProductSection::orderBy('display_order', 'asc')->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product sections reordered successfully',
            'data' => $sections
        ]);
    }
}
