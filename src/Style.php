<?php
/**
 * Created by PhpStorm.
 * User: 86182
 * Date: 2021/7/3
 * Time: 16:58
 */

namespace LTCSV;

use LTCSV\Help\AttrHelper;

/*
 * 基础样式类
 * */
class Style
{
    use AttrHelper;

    /*
     * @var string 颜色
     * */
    public $color;

    /*
     * @var string 对其方式
     * */
    public $align;

    /*
     * @var string 格式化
     * */
    public $format;
}