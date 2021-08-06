<?php
/**
 * Created by PhpStorm.
 * User: 86182
 * Date: 2021/7/5
 * Time: 11:36
 */

namespace LTCSV;

use LTCSV\IteratorCache;
use LTCSV\Help\AttrHelper;

/*
 * 电子表
 * */
class CSV
{
    use AttrHelper;

    /*
     * @var mixed 表数据源
     * */
    protected $cache;

    /*
     * @var array 导出数据缓存
     * */
    public $rows = [];

    /*
     * @var array 表头 [field=>zh]
     * */
    public $header = [];

    /*
     * @var bool 表头是否输出到报表中
     * */
    public $headerOut = true;

    /*
     * @var string 汇总位置 top | bottom
     * */
    protected $summaryPos = 'top';

    /*
     * @var int 数据行数,包含表头
     * */
    protected $rowIndex = 0;

    /*
     * @var int 数据列数
     * */
    protected $columnIndex = 0;

    /**
     *@description 初始化
     *
     *@author biandou
     *@date 2021/7/6 9:22
     *@param mixed $source 数据源
     *@param array $callArray 回调函数列表
     *@param int $quantity 单次处理的数据条数
     */
    public function __construct($source,array $callArray = [],int $quantity=1000)
    {
        $cache = new IteratorCache($source,$callArray,$quantity);
        $this->cache = $cache;
    }

    /**
     *@description 获取数据源
     *
     *@author biandou
     *@date 2021/7/5 13:49
     *
     *@return IteratorCache
     */
    public function getCache():IteratorCache
    {
        return $this->cache;
    }

    /**
     *@description 是否设置了表头信息
     *
     *@author biandou
     *@date 2021/7/5 15:44
     *@param
     *
     *@return
     */
    public function isHeaderEnable()
    {
        return is_array($this->header) && !empty($this->header);
    }

    /**
     *@description 表头是否要输出
     *
     *@author biandou
     *@date 2021/7/5 15:43
     *
     *@return bool
     */
    public function isHeaderOutEnable():bool
    {
        return $this->isHeaderEnable() && $this->headerOut;
    }

    /**
     *@description 数据行数+1
     *
     *@author biandou
     *@date 2021/7/6 10:52
     */
    public function addRowIndex()
    {
        $this->rowIndex += 1;
    }

    /**
     *@description 数据列数+1
     *
     *@author biandou
     *@date 2021/8/4 19:20
     */
    public function addColumnIndex()
    {
        $this->columnIndex += 1;
        if($this->columnIndex >= count($this->header)) {
            $this->columnIndex = 0;
        }
    }

    /**
     *@description 获取当前数据行数
     *
     *@author biandou
     *@date 2021/7/6 10:53
     *
     *@return int
     */
    public function getRowIndex():int
    {
        return $this->rowIndex;
    }

    /**
     *@description 获取当前数据列数
     *
     *@author biandou
     *@date 2021/8/4 19:22
     *@return
     */
    public function getColumnIndex():int
    {
        return $this->columnIndex;
    }

    /**
     *@description 添加一行数据值
     *
     *@author biandou
     *@date 2021/8/4 19:31
     *@param array $row 数组
     */
    public function addValue(array $row)
    {
        $item = [];

        #数组长度不够则填充
        if(count($row) < count($this->header)) {
            $row = array_pad($row,count($this->header),'');
        }

        foreach ($row as $val) {
            if(is_bool($val)) {
                $val = $val ? "true" : "false";
            }
            $item[] = '"' . $val . '"';
            $this->addColumnIndex();
        }

        $this->rows[] = $item;
        $this->addRowIndex();
    }

    /**
     *@description 方法作用
     *
     *@author biandou
     *@date 2021/8/4 19:35
     *@param array $rows 二维非关联数组
     */
    public function addValues(array $rows)
    {
        foreach ($rows as $row) {
            $this->addAssocValue($row);
        }
    }

    /**
     *@description 添加一行关联数据值
     *
     *@author biandou
     *@date 2021/8/4 19:31
     *@param array $row 关联数组
     */
    public function addAssocValue(array $row)
    {
        $item = [];

        #数组长度不够则填充
        if(count($row) < count($this->header)) {
            $row = array_pad($row,count($this->header),'');
        }

        foreach ($this->header as $column=>$zh) {
            if(isset($row[$column])) {
                if(is_bool($row[$column])) {
                    $row[$column] = $row[$column] ? "true" : "false";
                }
            }

            $item[] = isset($row[$column]) ? '"'.$row[$column].'"' : '""';
            $this->addColumnIndex();
        }

        $this->rows[] = $item;
        $this->addRowIndex();
    }

    /**
     *@description 添加多行关联数据值
     *
     *@author biandou
     *@date 2021/8/4 19:34
     *@param array $rows 二维关联数组
     */
    public function addAssocValues(array $rows)
    {
        foreach ($rows as $row) {
            $this->addAssocValue($row);
        }
    }


    /**
     *@description 从指定起始,行添加多行关联数据值
     *
     *@author biandou
     *@date 2021/8/5 9:16
     *@param int $rowIndex 起始行比索引大1
     *@param array $rows 二维关联数组
     */
    public function addAssocValuesWithRowIndex(array $rows)
    {
        foreach ($rows as $index=>$row) {
            $rowIndex = $row['row'];
            $val = $row['val'];
            $item = [];

            foreach ($this->header as $column=>$zh) {
                $item[] = isset($val[$column]) ? '"'.$val[$column].'"' : '""';
            }

            if($this->rowIndex >= $rowIndex) {
                $this->rows[$rowIndex - 1] = $item;
            }else {
                $this->rows[] = $item;
                $this->rowIndex += 1;
            }
        }
    }

    /**
     *@description 重置行和列数
     *
     *@author biandou
     *@date 2021/8/5 10:27
     */
    public function resetRowAndColumn()
    {
        $this->rowIndex = $this->columnIndex = 0;
    }
}