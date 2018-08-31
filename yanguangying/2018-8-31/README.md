# javascripe中的标签语句
```
    //看代码
    function a() {
        outer: 
        for (let a = 1; a < 10; a++) {
            if (a > 5) {
                break outer;
            }
            console.log(a);
        }
    };
    a();
```
### 代码很简单，但是这个"outer"是个什么东西，函数中的键值对？？？
<div align=left>
    <img src="../pic/what.jpg" style="width:200px;">
</div>

### 搜了一下，这个东西叫标签语句，用处就是标识js中的语句。就像上边的代码中的outer代表了for循环，在条件a>5时，跳出for循环，这样一看也没什么用啊，直接写break就行了，多写个这个还浪费时间。

## 下边该说这个东西有什么用了，，， 

1. 给一个js语句起个名字，名字的格式是：标签名 + 冒号（如 outer：），起完名字就可以像调用函数一样来调用这个语句了,不过一般都是跟break和continue结合使用的。
2. 一般被标记的语句为循环语句，即while、do/while、for和for/in语句，（二般情况还没见过）标记后的循环语句，就可以用break和continue来退出指定的循环或者循环的某一次迭代。

```
//eg:

    outerloop:
    for (var i = 0; i < 3; i++) {
        innerloop: 
        for (var j = 0; j < 3; j++) {
            if (i == 2) {
                break outerloop;
            }
            console.log(j)
        }
    }

```




# Lodash

> lodash 是一个一致性、模块化、高性能的 JavaScript 实用工具库。内部封装了诸多对字符串、数组、对象等常见数据类型的处理函数。


## lodash  的“惰性求值”

> 实现的大概思路：给予被处理的value一个壳子，在壳子上赋予一些属性，这些属性保存value，迭代器，其他一些参数，在执行固定方法时，将迭代器对value执行并返回所需的值。

### 示例

```
        var users = [
            {'user': 'barney', 'age': 36 },
            {'user': 'fred',   'age': 40 },
            {'user': 'pebbles','age': 1 }
        ];

        var youngest = _
            .chain(users)
            .sortBy('age')
            .map(function (o) {
                return o.user + ' is ' + o.age;
            })
            .head()
            .value();
        
        // => 'pebbles is 1'
```

### lodash源码
```
    function LazyWrapper(value) {
      this.__wrapped__ = value;
      this.__actions__ = [];
      this.__dir__ = 1;                         //循环遍历时的步长，即每次增加的数值
      this.__filtered__ = false;
      this.__iteratees__ = [];                  
      this.__takeCount__ = MAX_ARRAY_LENGTH;    //数组的最大长度和索引的引用
      this.__views__ = [];
    }
```


```
    function lazyValue() {
      var array = this.__wrapped__.value(),                                     //获取value
          dir = this.__dir__,                                                   //获取步长
          isArr = isArray(array),                                               //value是否为数组
          isRight = dir < 0,                                                    //判断步长是否小于0
          arrLength = isArr ? array.length : 0,                                 //value若为数组，获取数组的长度
          view = getView(0, arrLength, this.__views__),                         //迭代器迭代数组时，开始和结束的位置
          start = view.start,                                                   //开始
          end = view.end,                                                       //结束
          length = end - start,                                                 //处理的长度  
          index = isRight ? end : (start - 1),                                  //确定循环遍历的初始值
          iteratees = this.__iteratees__,                                       //获取操作value的迭代器集合
          iterLength = iteratees.length,                                        //迭代器的数量
          resIndex = 0,
          takeCount = nativeMin(length, this.__takeCount__);                    // nativeMin = Math.min,

      if (!isArr || (!isRight && arrLength == length && takeCount == length)) {  //value不是数组 或 （处理长度大于0 且 value长度等于处理长度 且 处理长度不超过最大长度）
        return baseWrapperValue(array, this.__actions__);
      }

      var result = [];

      outer:
      while (length-- && resIndex < takeCount) {
        index += dir;

        var iterIndex = -1,
            value = array[index];

        while (++iterIndex < iterLength) {
            var data = iteratees[iterIndex],
                iteratee = data.iteratee,
                type = data.type,
                computed = iteratee(value);

            if (type == LAZY_MAP_FLAG) {
                value = computed;
            } else if (!computed) {
                if (type == LAZY_FILTER_FLAG) {
                    continue outer;
                } else {
                    break outer;
                }
            }
        }
        result[resIndex++] = value;
      }
      return result;
    }

```
