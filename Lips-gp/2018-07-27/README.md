## foreach的引用赋值

我们先来做一道试题，进而思考一下foreach的引用赋值。

题目：请写出以下php语句执行输出的结果：

```php
<?php
    $arr = [1,2,3];  
    foreach($arr as &$value){
    	# code...
    }
    foreach($arr as $value){
    	# code...
    }
    print_r($arr);
```
	
答案：  
    Array ( [0] => 1 [1] => 2 [2] => 2 ) 

怎么样？这样的输出结果是如你所愿呢？还是出乎了意料呢？

***

那下面我们来分析一下为何会有这样的结果。如下：

```php
<?php
    $arr = [1,2,3];  
    foreach($arr as &$value){
    	var_dump($arr);
        echo "<br/>";
    }
    echo "<br />";
    foreach($arr as $value){
    	var_dump($arr);
        echo "<br/>";
    }
```
```
输出：
    array(3) { [0]=> &int(1) [1]=> int(2) [2]=> int(3) } 
    array(3) { [0]=> int(1) [1]=> &int(2) [2]=> int(3) } 
    array(3) { [0]=> int(1) [1]=> int(2) [2]=> &int(3) } 

    array(3) { [0]=> int(1) [1]=> int(2) [2]=> &int(1) } 
    array(3) { [0]=> int(1) [1]=> int(2) [2]=> &int(2) } 
    array(3) { [0]=> int(1) [1]=> int(2) [2]=> &int(2) } 
```
当我们在两个foreach语句的循环体中增加了`var_dump()`时就很容易看出其中引用赋值对foreach语句的执行带来的变化。  

原因分析：foreach循环遍历时，会将数组的键和值分别赋值给变量`$key`（上述未定义）和`$value`，相当于`$value = $arr[$key]`。如果是`&$value`时，则`$value = &$arr[$key]`。故第一个foreach循环时，通过`var_dump()`打印会看到三次分别再`$arr[0],$arr[1],$arr[2]`处存在引用赋值。而第二个foreach循环时，因`$arr[2]`的地址指向和`$value`是同一内存空间，所以会看到第二次foreach循环时`$arr[2]`的值会分别是`1,2,3`。

（注：foreach循环执行完毕后并不会释放`$key`和`$value`，所以在你的代码中foreach执行完毕后请谨慎时候`$key`和`$value`，尤其是引用赋值。）

***

不知道上述是否能让你有所收获，结尾我们还有一道题，来看一下吧。

题目：请写出以下php语句执行输出结果：  
```php
<?php
    $arr = [1,2,3];  
    foreach($arr as $value){
    	unset($value);
    }
    var_dump($arr);
    
    foreach($arr as &$value){
    	unset($value);
    }
    var_dump($arr);
```
```
输出：
    array(3) { [0]=> int(1) [1]=> int(2) [2]=> int(3) } 
    array(3) { [0]=> int(1) [1]=> int(2) [2]=> int(3) } 
```

## 由foreach引发的关于PHP底层变量存储的思考

> 本文首发于 [zval _ 引用计数 _ 变量分离 _ 写时拷贝](https://segmentfault.com/a/1190000004340427)

经过上文中的两个题目后不知道你有没有对foreach的机制了解的更清楚一点了呢？不如我们再来看一个题目：

```php
<?php
    $arr = [1,2,3];  
    foreach($arr as $value){
        if($value <= 2){
            $arr[] = $value * 10;
        }
        echo $value . "<br />";
    }
    var_dump($arr);
    echo "<br />";
    echo "<br />";
    $arr = [1,2,3];
    foreach($arr as &$value){
        if($value <= 2){
            $arr[] = $value * 10;
        }
        echo $value . "<br />";
    }
    var_dump($arr);
```
```
输出：
    1
    2
    3
    array(5) { [0]=> int(1) [1]=> int(2) [2]=> int(3) [3]=> int(10) [4]=> int(20) } 

    1
    2
    3
    10
    20
    array(5) { [0]=> int(1) [1]=> int(2) [2]=> int(3) [3]=> int(10) [4]=> &int(20) } 
```

看到这个题目有没有清楚一点呢？当我们对数据循环时相当于先对数据进行了拷贝，在数组循环体中如果要改变原数组的值或者往原数组中插入新的元素都需要对`$arr`进行赋值操作，且新插入的元素不会被循环。而foreach引用赋值时，修改原数组的值只需要对相对应的元素的`$value`值进行修改就好，且新插入的元素会被循环。这究竟是为什么呢？为什么会是这样一种机制呢？下面我们来了解一下PHP的zval存储结构。