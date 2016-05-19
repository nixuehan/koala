<?php
namespace Valid;

/**
 * 验证手机号
 */
function mobile($phone) {
	if(!preg_match("/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])[0-9]{8}$/",$phone)){
		\Facile::json(\coreState::BREAK_REQUEST);
	}
    return $phone;
}

/**
 * 验证密码格式
 * (不能纯数字，长度6-16，可以用数字，大小写英文，各种符号)
 */
function passwd($passwd) {
    // !preg_match("/^\d+$/",$passwd) 去掉6个纯数字
    if (preg_match('/^[\dA-Za-z(\`\~\!\@\#\$\%\^\&\*\(\)\_\-\+\=\{\}\[\]\\\\|\:\;\'\"\,\.\/\<\>\?\)]{6,16}$/', $passwd)) {
            return $passwd;
        }
    return \Facile::json(\coreState::BREAK_REQUEST);
}

/**
 * 必须字段
 * @param  mixed $str 
 */
function required($str){
	if(empty($str)){
        exit('exit!');
    }

    return is_numeric($str) ? intval($str)  : strval($str);
}