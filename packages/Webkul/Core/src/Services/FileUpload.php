<?php
namespace Webkul\Core\Services;


use Illuminate\Support\Facades\Storage;

class FileUpload
{

    public static function saveImgBase64($param, $folder)
    {
        list($extension, $content) = explode(';', $param);
        $tmpExtension = explode('/', $extension);
        preg_match('/.([0-9]+) /', microtime(), $m);
        $fileName = sprintf('img%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);
        $content = explode(',', $content)[1];
        $storage = Storage::disk();

        $checkDirectory = $storage->exists($folder);

        if (!$checkDirectory) {
            $storage->makeDirectory($folder);
        }

        $storage->put($folder . '/' . $fileName, base64_decode($content));

        return $folder.$fileName;
    }

    public static function updateImgBase64($param, $folder,$oldFileName=null)
    {
        list($extension, $content) = explode(';', $param);
        $tmpExtension = explode('/', $extension);
        preg_match('/.([0-9]+) /', microtime(), $m);
        $fileName = sprintf('img%s%s.%s', date('YmdHis'), $m[1], $tmpExtension[1]);
        $content = explode(',', $content)[1];
        $storage = Storage::disk('public');

        $checkDirectory = $storage->exists($folder);
        if (!$checkDirectory) {
            $storage->makeDirectory($folder);
        }

        if($oldFileName)
            self::deleteImg($oldFileName);

        $storage->put($folder . '/' . $fileName, base64_decode($content), 'public');
        return $folder.$fileName;
    }




    // upload any file
    public static function saveFile($param, $folder){
        $fileContent = pathinfo($param, PATHINFO_FILENAME);

        preg_match('/.([0-9]+) /', microtime(), $m);
        $fileName = sprintf('%s%s.%s', date('YmdHis'), $m[1], $param->getClientOriginalExtension());
        $storage = Storage::disk('public');

        $checkDirectory = $storage->exists($folder);
        if (!$checkDirectory) {
            $storage->makeDirectory($folder);
        }

        $storage->put(
            $folder . $fileName,
            $fileContent
        );

        return $folder.$fileName;

    }

    public static function saveAudio($param, $folder){
        $uploadedFile =$param;
        $filename = time().$uploadedFile->getClientOriginalName();

        $storage = Storage::disk('public');
        $checkDirectory = $storage->exists($folder);
        if (!$checkDirectory) {
            $storage->makeDirectory($folder);
        }
        return $storage->putFileAs(
            $folder ,
            $uploadedFile,
            $filename
        );
    }

    public static function deleteFile(  $fileName)
    {
        $storage = Storage::disk('public');
        if ($storage->exists($fileName))
            return $storage->delete( $fileName );
    }

    public static function deleteFolder($folder){
        $storage = Storage::disk('public');
        if ($storage->exists($folder))
            return $storage->deleteDirectory($folder);
    }

    public static function deleteImg(  $fileName)
    {
        $storage = Storage::disk('public');
        return $storage->delete( $fileName );
    }



}
