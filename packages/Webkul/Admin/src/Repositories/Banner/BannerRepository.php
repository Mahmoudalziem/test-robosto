<?php

namespace Webkul\Admin\Repositories\Banner;

use Carbon\Carbon;
use Webkul\Core\Rules\ImageBase64;
use Webkul\Product\Models\Product;
use Webkul\Banner\Contracts\Banner;
use Webkul\Category\Models\Category;
use Webkul\Core\Eloquent\Repository;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\SubCategory;

class BannerRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {

        return Banner::class;
    }

    public function list($section, $request)
    {

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
            $query = $query->orderBy('position', 'asc');
        }
        
        // Search by Status
        if ($request->exists('status') && ($request['status'] != null)) {
            $query->where('status', $request['status']);
        }        

        if ($section && !empty($section)) {
            $query->where('section', '=', $section);
        }

        // area  // source  // date1,date2
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->whereHas('area', function ($q) use ($request) {
                $q->where('id', '=', $request['area_id']);
            });
        }

        if ($request->exists('start_date') && !empty($request['start_date']) && $request->exists('end_date') && !empty($request['end_date'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['start_date'] . ' 00:00:00';
                $dateTo = $request['end_date'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        $perPage = $request->has('per_page') ? (int)$request->per_page : null;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;

    }

    public function create(array $data)
    {
        foreach ($data['area_id'] as $area) {
            $bannerFound = $this->findOneWhere( ['area_id' => $area,'section' => $data['section'], 'position' => $data['position']]);

            if ($bannerFound) {

                $bannerFound->update(['position' => $this->findWhere(['area_id' => $area,'section' => $data['section']])->count() + 1]);
            }
            unset($data['area_id']);
            $data['area_id']=(int)$area;
            $banner = $this->model->create($data);

            // Store image
            foreach (core()->getAllLocales() as $locale) {
                if (isset($data['image_'.$locale->code]) && !empty($data['image_'.$locale->code])) {
                    $this->saveImgBase64($data, $banner, 'image_'.$locale->code);
                }
            }
        }

        return $banner;
    }

    public function update(array $data, $id, $attribute = "id")
    {
        $banner = $this->findOrFail($id);

        // new postion
        $oldPosition= $banner->position;
        $newPosition= $data['position'];
        $areaId= isset($data['area_id'])? $data['area_id'] : $banner->area_id;
        $bannerToUpdate= $this->findOneWhere(['area_id' => $areaId,'section'=>$banner->section,'position'=>$newPosition]);

        // update other banner position

        if($bannerToUpdate){
            if($bannerToUpdate->area_id == $areaId){
                $bannerToUpdate->update(['position'=>$oldPosition,'area_id'=>$areaId]);
            }else{
                $bannerToUpdate->update(['position' => $this->findWhere(['area_id' =>$bannerToUpdate->area_id,'section' => $data['section']])->count() + 1]);
            }

        }

       // $banner = $this->update(['position'=>$newPosition], $banner->id);

        $banner->update($data);

        // Store image
        foreach (core()->getAllLocales() as $locale) {
            if (isset($data['image_'.$locale->code])  ) {
                $this->saveImgBase64($data, $banner, 'image_'.$locale->code);
            }
        }

        return $banner;
    }

    public function setStatus($bannerModle,$data){

        $bannerModle->update($data );
        
        return $bannerModle;
    }

    public function setDefault($bannerModle,$data){
        return $bannerModle->update($data );
    }


    /**
     * @param $data
     * @param $model
     * @param string $type
     * @param bool $createThumb
     * @return mixed
     */
    protected function saveImgBase64($data, $model, $type = 'image', $createThumb = false)
    {
        if(!$data[$type])
            return false;
        $modelName = strtolower(class_basename($model));
        
        /**
         * First Decode Image from Base64
         */
        list($extension, $content) = explode(';', $data[$type]);
        
        $tmpExtension = explode('/', $extension);
        
        preg_match('/.([0-9]+) /', microtime(), $m);
        
        $content = explode(',', $content)[1];
        
        // Prepare Folder and Filename to Store in
        $folder = $modelName . '/' . $model->id . '/';
        $fileName = sprintf('Robosto-%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);        
        // Final FOlder with Name
        $storedFolder = $folder . $fileName;

        if ($model->{$type}) {
            Storage::delete($model->{$type});
        }

        // Store Image
        Storage::put($storedFolder, base64_decode($content));

        // Save Image in DB
        $model->{$type} = $storedFolder;
        $model->save();
    }
}