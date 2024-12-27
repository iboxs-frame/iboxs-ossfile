<?php
namespace iboxs\ossfile;

use App\Common\RabbitMQ;
use iboxs\basic\Basic;
use iboxs\redis\Redis;

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

    public static function hasFile($key){
        $path=config('ossfile.path');
        $saveFile=$path.$key;
        return file_exists($saveFile);
    }

    /**
     * 存入文件
     * @param string $key 文件Key
     * @param string $file 要存入的文件路径或文件二进制数据
     * @param bool $isData 是否为文件二进制数据
     * @return bool
     */
    public static function saveFile($key,$file,$isData=false){
        $path=config('ossfile.path');
        if(!is_dir($path)){
            mkdir($path,0777,true);
            chmod($path,0777);
        }
        $saveFile=$path.$key;
        if(file_exists($saveFile)){
            unlink($saveFile);
        }
        $pathInfo=pathinfo($saveFile);
        if(!is_dir($pathInfo['dirname'])){
            mkdir($pathInfo['dirname'],0777,true);
            chmod($pathInfo['dirname'],0777);
        }
        if($isData){
            file_put_contents($saveFile,$file);
        }else{
            copy($file,$saveFile);
        }
        return true;
    }

    /**
     * 删除文件
     */
    public static function delFile($key){
        $path=config('ossfile.path');
        $saveFile=$path.$key;
        if(file_exists($saveFile)){
            unlink($saveFile);
        }
        return true;
    }

    /**
     * 获取文件Token
     */
    public static function getFileUrl($key,$expire=3600){
        $token=md5($key.config('ossfile.secret').microtime(true). Basic::GetRandStr(8));
        $cacheKey='iboxs:ossfile:token:'.$token;
        Redis::basic()->set($cacheKey,[
            'key'=>$key
        ],$expire+10);
        $url=config('ossfile.domain').trim($key,'/')."?ft=".$token;
        return $url;
    }

    public static function getFilePath($key){
        $path=config('ossfile.path');
        $saveFile=str_replace('//','/',$path.$key);
        return $saveFile;
    }

    public static function putFile($url,$token){
        if(Basic::isEmpty($token)){
            if(request()->isAjax()){
                return json(['code'=>-403,'msg'=>'token错误']);
            }
            return 'token错误';
        }
        $cacheKey='iboxs:ossfile:token:'.$token;
        $cache=Redis::basic()->get($cacheKey);
        if($cache==null){
            if(request()->isAjax()){
                return json(['code'=>-403,'msg'=>'token错误']);
            }
            return 'token错误';
        }
        $key=trim($cache['key'],'/');
        if($key!=$url){
            if(request()->isAjax()){
                return json(['code'=>-403,'msg'=>'token错误']);
            }
            return 'token错误';
        }
        $filePath=self::getFilePath($url);
        if(!$filePath){
            return response('文件不存在','404');
        }
        return response()->file($filePath);
    }
}
