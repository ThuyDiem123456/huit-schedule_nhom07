<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('CLI only');
$root=dirname(__DIR__);
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
$checked=0;
foreach($iterator as $file){
    $path=$file->getPathname();
    if (str_contains($path.DIRECTORY_SEPARATOR,'vendor'.DIRECTORY_SEPARATOR) || $file->getExtension()!=='php') continue;
    $output=[]; $result=0;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1',$output,$result);
    if($result!==0){echo implode("\n",$output)."\n";exit(1);}
    $checked++;
}
echo "PHP syntax OK for {$checked} project files.\n";
