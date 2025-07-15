<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;

trait FileHandler
{
    public function upload($file, $path = '')
    {
        \Storage::disk('CDN')->makeDirectory($path);
        $hash_name = rand(1000000,9999999).time().$file->hashName();
        $original_path = $path . '/' . $hash_name;
        $img = Image::make($file);
//        $img->widen(300);
        $path =\Storage::disk('CDN')->put($path,$file);
        return $path;
    }


    public function uploadIntoSameServer($file, $path = '')
    {
        File::ensureDirectoryExists(storage_path('app/public/' . $path));
        $hash_name = rand(1000000,9999999).time().$file->hashName();
        $original_path = $path . '/' . $hash_name;
        $path = 'storage/' . $path;
        $img = Image::make($file);
        $path = $path . '/' . $hash_name;
//        $img->widen(300);
        $img->save(storage_path('app/public/' . $original_path));
        return $path;
    }

    public function download_file($path = '', $title = '')
    {
        $arr = explode('.', $path);
        $mimetype = $arr[count($arr) - 1];
        return response()->download($path, $title . '.' . $mimetype);
    }


    public function delete_file($path = '')
    {
        if (!is_null($path))
            @File::delete($path);
    }

    public function delete_dir($path = '')
    {
        @File::deleteDirectory($path);
    }

    public function loadArrayFromFile($path)
    {
        return File::getRequire($path);
    }

    public function CopyFileContent($src, $target)
    {
        if ($this->FileExists($src))
            File::copy($src, $target);
    }

    public function PutFileContent($path, $content)
    {
        File::put($path, $content);
    }

    public function GetFileContent($path): string
    {
        return File::get($path);
    }

    public function FileExists($path): bool
    {
        return File::exists($path);
    }
    public function UploadFile($file, $path = '',$disk = 'public')
    {


        ini_set('memory_limit', '-1');
        File::ensureDirectoryExists(storage_path('app/public/' . $path));
        $fileName = preg_replace('/\s+/', '_', time() . ' ' . $file->getClientOriginalName());

        if (\Storage::putFileAs($path, $file, $fileName)) {
            return "storage/".$path."/" . $fileName;
        }

        return false;
    }
    public function upload_files($file, $path = '')
    {

        \Storage::disk('CDN')->makeDirectory($path);
        return \Storage::disk('CDN')->put($path,$file);
    }
}
