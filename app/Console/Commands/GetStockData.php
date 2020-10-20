<?php

namespace App\Console\Commands;

use App\Models\StockInfo;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use QL\QueryList;

class GetStockData extends Command
{
    protected $signature = 'stock:get';

    protected $description = '获取股票数据';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        for ($i=700000;$i--;)
        {
            $url = "http://www.aigaogao.com/tools/history.html?s={$i}";

            $allPage = QueryList::getInstance()->get($url);

            $title = $allPage->find('title:eq(0)')->text();

            //处理stockId
            preg_match_all('/\d+/',$title,$tmp);

            $tmp = Arr::flatten($tmp);

            //说明不存在这个id
            if (empty($tmp)) continue;

            $stockId = current($tmp);

            //处理其他数据
            $searchId = 'ctl16_contentdiv';

            $table = $allPage->find("#{$searchId}>table");

            // 采集表头
            $tableHeader = $table->find('tr:eq(0)')->find('td')->texts();

            // 采集表的每行内容
            $tableRows = $table->find('tr:gt(0)')->map(function ($row) {
                return $row->find('td')->texts()->all();
            });

            //说明没数据
            if (empty($tableRows)) continue;

            foreach ($tableRows as $one)
            {
                $insert = [
                    'stockId' => $stockId,
                    'date' => 0,
                    'open' => 0.00,
                    'height' => 0.00,
                    'low' => 0.00,
                    'close' => 0.00,
                    'deal' => 0,
                    'dealMoney' => 0,
                    'upDownMoney' => 0.00,
                    'upDownPercent' => 0.00,
                ];

                //处理 date
                if (!empty($one[0]))
                {
                    $tmp = explode('/',trim($one[0]));
                    if (count($tmp) === 3) $insert['date'] = $tmp[2].$tmp[0].$tmp[1];
                }

                //处理 open
                if (!empty($one[1]))
                {
                    $tmp = trim($one[1]);
                    $insert['open'] = $tmp;
                }

                //处理 height
                if (!empty($one[2]))
                {
                    $tmp = trim($one[2]);
                    $insert['height'] = $tmp;
                }

                //处理 low
                if (!empty($one[3]))
                {
                    $tmp = trim($one[3]);
                    $insert['low'] = $tmp;
                }

                //处理 close
                if (!empty($one[4]))
                {
                    $tmp = trim($one[4]);
                    $insert['close'] = $tmp;
                }

                //处理 deal
                if (!empty($one[5]))
                {
                    $tmp = trim($one[5]);
                    $tmp = str_replace(',','',$tmp);
                    $insert['deal'] = $tmp;
                }

                //处理 dealMoney
                if (!empty($one[6]))
                {
                    $tmp = trim($one[6]);
                    $tmp = str_replace(',','',$tmp);
                    $insert['dealMoney'] = $tmp;
                }

                //处理 upDownMoney
                if (!empty($one[7]))
                {
                    $tmp = trim($one[7]);
                    $insert['upDownMoney'] = $tmp;
                }

                //处理 upDownPercent
                if (!empty($one[8]))
                {
                    $tmp = trim($one[8]);
                    $tmp = str_replace('%','',$tmp);
                    $insert['upDownPercent'] = trim($tmp);
                }

                //如果插入过了，就不插入
                $check = StockInfo::where('stockId',$stockId)->where('date',$insert['date'])->first();

                if (!empty($check)) continue;

                StockInfo::create($insert);
            }

            sleep(5);
        }











//        Schema::create('stock_info', function (Blueprint $table)
//        {
//            $table->increments('id')->unsigned()->comment('自增主键');
//            $table->string('stockId',8)->comment('股票id');
//            $table->integer('date')->unsigned()->comment('日期');
//            $table->decimal('open')->comment('开盘');
//            $table->decimal('height')->comment('最高');
//            $table->decimal('low')->comment('最低');
//            $table->decimal('close')->comment('收盘');
//            $table->bigInteger('deal')->unsigned()->comment('成交量');
//            $table->bigInteger('dealMoney')->unsigned()->comment('成交金额');
//            $table->decimal('upDownMoney')->comment('升跌金额');
//            $table->decimal('upDownPercent')->comment('升跌百分数');
//            $table->timestamps();
//            $table->index('stockId');
//            $table->engine='InnoDB';
//        });
    }
}
