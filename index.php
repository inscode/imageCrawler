<?php
/**
 * Created by PhpStorm.
 * User: inscode
 * Date: 2018/9/24
 * Time: 14:48
 */
require './vendor/autoload.php';

use QL\QueryList;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Demo
{
    public $access_key;
    public $secret_key;
    public $bucket;

    //参数初始化
    public function __construct()
    {
        //七牛云access_key
        $this->access_key = '七牛云access_key';
        //七牛云secret_key
        $this->secret_key = '七牛云secret_key';
        //七牛云存储空间
        $this->bucket = 'inscode';
    }

    /**
     * 主方法
     *
     * */
    public function inscode()
    {
        //todo 这是改为动态获取
        $url = "http://you.ctrip.com/article/detail/1080877.html";
        $this->getCrawlerPics($url);
    }

    private function getCrawlerPics($url)
    {
        $content = file_get_contents($url);
        $parseOrigin = parse_url($url);
        $host = $parseOrigin['host'];
        $rules = [
            'img1' => array('img', 'data-rt-src'),
            'img2' => array('img', 'data-src'),
            'img3' => array('img', 'src'),
        ];
        $data = QueryList::html($content)->rules($rules)->query()->getData();
        foreach ($data as $imgItem) {
            static $orderNum = 1;
            if ($imgItem['img1']) {
                $imgItem = $imgItem['img1'];
            } elseif ($imgItem['img2']) {
                $imgItem = $imgItem['img2'];
            } else {
                $imgItem = $imgItem['img3'];
            }
            $this->singleImgHandler($imgItem, $host, $orderNum);
            sleep(2);
            $orderNum++;
        }
    }

    private function singleImgHandler($imgInfo, $host, $orderNum)
    {
        $dirPath = "./images/$host/";
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        $parseInfo = parse_url($imgInfo);
        $imgUrl = '';
        if ($parseInfo['scheme']) {
            $imgUrl .= $parseInfo['scheme'] . "://";
        }
        $imgUrl .= $parseInfo['host'];
        $imgUrl .= $parseInfo['path'];

        if ($imgSize = getimagesize($imgUrl)) {
            //小图片过滤
            if ($imgSize[0] >= 320 && $imgSize[1] >= 320) {
                //保存到七牛的文件名
                $key = date("Y:m:d-H:i:s", time()) . '-' . $orderNum . '.jpeg';
                $imgData = file_get_contents($imgUrl);

                //保存到本地的的文件名
                $savePath = $dirPath . $key;

                //保存图片到本地
                file_put_contents($savePath, $imgData);
                $auth = new Auth($this->access_key, $this->secret_key);
                $token = $auth->uploadToken($this->bucket);
                $up = new UploadManager();
                $mime = 'image/jpeg';
                list($rest, $err) = $up->put($token, $key, $imgData, null, $mime);
                if ($err) {
                    file_put_contents("err.log", $imgUrl);
                } else {
                    echo $imgUrl . ' save success' . PHP_EOL;
                }
            } else {
                echo $imgUrl . "too small" . PHP_EOL;
                file_put_contents("size.log", $imgUrl . PHP_EOL);
            }
        } else {
            echo "getImageSize failed" . PHP_EOL;
            file_put_contents("getImageSize.log", $imgUrl . PHP_EOL);
        }
    }

    /**
     * 读取远程图片
     * @param $$imgUrl
     * @return mixed
     */
    protected function getImgData($imgUrl)
    {
        $ch = curl_init($imgUrl);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

        //读取图片信息
        $rawData = curl_exec($ch);
        curl_close($ch);

        return $rawData;
    }
}

$upTest = new Demo();

$upTest->inscode();
