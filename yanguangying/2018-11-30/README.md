# 与new相关的优先级问题

## 问题：代码如下
```
    var provider = {
        test: {
            $get: function(){
                return function anonymous(config){
                    console.log(this)
                };
            }
        }
    };
    var type = "test";
    var config = {};
    
    new provider[type].$get()(config);

    // Window {postMessage: ƒ, blur: ƒ, focus: ƒ, close: ƒ, parent: Window, …}
```

## 描述:
    用anonymous函数为构造函数创建实例，但是用上面的写法得到的this指向了window。

## 相关:
1. 构造函数的返回：JavaScript构造函数中可以返回值，也可以不返回值，比如:

    ```
        function Person(){
        }
        var person = new Person()
    ```

     我们知道这个时候构造函数返回的是创建的实例对象，也就是构造函数中this所指向的对象。

     但是当你构造函数有返回值时，就要分情况区分。
    - 如果返回的是一个非引用类型的值时，实际上返回的是仍然是新创建的实例对象。
    - 但是当返回的是一个引用类型的值时，返回的是引用对象本身。比如:

    ```
            function Person(){
                return function(){}
            }
            var person = new Person()
            typeof person // "function"
    ```

2. new 的语法： `new constructor[([arguments])]`
    - new执行的构造函数含参数
    - new执行的构造函数不含参数

    对于不含参数的构造函数而言:new Person()与new Person 二者并无区别，

    但是对于含参数的构造函数而言：new Person()到底执行的是new Person()还是是(new Person)()呢。

    从MDN的运算符优先级的表可以看到：带有参数的new操作符的优先级大于无参数列表的new操作符。因此总是会执行第一种而不是第二种。

    **注：带括号就是带参数** 

## 结论:

>  `new provider[type].$get()(config)` ， JavaScript引擎解析成: `(new provider[type].$get())(config)`。


## 解析：被new的到底是啥：
关键在于 `provider[type]` ，`provider[type]`不带参数，在执行代码的时候，`new provider[type]`组成不带参数的new运算符 与`provider[type]`后面的`"."`运算符比较优先级,`"."`优先级高于前者，所以先执行`provider[type].$get()`,`provider[type].$get()`返回函数anonymous,至此运行到关键位置。

用上面的逻辑判断`new anonymous(fonfig)`被引擎解析成`(new anonymous)(fonfig)`还是`new (anonymous(fonfig))`;
很明显了，anonymous是有参数的，所以先执行new操作，故解析成了`(new anonymous)(fonfig)`，所以最后得到的结果是anonymous(config);
所以this指向了window。



### [原文链接](https://juejin.im/post/5bfa94eb6fb9a04a0c2e1c62) 
### [MDN运算符优先级](https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Operators/Operator_Precedence)    