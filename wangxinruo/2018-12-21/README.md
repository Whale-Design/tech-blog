## history对象的pushState()和replaceState()方法
DOM window 对象通过 history 对象提供了对浏览器历史的访问。它暴露了很多有用的方法和属性，允许你在用户浏览历史中向前和向后跳转，同时——从HTML5开始——提供了对history栈中内容的操作。

1. back()：移动到上一个访问页面，等同于浏览器的后退键
```c
window.history.back();
```
2. forward()：移动到下一个访问页面，等同于浏览器的前进键
```c
window.history.forward();
```

3. go()：你可以用 go() 方法载入到会话历史中的某一特定页面， 通过与当前页面相对位置来标志 (当前页面的相对位置标志为0),比如go(1)相当于forward()，go(-1)相当于back(),go(0)相当于刷新当前页面。   

4. 您可以通过查看长度属性的值来确定的历史堆栈中页面的数量:
```c
var numberOfEntries = window.history.length;
```
#### HTML5引入了 history.pushState() 和 history.replaceState() 方法，它们分别可以添加和修改历史记录条目。这些方法通常与window.onpopstate 配合使用。

history.pushState() 方法向浏览器历史添加了一个状态。history.pushState方法接受三个参数，依次为： 

state：状态对象state是一个JavaScript对象，通过pushState () 创建新的历史记录条目。无论什么时候用户导航到新的状态，popstate事件就会被触发，且该事件的state属性包含该历史记录条目状态对象的副本。 

它存储JSON字符串，state 
对象可以是任何可以序列化的东西。由于 火狐 
会将这些对象存储在用户的磁盘上，所以用户在重启浏览器之后这些state对象会恢复，我们施加一个最大640k 
的字符串在state对象的序列化表示上。如果你想pushState() 方法传递了一个序列化表示大于640k 
的state对象，这个方法将扔出一个异常。如果你需要更多的空间，推荐使用sessionStorage或者localStorage。

title：新页面的标题，但是所有浏览器目前都忽略这个值，因此这里可以填null。

url：新的网址，必须与当前页面处在同一个域。浏览器的地址栏将显示这个网址,但并不会导致浏览器加载。

总之，pushState方法不会触发页面刷新，只是导致history对象发生变化，地址栏会有相应变化。

history.replaceState() 的使用与 history.pushState() 非常相似，区别在于  replaceState()  是修改了当前的历史记录项而不是新建一个。 注意这并不会阻止其在全局浏览器历史记录中创建一个新的历史记录项。

replaceState() 的使用场景在于为了响应用户操作，你想要更新状态对象state或者当前历史记录的URL。

#### popstate事件
每当活动的历史记录项发生变化时， popstate 事件都会被传递给window对象。如果当前活动的历史记录项是被 pushState 创建的，或者是由 replaceState 改变的，那么 popstate 事件的状态属性 state 会包含一个当前历史记录状态对象的拷贝。

需要注意的是，仅仅调用pushState方法或replaceState方法 ，并不会触发该事件，只有用户点击浏览器倒退按钮和前进按钮，或者使用JavaScript调用back、forward、go方法时才会触发。另外，该事件只针对同一个文档，如果浏览历史的切换，导致加载不同的文档，该事件也不会触发。

使用的时候，可以为popstate事件指定回调函数。这个回调函数的参数是一个event事件对象，它的state属性指向pushState和replaceState方法为当前URL所提供的状态对象（即这两个方法的第一个参数）。
```c
window.onpopstate = function (event) {

  console.log('location: ' + document.location);

  console.log('state: ' + JSON.stringify(event.state));

};

window.addEventListener('popstate', function(event) {

  console.log('location: ' + document.location);

  console.log('state: ' + JSON.stringify(event.state));

});
```

上面代码中的event.state，就是通过pushState和replaceState方法，为当前URL绑定的state对象。

这个state对象也可以直接通过history对象读取。


```c 
var currentState = history.state;
```

参考链接: https://developer.mozilla.org/zh-CN/docs/Web/API/History_API


