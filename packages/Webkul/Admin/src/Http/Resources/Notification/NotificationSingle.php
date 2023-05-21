<?php

namespace Webkul\Admin\Http\Resources\Notification;


use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\SubCategory;

class NotificationSingle extends JsonResource
{
    protected $append;
    public function __construct($resource, $append = null)
    {
        $this->append = $append;
        parent::__construct($resource);
    }
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'body'         => $this->body,
            'scheduled_at'      => $this->scheduled_at ? $this->scheduled_at : $this->created_at->format('Y-m-d H:i:s'),
            'published_now'      => $this->scheduled_at == null ? true : false,
            'tags'    => $this->tags,
        ];
    }
}
