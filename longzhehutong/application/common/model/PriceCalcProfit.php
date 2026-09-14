<?php

namespace app\common\model;

use think\Model;

class PriceCalcProfit extends Model
{
    protected $name = 'price_calc_profit';

    protected $autoWriteTimestamp = 'integer';

    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;
}
