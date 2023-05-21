<?php

namespace Webkul\Admin\Repositories\SmsCampaign;

use Webkul\Core\Eloquent\Repository;

class SmsCampaignRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return \Webkul\Core\Models\SmsCampaign::class;
    }

    public function list($request) {
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }
        
        // Search by is pushed
        if ($request->exists('is_pushed') && ( ($request['is_pushed'] != null) || ($request['is_pushed'] != '') )) {
            $query->where('is_pushed', $request['is_pushed']);
        }
        
        // Search by tag id
        if ($request->exists('tag_id') && !empty($request['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', '=', $request['tag_id']);
            });
        }      
        
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    /**
     * @param  array  $data
     * @return User
     */
    public function create(array $data) {

        $data['is_pushed'] = isset($data['scheduled_at']) && $data['scheduled_at'] ? 0 : 1;
        unset($data['filter']['phones']); // no need to save filtered phones in filter column in db

        $smsCampaign = $this->model->create($data);
    
        if (isset($data['tags']) && $data['tags']) {
            $smsCampaign->tags()->sync($data['tags']);
        }

        // send SMS to one customer or multiple customers 
        if (isset($data['customers']) && $data['customers']) {
            $smsCampaign->customers()->sync($data['customers']);
        }

        return $smsCampaign;
    }

}
