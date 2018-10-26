### Cookie,Session,LocalStorage,SessionStorage的区别
#### Cookie 
cookie是指某些网站为了辨别用户身份而存储在用户本地终端上的数据（通常经过加密）。 
- 由服务端生成，保存在客户端（由于前后端有交互，所以安全性差，且浪费带宽）
- 存储大小有限（最大 4kb )
- 存储内容只接受 String 类型
- 保存位置：
    - 若未设置过期时间，则保存在 内存 中，浏览器关闭后销毁
    - 若设置了过期时间，则保存在 系统硬盘 中，直到过期时间结束后才消失（即使关闭浏览器）
- 数据操作不方便，原生接口不友好，需要自己封装
- 应用场景
    - 判断用户是否登录过网站，以便下次登录时能够实现自动登录（或者记住密码）
    - 保存登录时间、浏览次数等信息
#### Session 
session相当于程序在服务器上建立一份客户档案，客户来访时只需要查询客户档案表就可以了。 
- 依赖于 cookie（sessionID 保存在cookie中）
- 保存在服务端
- 存储大小无限制
- 支持任何类型的存储内容
- 保存位置：服务器内存，若访问较多会影响服务器性能
#### WebStorage
webStorage是 html5 提供的本地存储方案,包括两种方式：==sessionStorage== 和 ==localStorage==。
- 保存在客户端，不与服务器通信，因此比 cookie 更安全、速度更快
- 存储空间有限，但比 cookie 大（5MB)
- 仅支持 String 类型的存储内容（和 cookie 一样）
- html5 提供了原生接口，数据操作比 cookie 方便
    - setItem(key, value) 保存数据，以键值对的方式储存信息。
    - getItem(key) 获取数据，将键值传入，即可获取到对应的value值。
    - removeItem(key) 删除单个数据，根据键值移除对应的信息。
    - clear() 删除所有的数据
    - key(index) 获取某个索引的key
##### LocalStorage 
- 持久化的本地存储，浏览器关闭重新打开数据依然存在（除非手动删除数据）。
- 应用场景：长期登录、判断用户是否已登录，适合长期保存在本地的数据。
##### SessionStorage
- 会话级的保存下来，刷新页面数据依旧存在。但浏览器窗口关闭后数据被销毁。
- 应用场景：敏感账号一次性登录。
#### Cookie和Session的区别
1. session 保存在服务器，客户端不知道其中的信息；cookie 保存在客户端，服务器能够知道其中的信息。
2. session 中保存的是对象，cookie 中保存的是字符串。
3. session 不能区分路径，同一个用户在访问一个网站期间，所有的session在任何地方都可以访问到。而 cookie 中如果设置了路径参数，那么同一个网站不同路径下的 cookie 互相是不可以访问的。
4. cookie 不是很安全，本人可以分析存放在本地的 COOKIE 并进行 COOKIE欺骗
5. session 会在一定时间内保存在服务器上。当访问增多，会占用你服务器的性能。考虑到减轻服务器性能方面，应该使用 COOKIE。
6. 单个 cookie 保存的数据不能超过 4k ，很多浏览器都限制一个站点最多保存 20 个 cookie。
7. session 是通过 cookie来工作的。
#### Cookie和SessionStorage、LocalStorage的区别
1. cookie数据始终在同源的http请求中携带（即使不需要），即cookie在浏览器和服务器间来回传递。而sessionStorage和localStorage不会自动把数据发给服务器，仅在本地保存。
2. cookie数据还有路径（path）的概念，可以限制cookie只属于某个路径下。
3. 存储大小限制也不同， 
    - cookie数据不能超过4k，同时因为每次http请求都会携带cookie，所以cookie只适合保存很小的数据，如会话标识 
    - sessionStorage和localStorage 虽然也有存储大小的限制，但比cookie大得多，可以达到5M或更大。
4. 数据有效期不同，
    - sessionStorage：仅在当前浏览器窗口关闭前有效，自然也就不可能持久保持；
    - localStorage：始终有效，窗口或浏览器关闭也一直保存，因此用作持久数据；
    - cookie只在设置的cookie过期时间之前一直有效，即使窗口或浏览器关闭。 
5. 作用域不同，
    - sessionStorage不在不同的浏览器窗口中共享，即使是同一个页面；
    - localStorage 在所有同源窗口中都是共享的；
    - cookie也是在所有同源窗口中都是共享的。

参考：
- https://www.jianshu.com/p/a231e9b05683
- https://segmentfault.com/a/1190000015804205
