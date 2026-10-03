<?php
$dir = new RecursiveDirectoryIterator("app/Http/Controllers");
foreach(new RecursiveIteratorIterator($dir) as $file) {
    if ($file->getExtension() == "php") {
        $c = file_get_contents($file->getPathname());
        $c = str_replace("where('area_id', auth()->user()->area_id)", "whereIn('area_id', auth()->user()->getAccessibleAreaIds())", $c);
        file_put_contents($file->getPathname(), $c);
    }
}
