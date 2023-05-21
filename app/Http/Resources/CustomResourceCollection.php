<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Log;

class CustomResourceCollection extends ResourceCollection
{

    protected function preparePaginatedResponse($request)
    {
        if ($this->preserveAllQueryParameters) {
            $this->resource->appends($request->query());
        } elseif (! is_null($this->queryParameters)) {
            $this->resource->appends($this->queryParameters);
        }
        (new CustomPaginatedResourceResponse($this));
        return (new CustomPaginatedResourceResponse($this))->toResponse($request);
    }


    public function toResponse($request)
    {
        if ($this->resource instanceof AbstractPaginator) {
            return $this->preparePaginatedResponse($request);
        }
        return parent::toResponse($request);
    }
}
