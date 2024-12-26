<?php
namespace iboxs\ossfile;

class OSS
{
    public static function install(){
        if(function_exists('config_path')){
            $path=config_path();
            if(!file_exists($path.'/ossfile.php')){
                copy(__DIR__.'/../test/config.php',$path.'/ossfile.php');
            }
        }
        return true;
    }
}