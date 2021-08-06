<?php
/**
 * Created by PhpStorm.
 * User: 86182
 * Date: 2021/7/3
 * Time: 16:59
 */

namespace LTCSV;

use LTCSV\Help\StrHealper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * 导出类
 * */
class Export
{
    /*
     * @var string 导出名称
     * */
    protected $name = "download.csv";

    /*
     * @var object CSV实例
     * */
    protected $csv;

    /*
     * @var string 临时文件夹地址
     * */
    protected $dir = "/tmp";

    /*
     * @var string 临时文件地址
     * */
    protected $path = "";

    /**
     *@description 初始化
     *
     *@author biandou
     *@date 2021/7/5 13:52
     *@param CSV $csv CSV实例
     *@param string $name 下载输出名字
     */
    public function __construct(CSV $csv,string $name = 'download.csv',string $dir="")
    {
        $this->csv = $csv;
        $this->name = $name;

        if($dir) {
            $dir = rtrim($dir,'/\\');
            $this->dir = $dir;
        }

        #生成临时文件地址
        if(!is_dir($this->dir)) {
            $this->mkDir($this->dir);
        }

        $this->path = $this->dir . '/' . $this->name;
        if(is_file($this->path)) {
            unlink($this->path);
        }

        #设置地区信息
        setlocale(LC_ALL,'zh-CN');
    }

    /**
     *@description 生成新的文件夹
     *
     *@author biandou
     *@date 2021/8/5 9:43
     *@param string $dir 文件夹地址
     */
    public function mkDir(string $dir)
    {
        $dirs = explode('/',str_replace('\\','/',$dir));
        $fd = "";

        while ($dirs) {
            $d = array_shift($dirs);
            if(!$d) {
                continue;
            }

            if(!$fd) {
                $fd = $d;
            }else {
                $fd .= '/'.$d;
            }

            if(!is_dir($fd)) {
                mkdir($fd,"0755");
            }
        }
    }

    /**
     *@description 生成一个随机文件名
     *
     *@author biandou
     *@date 2021/8/5 9:55
     *@param string $name 真实文件名
     *
     *@return string
     */
    public function mkRandomFileName(string $name)
    {
        return StrHealper::getRandMd5Str($name);
    }


    /**
     *@description 写入数据
     *
     *@author biandou
     *@date 2021/7/5 19:20
     *@param array $rows 数据行
     */
    public function setCellValue(array $rows)
    {
        $this->csv->addAssocValues($rows);
    }

    /**
     *@description 设置指定横坐标的值
     *
     *@author biandou
     *@date 2021/7/12 17:57
     *@param array $rows [['row'=>1,'val'=>[]]...]
     */
    public function setCellValueWithRowIndex(array $rows)
    {
        $this->csv->addAssocValuesWithRowIndex($rows);
    }

    /**
     *@description 获取当前电子表的行数
     *
     *@author biandou
     *@date 2021/7/12 17:45
     *
     *@return int
     */
    public function getRowIndex():int
    {
        return $this->csv->getRowIndex();
    }


    /**
     *@description 下载
     *
     *@author biandou
     *@date 2021/7/5 16:31
     *
     *@return Response
     */
    public function download()
    {
        #根据不同的浏览器决定是否对名称编码
        $name = $this->name;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if(preg_match("/Chrome/i",$ua)) {
            $name = urlencode($name);
        }

        $headers = [
            "Content-Type" => "application/x-csv;charset=UTF-8",
            "Content-Disposition" => 'attachment;filename="'.$name.'"',
            "Cache-Control" => "max-age=0"
        ];

        return StreamedResponse::create(
            $this->stream(),
            200,
            $headers
        );
    }

    /**
     *@description 返回输出流
     *
     *@author biandou
     *@date 2021/8/5 10:46
     */
    public function stream()
    {
        $rows = &$this->csv->rows;

        return function () use (&$rows) {
            $content = "\xEF\xBB\xBF";

            while ($rows) {
                $ctx = join(',',array_shift($rows)). PHP_EOL;
                $content .= $ctx;
            }

            echo $content;
        };
    }

    /**
     *@description 写入文件文件
     *
     *@author biandou
     *@date 2021/7/5 18:14
     */
    public function write ()
    {
        $count = 0;
        $content = "\xEF\xBB\xBF";

        while ($this->csv->rows) {
            $ctx = join(',',array_shift($this->csv->rows)). PHP_EOL;
            $content .= $ctx;
            $count += 1;
            if($count >= 1000) {
                $flag = file_exists($this->path) ? FILE_APPEND : 0;
                file_put_contents($this->path,$content,$flag);
                $content = "";
                $count = 0;
            }
        }

        if($content) {
            $flag = file_exists($this->path) ? FILE_APPEND : 0;
            file_put_contents($this->path,$content,$flag);
        }

        $this->csv->resetRowAndColumn();
    }

}