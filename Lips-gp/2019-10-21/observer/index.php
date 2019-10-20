<?php
use Observer\OntimeObserver;
use Observer\User;
use Observer\UserObserver;

spl_autoload_register(function($class_name) {
    $class_file = realpath(dirname(__FILE__)."/../") .'/' . str_replace('\\','/', $class_name) . '.php';
    if (file_exists($class_file)) {
        include $class_file;
    } else {
        echo "加载文件失败";
    }
});

//实例化主题类
$user = new User();

//添加观察者
$user->attach(new UserObserver());
$user->attach(new OntimeObserver());

//通知观察者
$user->notify();