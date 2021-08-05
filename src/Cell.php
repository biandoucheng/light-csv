<?php
/**
 * Created by PhpStorm.
 * User: 86182
 * Date: 2021/7/5
 * Time: 11:08
 */

namespace LTCSV;

use LTCSV\Style;

/*
 * 单元格
 * */
class Cell extends Style
{
    /*
     * @var string 坐标
     * */
    public $position;

    /*
     * @var mixed 值
     * */
    public $val;
}