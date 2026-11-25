<?php

namespace App\Http\Resources\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class AssetCollection extends ResourceCollection
{
    public $collects = AssetResource::class;

    /**
     * @param  array{as_of_date?: string, totals?: array<string, string>}|null  $summary  khusus laporan Asset Register
     */
    public function __construct($resource, private readonly ?array $summary = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if ($this->summary === null) {
            return [];
        }

        return [
            'summary' => $this->summary,
        ];
    }
}
