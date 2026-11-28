<?php

namespace App\Actions\MobileAppReleases;

use App\Http\Requests\MobileAppReleases\IndexMobileAppReleaseRequest;
use App\Models\MobileAppRelease;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexMobileAppReleaseAction
{
    public function execute(IndexMobileAppReleaseRequest $request): LengthAwarePaginator
    {
        $query = MobileAppRelease::query()->with('uploader');

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';

            $query->where(function ($builder) use ($search) {
                $builder->where('version_name', 'like', $search)
                    ->orWhere('notes', 'like', $search);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderByDesc('version_code')
            ->paginate($request->integer('per_page', 20));
    }
}
