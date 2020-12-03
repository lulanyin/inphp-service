<?php
// +----------------------------------------------------------------------
// | INPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2020 https://inphp.cc All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/MIT )
// +----------------------------------------------------------------------
// | Author: lulanyin <me@lanyin.lu>
// +----------------------------------------------------------------------
namespace Inphp\Service\Util;

class File{
    /**
     * 创建文件夹
     * @param string $path
     * @param string|null $base_path
     * @return bool
     */
    public static function makeDir(string $path, string $base_path = null) : bool {
        $path = $base_path ? self::filterPath($base_path.DS.$path) : self::filterPath($path);
        if(!is_dir($path)){
            if(!mkdir($path, 0777, true)){
                return false;
            }
        }
        return true;
    }

    /**
     * 获取文件列表
     * @param string $path
     * @param string $match
     * @param string|null $base_path
     * @return array
     */
    public static function getFiles(string $path, string $match, string $base_path = null) : array {
        $path = $base_path ? self::filterPath($base_path.DS.$path) : self::filterPath($path);
        if(is_dir($path)){
            //获取此路径下的所有文件
            $preg_match = "/\.(".$match.")$/i";
            $handle = opendir( $path );
            $files = [];
            while ( false !== ( $file = readdir( $handle ) ) ){
                if ( $file != '.' && $file != '..' && $file!="" && $file != '_notes'){
                    $path2 = $path .DS. $file;//路径
                    if(!is_dir( $path2 ) ){
                        if ( preg_match( $preg_match , $file ) ) {
                            $file = [
                                "filename"      => $file,
                                "md5"           => md5_file($path2),
                                "path"          => $path2,
                                "suffix"        => stripos($file, ".")>0 ? substr(strchr($file, "."), 1) : null,
                                "file_size"     => filesize($path2)
                            ];
                            $files[] = $file;
                        }
                    }
                }
            }
            @closedir($path);
            return $files;
        }
        return [];
    }

    /**
     * 获取文件夹
     * @param string $path
     * @param string|null $base_path
     * @return array
     */
    public static function getDirs(string $path, string $base_path = null){
        $path = $base_path ? self::filterPath($base_path.DS.$path) : self::filterPath($path);
        if(is_dir($path)){
            //获取此路径下的所有文件
            $handle = opendir( $path );
            $files = [];
            while ( false !== ( $file = readdir( $handle ) ) ){
                if ( $file != '.' && $file != '..' && $file!="" && $file != '_notes'){
                    $path2 = $path .DS. $file;//路径
                    if(is_dir( $path2 ) ){
                        $file = [
                            "name"  => $file,
                            "path"  => $path2
                        ];
                        $files[] = $file;
                    }
                }
            }
            @closedir($path);
            return $files;
        }
        return [];
    }

    /**
     * 修复路径
     * @param string $path
     * @return string
     */
    private static function filterPath(string $path) : string {
        $path = str_replace("\\", "/", $path);
        $path = str_replace("//", "/", $path);
        return $path;
    }

    /**
     * 判断文件是否存在
     * @param string $file
     * @return bool
     */
    public static function exists(string $file){
        return file_exists($file);
    }

    /**
     * 往文件末尾添加内容
     * @param $path
     * @param $file
     * @param $content
     */
    public static function writeEnd($path, $file, $content){
        if(!is_dir($path)){
            self::makeDir($path);
        }
        //异步写入文件
        //swoole_async_write($path.DS.$file, $content, -1);
        $f = fopen($path.DS.$file, "a");
        if($f){
            @fwrite($f, $content);
            @fclose($f);
        }
    }

    /**
     * 组合路径，目录后的/不是必须
     *
     * @param string ...$args
     * @return string
     */
    public static function path(...$args)
    {
        static $dsds = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
        $result = implode(DIRECTORY_SEPARATOR, $args);
        while(false !== strpos($result, $dsds))
        {
            $result = str_replace($dsds, DIRECTORY_SEPARATOR, $result);
        }
        return $result;
    }

    /**
     * 获取某个文件夹下所有的文件
     * @param string $path
     * @param string $match
     * @param null|string $base_path
     * @return array
     */
    public static function getAllFiles(string $path, string $match, string $base_path = null){
        $files = self::getFiles($path, $match, $base_path);
        $dirs = self::getDirs($path, $base_path);
        foreach ($dirs as $dir){
            $files = array_merge($files, self::getAllFiles($path."/".$dir['name'], $match, $base_path));
        }
        return $files;
    }

    /**
     * 清空某个文件夹
     * @param string $path
     * @param bool $delete
     */
    public static function clearDir(string $path, $delete = false){
        if(is_dir($path)){
            //清除文件
            $files = scandir($path);
            if(count($files) > 0){
                foreach ($files as $file){
                    if($file != '.' && $file != ".."){
                        if(is_dir($path."/".$file)){
                            self::clearDir($path."/".$file, true);
                        }else{
                            unlink($path."/".$file);
                        }
                    }
                }
            }
            if($delete){
                rmdir($path);
            }
        }
    }

    /**
     * 删除某个文件夹
     * @param string $path
     */
    public static function deleteDir(string $path){
        self::clearDir($path, true);
    }

}