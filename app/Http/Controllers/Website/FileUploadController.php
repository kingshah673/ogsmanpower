<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Website\FileReaderService;

class FileUploadController extends Controller
{
    public function upload(Request $request, FileReaderService $reader)
    {
        $request->validate([
            'file' => 'required|file|max:10240'
        ]);

        $result = $reader->process($request->file('file'));

        return response()->json([
            'success' => true,
            'reply'   => $result
        ]);
    }
}