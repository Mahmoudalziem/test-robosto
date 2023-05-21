<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Support\Arr;

class CustomPaginatedResourceResponse extends PaginatedResourceResponse
{
   protected function paginationInformation($request)
    {
        $paginated = $this->resource->resource->toArray();
        return Arr::except($paginated, [
            'data'
        ]);
    }
}
