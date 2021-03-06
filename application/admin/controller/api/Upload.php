<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\admin\controller\api;


use app\common\controller\AdminController;
use app\common\service\QiniuService;
use think\Db;

/**
 * 后台上传通用接口
 * Class Upload
 * @package app\admin\controller\api
 */
class Upload extends AdminController {

    /**
     * 编辑器多图片上传
     * @return \think\response\Json
     */
    public function image() {
        $files = request()->file();
        if (is_array($files)) {
            foreach ($files as $vo) {
                $info = $vo->move('../public/static/uploads');
                if ($info) {
                    $url[] = '/static/uploads/' . date('Ymd') . '/' . $info->getFilename();
                } else {
                    return json(['code' => 1, 'msg' => $vo->getError()]);
                }
            }
        } else {
            $info = $files->move('../public/static/uploads');
            if ($info) {
                $url[] = '/static/uploads/' . date('Ymd') . '/' . $info->getFilename();
            } else {
                return json(['code' => 1, 'msg' => $files->getError()]);
            }
        }
        //判断是否使用七牛云上传
        $file_type = Db::name('SystemConfig')->where(['name' => 'FileType', 'group' => 'file'])->value('value');
        if ($file_type == 2) {
            foreach ($url as &$vo) {
                $vo = QiniuService::upload($vo);
            }
        }
        return json(['code' => 0, 'msg' => '上传成功！', 'url' => $url]);
    }


    /**
     * markdown单图上传
     * @return \think\response\Json
     */
    public function oneImage()
    {
        $files = request()->file('editormd-image-file');
        if(empty($files))  return json(['success' => 0, 'message ' => '请上传图片']);
        $info = $files->validate(['ext' => 'jpg,png,gif,JPG,PNG,GIF'])->move('../public/static/uploads');
        if ($info) {
            $url = '/static/uploads/' . date('Ymd') . '/' . $info->getFilename();
        } else {
            return json(['success' => 0, 'message ' => $files->getError()]);
        }

        //判断是否使用七牛云上传
        $file_type = Db::name('SystemConfig')->where(['name' => 'FileType', 'group' => 'file'])->value('value');
        if ($file_type == 2) {
            $url = QiniuService::upload($url);
        }
        return json(['success' => 1, 'message ' => '上传成功', 'url' => $url]);
    }

}