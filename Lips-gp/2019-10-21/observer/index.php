<?php
use Observer\LogintimeObserver;
use Observer\User;
use Observer\PushObserver;

spl_autoload_register(function($class_name) {
    $class_file = realpath(dirname(__FILE__)."/../") .'/' . str_replace('\\','/', $class_name) . '.php';
    if (file_exists($class_file)) {
        include $class_file;
    } else {
        echo "加载文件失败";
    }
});

echo "假设现在用户已经登录成功\r\n";

//实例化用户类
$user = new User();

//添加观察者
$user->attach(new LogintimeObserver());
$user->attach(new PushObserver());

//被观察者（用户）登录成功后通知观察者
$user->login();