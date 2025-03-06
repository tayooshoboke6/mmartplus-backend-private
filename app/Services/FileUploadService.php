<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Store an uploaded file and return the file path.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @param  string|null  $filename
     * @return string
     */
    public function store(UploadedFile $file, string $directory = 'uploads', string $filename = null): string
    {
        // Generate a unique filename if not provided
        if (is_null($filename)) {
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
        }
        
        // Store the file in the specified directory
        $path = $file->storeAs($directory, $filename, 'public');
        
        return $path;
    }
    
    /**
     * Delete a file from storage.
     *
     * @param  string  $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        return false;
    }
    
    /**
     * Get the full URL for a stored file.
     *
     * @param  string  $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
