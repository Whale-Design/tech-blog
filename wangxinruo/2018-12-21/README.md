## history对象的pushState()和replaceState()方法
浏览器窗口有一个history对象，用来保存浏览历史。
history对象提供了一系列方法，允许在浏览历史之间移动。
1. back()：移动到上一个访问页面，等同于浏览器的后退键
2. forward()：移动到下一个访问页面，等同于浏览器的前进键
3. go()：接受一个整数作为参数，移动到该整数指定的页面，比如go(1)相当于forward()，go(-1)相当于back(),go(0)相当于刷新当前页面。  
#### HTML5为history对象添加了两个新方法，history.pushState()和history.replaceState()，用来在浏览历史中添加和修改记录
history.pushState方法接受三个参数，依次为： 

state：一个与指定网址相关的状态对象，popstate事件触发时，该对象会传入回调函数。如果不需要这个对象，此处可以填null。  

title：新页面的标题，但是所有浏览器目前都忽略这个值，因此这里可以填null。

url：新的网址，必须与当前页面处在同一个域。浏览器的地址栏将显示这个网址。

总之，pushState方法不会触发页面刷新，只是导致history对象发生变化，地址栏会有反应。

history.replaceState方法的参数与pushState方法一模一样，区别是它修改浏览历史中当前纪录。 

#### history.state属性返回当前页面的state对象。

每当同一个文档的浏览历史（即history对象）出现变化时，就会触发popstate事件。

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
注意，页面第一次加载的时候，在load事件发生后，Chrome和Safari浏览器（Webkit核心）会触发popstate事件，而Firefox和IE浏览器不会。
