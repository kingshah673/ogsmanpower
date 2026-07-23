<?php

namespace App\Services\API\Website\Agency\Bookmark;

use App\Models\AgencyBookmarkCategory;
use F9Web\ApiResponseHelpers;
use Illuminate\Support\Facades\Validator;

class CreateBookmarkCategoryService
{
    use ApiResponseHelpers;

    public function create($request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $category = CompanyBookmarkCategory::create([
            'agency_id' => auth('sanctum')->user()->agency->id,
            'name' => $request->name,
        ]);

        return $this->respondWithSuccess([
            'data' => [
                'category' => $category,
                'message' => __('category_created_successfully'),
            ],
        ]);
    }
}
