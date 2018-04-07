<?php
require '/home/hanggecrazy/vendor/autoload.php';
class Bot extends Baidu\Duer\Botsdk\Bot{
	public function __construct($postData = []) {
		parent::__construct($postData); 
		/* DuerOS和技能之间通讯需要进行签名验证，PHP需要开启open ssl扩展*/
		$this->certificate->enableVerifyRequestSign();
	}
}

/**
 * 通过addHandler函数创建处理技能的意图函数。在构造函数中添加如下代码
 **/
use \Baidu\Duer\Botsdk\Card\TextCard;


$this->addIntentHandler('remind', function(){
	$remindTime = $this->getSlot('remind_time');
	if($remindTime) {
		$card = new TextCard('创建中');
		return [
			'card' => $card,
		];
	}
});


$this->addIntentHandler('remind', 'create');

public function create(){
	$remindTime = $this->getSlot('remind_time');
	if($remindTime) {
		$card = new TextCard('创建中');
		return [
			'card' => $card,
		];
	} 
}

?>
