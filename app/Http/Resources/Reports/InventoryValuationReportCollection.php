<?php

namespace App\Http\Resources\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class InventoryValuationReportCollection extends ResourceCollection
{
    /**
     * @var class-string
     */
    public $collects = InventoryValuationReportResource::class;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct($resource, private readonly array $meta = [])
    {
        parent::__construct($resource);
    }

    /**
     * Metadata tambahan ditaruh di dalam `meta` agar kontrak root (data/links) tetap sama.
     */
    public function with(Request $request): array
    {
        return [];
    }

    public function paginationInformation($request, $paginated, $default): array
    {
        $default['meta'] = array_merge($default['meta'] ?? [], $this->meta);

        return $default;
    }
}
