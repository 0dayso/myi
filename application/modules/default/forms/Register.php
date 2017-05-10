<?php
class Default_Form_Register extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('user');
		$uname = new Zend_Form_Element_Text('uname');
		$uname->setLabel('用户名：')->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty');
		$uname->setAttrib('class','validate[required,custom[noSpecialCaracters],length[2,20],ajax[ajaxUname]] reinput fl');
		
		$email = new Zend_Form_Element_Text('email');
		$email->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty')
		->addValidator('EmailAddress',true,array('messages' => array(
				'emailAddressInvalid'         => '这不是一个可用的电子邮件!',
				'emailAddressInvalidHostname' => '这不是一个有效的电子邮件地址!',
				'emailAddressInvalidMxRecord' => '这不是一个有效的电子邮件地址!',
				'emailAddressDotAtom'         => '这不是一个有效的电子邮件地址!',
				'emailAddressQuotedString'    => '这不是一个有效的电子邮件地址!',
				'emailAddressInvalidLocalPart'=> '这不是一个有效的电子邮件地址!',
				'hostnameUnknownTld'          =>'',
				'hostnameLocalNameNotAllowed' =>'')));
		$email->setAttrib('class','validate[required,custom[email],ajax[ajaxEmail]] reinput fl');
		
		$password = new Zend_Form_Element_Password('password');
		$password->setLabel('登录密码：')
		->setRequired(true)
		->addValidator('NotEmpty');
		$password->setAttrib('class','validate[required,length[6,25]] text-input w-200');
		
		$password2 = new Zend_Form_Element_Password('password2');
		$password2->setLabel('确认密码：')
		->setRequired(true)
		->addValidator('NotEmpty');
		$password2->setAttrib('class','validate[required,confirm[password]] text-input w-200');

		$this->addElements(array('uname'=>$uname,'email'=>$email,'password'=>$password,'password2'=>$password2));
	}
	public function checkUser()
	{
		return true;
	}
}