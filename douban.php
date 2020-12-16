<?php

namespace App\Http\Controllers;

use App\Http\Models\Robots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use QL\Ext\AbsoluteUrl;
use QL\Ext\CurlMulti;
use QL\QueryList;

class douban extends Controller
{
    protected string $platform = 'qq';

    public function __construct()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
    }

    public function index(Request $request)
    {
        // 注册插件
        $ql = QueryList::use([
            AbsoluteUrl::class, // 转换URL相对路径到绝对路径
            CurlMulti::class    // Curl多线程采集
        ]);

        // 获取文章列表链接集合，使用AbsoluteUrl插件转换URL相对路径到绝对路径.
        $rules = [
            'uid' => ['.name a', 'href'],
            'pic' => ['.pic a img', 'src'],
            'name' => ['.name a', 'text'],
        ];
        // 后面数组是分页码
        $data = $ql->get('https://www.douban.com/group/blabla/members', ['start' => 385], [
            'headers' => [
                'Referer' => 'https://www.douban.com/group/blabla',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
//                'Accept'     => 'application/html',
            ]
        ])->range('.member-list ul li')->rules($rules)->query()->getData();

        $list = $data->all();
        if (!count($list)) {
            throw new \Exception("列表为空");
        }

        $robots_list = [];
        foreach ($list as $item) {
            $_ = [];
            $item['uid'] = str_replace('/people/', '', parse_url($item['uid'])['path']);
            $_['userId'] = 'robots_' . strtolower(rtrim($item['uid'], '/'));
            $_['platformId'] = self::getPlatformId($this->platform, $_['userId']);
            $_['townName'] = $item['name'];
            $_['level'] = self::getRandLevel();
            $_['gender'] = self::getRandGender();

            // 保存头像
            $file_name = parse_url($item['pic']);
            $file_name = str_replace('/icon/', '', $file_name['path']);
            $ext = pathinfo($file_name)['extension'];
            $file = file_get_contents($item['pic']);
            $file_name = hash('md5', bin2hex($file)) . '.' . $ext;

            $file_url = storage_path('robots/headpic') . '/' . $file_name;
            if (!file_exists($file_url)) {
                Storage::disk('robots')->put($file_name, $file);
            }

            $_['headImgId'] = 'https://xxx.oss-cn-shenzhen.aliyuncs.com/robots/headpic/' . $file_name;

            $robots_list[$_['userId']] = $_;
        }

        if (empty($robots_list)) {
            throw new \Exception("列表为空");
        }

        $robots_list = array_values($robots_list);
        dd($robots_list);

        // 入库
        $model = new Robots;
        $insert = \DB::table($model->getTable())->insertOrIgnore($robots_list);

        if($insert === false) {
            throw new \Exception("入库失败");
        }

        $count = count($robots_list);

        throw new \Exception("入库成功 ($count) 条.");
    }

    protected static function getPlatformId($platform, $uid)
    {
        return $platform . '_' . $uid;
    }

    protected static function getRandLevel()
    {
        return mt_rand(10, 20);
    }

    protected static function getRandGender()
    {
        return mt_rand(0, 2);
    }
}
