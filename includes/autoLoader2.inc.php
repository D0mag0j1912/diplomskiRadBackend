<?php
spl_autoload_register('myAutoLoader2');

function myAutoLoader2($className){
    $path = "../classes/";
    $extension = ".class.php";
    $fullPath = $path.$className.$extension;

    if(!file_exists($fullPath)){
        return false;
    }

    include_once $fullPath;
}
?>