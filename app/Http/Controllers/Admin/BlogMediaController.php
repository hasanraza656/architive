<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Blog\ImageStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pictures dropped or pasted into the article editor are uploaded here and inserted by address (never as heavy inline data). */
class BlogMediaController extends Controller
{
    public function upload(Request $request, ImageStore $images): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192']], [
            'image.image' => 'That file is not a picture.',
            'image.max' => 'That picture is bigger than 8 MB.',
        ]);

        try {
            $path = $images->store($request->file('image'), 'blog');
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['url' => asset($path), 'path' => $path], 201);
    }
}
