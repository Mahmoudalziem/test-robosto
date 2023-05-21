<?php

namespace Webkul\Admin\Http\Resources\SmsCampaign;

use App\Http\Resources\CustomResourceCollection;

class SmsCampaignAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($smsCampaign) {
                    $sendTo = [];
                    if (count($smsCampaign->tags) > 0) {
                        $sendTo = $smsCampaign->tags;
                    } else {

                        if (count($smsCampaign->customers) > 20) {
                            $customers = $smsCampaign->customers->pluck('name');
                            $sendTo = array_slice($customers->toArray(), 0, 20); // display 20 customers at the most.
                        } else {
                            $sendTo = $smsCampaign->customers->pluck('name');
                        }
                    }

                    return [
                'id' => $smsCampaign->id,
                'created_by' => $smsCampaign->createdBy ? $smsCampaign->createdBy->name : null,
                'content' => $smsCampaign->content,
                'tags' => $smsCampaign->tags,
                'send_to' => $sendTo,
                'filter' => $smsCampaign->filter,
                'scheduled_at' => $smsCampaign->scheduled_at,
                'is_pushed' => $smsCampaign->is_pushed,
                'created_at' => $smsCampaign->created_at,
                'updated_at' => $smsCampaign->updated_at,
                    ];
                });
    }

}
