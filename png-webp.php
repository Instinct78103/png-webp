<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '250006M');
set_time_limit(30000);

const UPLOADS_DIR = '../wp-content/uploads';

class WebpCreator
{
    public function run()
    {
        $files_png = $this->get_files_png();
        if (count($files_png)) {
            array_map(function ($item) {
                $this->create_webp_from_png($item);
            }, $files_png);
        }
    }

    protected function get_files_all($twenty_dirs_only = true): array
    {
        $scandir = scandir(UPLOADS_DIR);
        $files_all = [];

        if ($twenty_dirs_only) {
            $excludeDirs = array_filter($scandir, fn($dir) => !preg_match('~^20\d\d$~', $dir));

            $uploads_dir = new RecursiveDirectoryIterator(UPLOADS_DIR, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveCallbackFilterIterator($uploads_dir, function ($file, $key, $iterator) use ($excludeDirs) {
                if ($iterator->hasChildren() && !in_array($file->getFileName(), $excludeDirs)) {
                    return true;
                }
                return $file->isFile();
            });

            $iter = new RecursiveIteratorIterator($files);
            foreach ($iter as $file) {
                $files_all[] = $file->getPathname();
            }
        }

        echo '<pre style="white-space: pre-wrap;">';
        echo 'Files Found: ' . count($files_all);
        echo '</pre>';

        return $files_all;
    }

    protected function get_files_png()
    {
        $files = $this->get_files_all();
        $png = [];
        if (count($files)) {
            $png = array_filter($files, fn($item) => preg_match('~\.png$~', $item));
        }

        echo '<pre style="white-space: pre-wrap;">';
        echo '<hr>';
        echo 'png-files found: ' . count($png);
        echo '</pre>';

        return $png;
    }

    protected function create_webp_from_png($png)
    {
        if (preg_match('~\.png$~', $png)) {
            $pngimg = imagecreatefrompng($png);

            // get dimens of image
            $w = imagesx($pngimg);
            $h = imagesy($pngimg);;

            // create a canvas
            $im = imagecreatetruecolor($w, $h);
            imageAlphaBlending($im, false);
            imageSaveAlpha($im, true);

            // By default, the canvas is black, so make it transparent
            $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);

            // copy png to canvas
            imagecopy($im, $pngimg, 0, 0, 0, 0, $w, $h);

            // lastly, save canvas as a webp
            imagewebp($im, str_replace('png', 'webp', $png));

            // done
            imagedestroy($im);
        }
    }
}

(new WebpCreator)->run();