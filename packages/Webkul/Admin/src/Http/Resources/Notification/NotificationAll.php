<?php

namespace Webkul\Admin\Http\Resources\Notification;

use App\Http\Resources\CustomResourceCollection;

class NotificationAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($notification) {

            return [
                'id'            => $notification->id,
                'title'          => $notification->title,
                'body'           => $notification->body,
                'scheduled_at'      => $notification->scheduled_at ? $notification->scheduled_at : $notification->created_at->format('Y-m-d H:i:s'),
                'published_now'      => $notification->scheduled_at == null ? true : false,
                'tags'      => $notification->tags
            ];
        });
    }
}
