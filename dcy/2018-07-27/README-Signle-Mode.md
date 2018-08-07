## PHP面试题设计模式-单例模式

设计模式是在技术面试的时候经常会被问到的一个问题,特别会让你举例说明各种设计模式使用的场景  
适当的使用设计模式可以减轻我们的工作量,优化我们的代码  
设计模式的种类非常的多,常见的有单例,工厂,策略,观察者等模式。这里我们先来介绍下单例模式  
单例模式你可以想象成在一次http请求中只生产该类的一个对象(即只 new className一次)
   
   编码中我们经常会见到在进行数据库连接时会使用单例模式  
例如:在一次http请求中我们可能会对数据库进行N条操作,如果每一次都进行connect一次,很明显就 
会 
导致我们服务器资源的浪费,那么为了节约资源,我们就可以使用单例模式来实现同一次http请求  
只进行 
一次 
connect连接的情况。  
单例模式最重要的一点就是__construct方法要定义为私有,这样只能通过getInstance()方法来获得 
实例连接符  
在getInstance()方法中判断是否已经存在了连接符,如果存在就直接返回该连接符,否则进new className  
工作中我们会经常用到redis,下面我们就来看看redis怎么进行连接
```
<?php
    /**
     * Class Signle
     */
    class Single{
        //连接池
        private static $_instance = null;
        private $options = [
            'host' => '127.0.0.1', //地址
            'port' => 6379,    //端口
            'pass' => '',   //密码
        ];
        
        //定义为私有 防止外部直接实例化
        private function __construct(){
            try{
                //连接redis
                $connect = new Redis();
                $connect->connect($this->option['host],$thi->option['port']);
                //使用密码连接
                if('' != $this->options['pass']){
                    $connect->auth($this->options['pass']);
                }
            }catch(\Exception $e){
                 die($e->getMessage());
            }
        }
        
        //获取redis连接
        public static function getInstance(){
           //self::_instance为空时进行实例化
           if (is_null(self::$_instance)) {
               self::$_instance = new self();
           }
           return self::$_instance;
        }
    }
    
```