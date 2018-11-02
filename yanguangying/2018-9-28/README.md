## arguments 与 es6

arguments：
> 是函数的内置对象（实参集合）,类数组对象。

es6知识点：
> 默认参数：当函数调用时，参数缺少，缺少的参数默认为undefined，在es6中，可以用一下方式指定默认参数值，当参数缺少时，使用给定的默认参数值。

```
  function fun(a = 1) {
    return a + 1;
  }
  fun();
  //2
```

思考题：
```
  function sidEffecting(ary) {
    ary[0] = ary[2];
  }

  function bar(a, b, c) {
    c = 10
    sidEffecting(arguments);
    return a + b + c;
  }
  console.log( bar(1, 1, 1))
  //21

```


```
  function sidEffecting(ary) {
    ary[0] = ary[2];
  }

  function bar(a, b, c=3) {
    c = 10
    sidEffecting(arguments);
    return a + b + c;
  }
  console.log( bar(1, 1, 1))
  //12

```

个人理解：
- 没有默认参数时，形参和arguments为引用相同的空间的值。
- 有默认参数时，形参形成一个新的内存空间，与arguments引用不同的空间的值。