<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $file = $request->file('image');
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path = 'products/' . now()->format('Y/m');

        $file->storeAs($path, $filename, 'public');

        $dimensions = @getimagesize($file->getRealPath());

        $media = Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path . '/' . $filename,
            'url' => Storage::disk('public')->url($path . '/' . $filename),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => $media->url,
        ]);
    }
}
