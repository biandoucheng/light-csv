<?php
/**
 * Created by PhpStorm.
 * User: 86182
 * Date: 2021/7/3
 * Time: 15:36
 */

namespace LTCSV;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LTCSV\Help\ArrayHelper;

/*
 * 迭代缓存器
 *
 * */
class IteratorCache
{
    /*
 * @const 数据源类型 数组
 * */
    private const SOURCE_TYPE_ARRAY = "array";

    /*
     * @const 数据源类型 集合
     * */
    private const SOURCE_TYPE_COLLECTION = "collection";

    /*
     * @const 数据源类型 集合
     * */
    private const SOURCE_TYPE_DB = "db";

    /*
     * @const 数据源类型 模型
     * */
    private const SOURCE_TYPE_MODEL = "model";

    /*
     * @var string 数据源类型
     * */
    private $sourceType;

    /*
     * @var mixed 数据源 array,collect,Query Instance(DB|Model)
     * */
    protected $source;

    /*
     * @var array 数据段 每次弹出的一段数据
     * 这部分数据是一次性的，只要写入的单元格后就会被销毁，写一个销毁一个
     * */
    public $data;

    /*
     * @var array 回调函数数组,回调函数请采用引用传值
     * 回调函数是按照先后顺序调用的
     * eg:[
     * ['call'=>function(&$row,$a,$b,...){...},'params'=>[$a,$b,...]],
     * ...
     * ]
     * */
    protected $callArray;

    /*
     * @var int 每次迭代数据最大条数
     * */
    protected $quantity = 1000;

    /*
     * 初始化
     * */
    public function __construct($source,array $callArray = [],int $quantity=1000)
    {
        $this->reset();
        $this->load($source,$callArray,$quantity);
    }

    /**
     *@description 重置类成员
     *
     *@author biandou
     *@date 2021/7/3 15:47
     */
    public function reset()
    {
        $this->source = null;
        $this->callArray = [];
        $this->data = [];
    }

    /**
     *@description 输入数据源
     *
     *@author biandou
     *@date 2021/7/3 15:42
     *@param mixed $source 数据源
     *@param array $callArray 回调函数列表
     */
    public function load($source,array $callArray = [],int $quantity=1000)
    {
        #设置数据源
        $this->source = $source;

        #数据类型检测
        if(is_array($source)) {
            $this->sourceType = self::SOURCE_TYPE_ARRAY;
        }else if($source instanceof Model) {
            $this->sourceType = self::SOURCE_TYPE_MODEL;
        }else if($source instanceof QueryBuilder || $source instanceof EloquentBuilder || $source instanceof Model) {
            $this->sourceType = self::SOURCE_TYPE_DB;
        }else if($source instanceof Collection) {
            $this->sourceType = self::SOURCE_TYPE_ARRAY;
            $this->source = $this->source->toArray();
            if(isset($this->source['data'])) {
                $this->source = $this->source['data'];
            }
        }else {
            $this->sourceType = self::SOURCE_TYPE_ARRAY;
            $this->source = (array)$this->source;
        }

        #设置回调函数
        foreach ($callArray as $call) {
            if(is_callable($call['call'])) {
                $call['params'] = $call['params'] ?? [];
                $this->callArray[] = $call;
            }
        }

        #每次输出数据条数
        if($quantity >= 0) {
            $this->quantity = $quantity;
        }
    }

    /**
     *@description 获取迭代器
     *
     *@author biandou
     *@date 2021/7/3 15:54
     *@param
     *
     *@return
     */
    public function iterator():iterable
    {
        switch ($this->sourceType) {
            case self::SOURCE_TYPE_ARRAY:
                if(!empty($this->source) && $this->source[0] instanceof \stdClass) {
                    $this->source = ArrayHelper::turnStdClassToArray($this->source);
                }
                return $this->iteratorArray();
            case self::SOURCE_TYPE_COLLECTION:
                $this->source = collect($this->source)->toArray();
                return $this->iteratorArray();
            case self::SOURCE_TYPE_MODEL:
                return $this->iteratorQuery();
            case self::SOURCE_TYPE_DB:
                return $this->iteratorQuery();
            default:
                return $this->iteratorArray();
        }
    }

    /**
     *@description 遍历数组数据
     *
     *@author biandou
     *@date 2021/7/3 15:53
     *@param
     */
    public function iteratorArray()
    {
        $index = 0;
        $out = [];

        while ($this->source){
            $index += 1;
            $out[] = array_shift($this->source);
            if($index >= $this->quantity || empty($this->source)) {
                $index = 0;
                yield $out;
                $out = [];
            }
        }
    }

    /**
     *@description 遍历Query实例数据
     *
     *@author biandou
     *@date 2021/7/3 15:53
     */
    public function iteratorQuery()
    {
        #数据库分片取出数据，并转化成数组返回
        do{
            #初始化
            if(empty($skip)) {
                $skip = 0;
            }

            #数据查询
            $items = $this->source->limit($this->quantity)->offset($skip)->get()->toArray();

            #数据整理
            if($items) {
                if($this->sourceType == self::SOURCE_TYPE_MODEL) {
                    #模型结果转数组
                    $items = $items->toArray();
                }else {
                    #DB结果转数组
                    $items = ArrayHelper::turnStdClassToArray($items);
                }
                #步长加一倍
                $skip += $this->quantity;

                yield $items;
            }
        }while($items);
    }

    /**
     *@description 执行回调
     *
     *@author biandou
     *@date 2021/7/3 16:22
     *@param array $rows 数据
     */
    public function runCallMembers(array &$rows)
    {
        foreach ($this->callArray as $call) {
            ($call['call'])($rows,...$call['params']);
        }
    }
}