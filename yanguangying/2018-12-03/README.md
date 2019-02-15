#  JavaScript中的函数柯里化

[原文链接](https://juejin.im/post/5c1a2f786fb9a04a073051f4)

## 柯里化是指将一个函数分解为一系列函数的过程，每个函数都只接收一个参数。

##  作用：
    1. 可以使用柯里化把一些需要判断的东西（差异化的东西）提前做，最后返回一个纯净的函数。达到解耦的目的。
    2. 也可以把参数先保存起来，等需要的时候再计算。
    更多作用待进一步探索。

## 柯理化实现：
```
    function curry(fn) {
        return (...xs) => {
            if (xs.length === 0) {
            throw Error('EMPTY INVOCATION');
            }
            if (xs.length >= fn.length) {
            return fn(...xs);
            }
            return curry(fn.bind(null, ...xs));
        };
    }

```
注：文章讲述一个功能函数实现的详细步骤，功能为将指定函数转化为柯理化函数。
