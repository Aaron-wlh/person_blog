<?php
// +----------------------------------------------------------------------
// | 99PHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2020 https://www.99php.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Mr.Chung <chung@99php.cn >
// +----------------------------------------------------------------------

namespace app\blog\controller;


use app\common\controller\BlogController;
use app\common\service\MailService;

class Login extends BlogController {

    /**
     * 模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Login constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('Member');
        $action = $this->request->action();
        if (!empty(session('member.id')) && $action !== 'out' && $action !== 'change') return $this->redirect('@blog');
    }

    /**
     * 博客登录
     * @return mixed|\think\response\Json
     */
    public function index() {
        if (!$this->request->isPost()) {
            isset($_SERVER['HTTP_REFERER']) && session('lastLink', $_SERVER['HTTP_REFERER']);
            $basic_data = [
                'title' => '登录||PHP社区',
            ];
            return $this->fetch('index_open', $basic_data);
        } else {
            $post = $this->request->post();

            //验证参数
            $validate = $this->validate($post, 'app\blog\validate\Login.index');
            if (true !== $validate) return __error($validate);

            //判断登录是否成功
            $login = $this->model->login($post['username'], $post['password']);
            if ($login['code'] == 1) return __error($login['msg']);

            //记录登录时间
            $login['member']['login_at'] = time();

            //储存session数据
            session('member', $login['member']);

            //记录登录日志
            $this->LoginRecord(1);

            return __success($login['msg']);
        }
    }

    /**
     * 用户注册
     */
    public function register() {
        if (!$this->request->isPost()) {
            //基础数据
            $basic_data = [
                'title' => '注册||博客',
            ];
            return $this->fetch('', $basic_data);
        } else {

            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\blog\validate\Login.register');
            if (true !== $validate) return __error($validate);
            //接口限制
            if(!api_limit('register', 2, 3600)) {
                return __error('在规定时间内接口请求超过限制');
            }

            //密码加密
            $post['password'] = password($post['password']);
            //邮箱验证字段 1:没有验证邮箱  2:已经验证
            $post['activated'] = 1;
            $post['activation_token'] = getRandChar(30);

            //保存数据,返回结果
            $res = $this->model->register($post);
            $resArr = json_decode($res->getContent(), true);
            //注册失败
            if($resArr['code'] == 1) {
                return $res;
            }
            //发送邮件
            MailService::send($post['email'], '注册验证邮件', email_template(request()->domain(). url('blog/login/confirmEmail', ['token' => $post['activation_token']])));
            return $res;
        }
    }

    public function confirmEmail($token) {
        $user = $this->model->where('activation_token', '=', $token)
            ->find();
        if(!$user) {
            $this->error('没找到对应的用户', url('index/index'));
        }
        if($user->save(['activated' => 2, 'activation_token' => ''])) {
            //记录登录时间
            $user->login_at = time();

            //储存session数据
            session('member', $user);

            //记录登录日志
            $this->LoginRecord(1);
            $this->success('邮箱验证成功', url('index/index'));
        }
    }


    /**
     * 切换账户
     * @return mixed
     */
    public function change() {
        if ($this->request->isGet()) {

            //基础数据
            $basic_data = [
                'title' => '久久PHP社区登录',
                'data'  => '',
            ];
            $this->assign($basic_data);

            return $this->fetch('index');
        }
    }

    /**
     * 退出登录
     * @return \think\response\Json
     */
    public function out() {

        //记录退出登录日志
        $this->LoginRecord(0);

        //清空sesion数据
        session('member', null);

        return __success('退出登录成功');
    }
}