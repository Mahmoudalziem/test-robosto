<?php

namespace Webkul\Core\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Container\Container as App;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use Intervention\Image\Facades\Image;


abstract class Repository extends BaseRepository implements CacheableInterface {

    use CacheableRepository;

    /**
     * Find data by field and value
     *
     * @param  string  $field
     * @param  string  $value
     * @param  array  $columns
     * @return mixed
     */
    public function findOneByField($field, $value = null, $columns = ['*'])
    {
        $model = $this->findByField($field, $value, $columns = ['*']);

        return $model->first();
    }

    /**
     * Find data by field and value
     *
     * @param  string  $field
     * @param  string  $value
     * @param  array  $columns
     * @return mixed
     */
    public function findOneWhere(array $where, $columns = ['*'])
    {
        $model = $this->findWhere($where, $columns);

        return $model->first();
    }

    /**
     * Find data by id
     *
     * @param int $id
     * @param array $columns
     * @return mixed
     * @throws RepositoryException
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * Find data by id
     *
     * @param  int  $id
     * @param  array  $columns
     * @return mixed
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

     /**
     * Count results of repository
     *
     * @param  array  $where
     * @param  string  $columns
     * @return int
     */
    public function count(array $where = [], $columns = '*')
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $result = $this->model->count($columns);
        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /**
     * @param  string  $columns
     * @return mixed
     */
    public function sum($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $sum = $this->model->sum($columns);
        $this->resetModel();

        return $sum;
    }

    /**
     * @param  string  $columns
     * @return mixed
     */
    public function avg($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $avg = $this->model->avg($columns);
        $this->resetModel();

        return $avg;
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
        // First Decode Image from Base64
        list($extension, $content) = explode(';', $data[$type]);
        $tmpExtension = explode('/', $extension);
        preg_match('/.([0-9]+) /', microtime(), $m);
        $content = explode(',', $content)[1];
        $decoded_image = base64_decode($content);

        // Prepare Folder and Filename to Store in
        $folder = $modelName . '/' . $model->id . '/';
        $fileName = sprintf('Robosto-%s%s', date('YmdHis'), $m[1]);
        $thumbFileName = sprintf('Robosto-Thumb-%s%s', date('YmdHis'), $m[1]);

        // Final FOlder with Name
        $storedFolder = $folder . $fileName . '.webp';
        $storedThumbFolder = $folder . $thumbFileName . '.webp';
        $storeDisk = 'public';

        if ($model->{$type}) {
            Storage::delete($model->{$type});
        }
        // Encode image to Webp
        $webpImage = Image::make($decoded_image)->encode('webp');

        // Store Original Image
        Storage::put($storedFolder, $webpImage);

        // Save Image in DB
        $model->{$type} = $storedFolder;

        if ($createThumb) {
            // Resize Image
            $resized_image = $webpImage->resize(250, null, function ($constraint) {
                $constraint->aspectRatio();
            })->stream($tmpExtension[1]);
            $tableThumbName = 'thumb';
            // Store Image
            Storage::put($storedThumbFolder, $resized_image);

            // Save Image in DB
            $model->{$tableThumbName} = $storedThumbFolder;
        }
        // Fire Database Saving
        $model->save();
    }


    /**
     * @param $data
     * @param $model
     * @param string $type
     * @param bool $createThumb
     * @return mixed
     */
    protected function saveImgBase64WithoutWebP($data, $model, $type = 'image', $createThumb = false)
    {
        if (!$data[$type])
            return false;

        $modelName = strtolower(class_basename($model));
        // First Decode Image from Base64
        list($extension, $content) = explode(';', $data[$type]);
        $tmpExtension = explode('/', $extension);
        preg_match('/.([0-9]+) /', microtime(), $m);
        $content = explode(',', $content)[1];
        $decoded_image = base64_decode($content);

        // Prepare Folder and Filename to Store in
        $folder = $modelName . '/' . $model->id . '/';
        $fileName = sprintf('Robosto-%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);
        $thumbFileName = sprintf('Robosto-Thumb-%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);

        // Final FOlder with Name
        $storedFolder = $folder . $fileName;
        $storedThumbFolder = $folder . $thumbFileName;
        
        if ($model->{$type}) {            
            Storage::delete($model->{$type});
        }
        // Make image
        $imageMaked = Image::make($decoded_image);

        // Store Original Image
        Storage::put($storedFolder, $decoded_image);

        // Save Image in DB
        $model->{$type} = $storedFolder;

        if ($createThumb) {
            // Resize Image
            $resized_image = $imageMaked->resize(250, null, function ($constraint) {
                $constraint->aspectRatio();
            })->stream($tmpExtension[1]);
            $tableThumbName = 'thumb';
            // Store Image
            Storage::put($storedThumbFolder, $resized_image);

            // Save Image in DB
            $model->{$tableThumbName} = $storedThumbFolder;
        }
        // Fire Database Saving
        $model->save();
    }


    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }
}
