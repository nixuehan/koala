<?php
namespace module;

class Member extends \Module{

    /**
     * 注册
     */
    public function register(Array $mobile) {

        if($userinfo = $this->existsByMobile('15007792365')){
            return false;
        }else{
            $this->db()->table('member')
                        ->insert([
                            'username' => 'meigui',
                            'mobile' => 15007792365,
                            'gender' => 1,
                            'avatar' => ''
                         ]);

            return true;
        }
    }

    /**
     * 用户是否存在
     */
    public function existsByMobile($mobile) {
        return $this->db()->table('member')
                            ->where("mobile = '%s'",$mobile)
                            ->has();
    }
}