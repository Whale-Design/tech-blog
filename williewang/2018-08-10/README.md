# 深入剖析 Web 服务器与 PHP 应用的通信机制 - 掌握 CGI 和 FastCGI 协议的运行原理

> 本文首发于 [深入剖析 Web 服务器与 PHP 应用之间的通信机制 - 掌握 CGI 和 FastCGI 协议的运行原理](http://blog.phpzendo.com/?p=430)

身为一名使用 PHP 语言开发后端服务的程序猿，我们每天都和 PHP 以及 Web 服务器产生无数次的亲密接触。得益于它们，我们才能够如此快速的构建出令人陶醉的 Web 产品。

尽管我们已经和 Web 服务器和 PHP 建立起深厚的友谊，但你知道它们之间为何能够配合的如此默契么？

这一切都需要从 CGI（Common Gateway Interface：通用网关接口）协议说起。但是请不要对 CGI 协议产生任何的恐惧心理，它并非什么特别复杂的协议，如果你对它不甚了解，可能的原因或许是你还有花一点小心思来学习它。

所以，你应该明白，现在你应该抽出 20 多分钟仔细的研究一下： Web 服务器与 PHP 应用之间是如何进行通信的这个问题。

## 介绍

我们知道 PHP 自 5.4 起为我们内置的 Web 服务器。不过在此之前的版本（或者不使用这个内置服务器时），我们就需要使用其他的 Web 服务器，通常是 Nginx 或者 Apache 这两块 Web 服务器，来部署我们的 PHP 应用。

<font color=#ee7976>
这就涉及一个问题，当用户发起一个 HTTP 请求后，我们的 PHP 应用程序在处理这个请求时并没有直接的解析这个 HTTP 协议，而是可以直接从 $GET、$POST 和 $_SERVER等全局变量中，获取到用户请求数据和其它系统环境。这究竟又是为何呢？
</font>

要想整明白这个问题，我们就不得不需要整明白一个问题：CGI 协议。

CGI 协议同 HTTP 协议一样是一个「应用层」协议，它的 功能 是为了解决 Web 服务器与 PHP 应用（或其他 Web 应用）之间的通信问题。

既然它是一个「协议」，换言之它与语言无关，即只要是实现类 CGI 协议的应用就能够实现相互的通信。

## 深入 CGI 协议

我们已经知道了 CGI 协议是为了完成 Web 服务器和应用之间进行数据通信这个问题。那么，这一节我们就来看看究竟它们之间是如何进行通信的。

简单来讲 CGI 协议它描述了 Web 服务器和应用程序之间进行数据传输的格式，并且只要我们的编程语言支持标准输入（STDIN）、标准输出（STDOUT）以及环境变量等处理，你就可以使用它来编写一个 CGI 程序。

### CGI 的运行原理

* 当用户访问我们的 Web 应用时，会发起一个 HTTP 请求。最终 Web 服务器接收到这个请求。
* Web 服务器创建一个新的 CGI 进程。在这个进程中，将 HTTP 请求数据已一定格式解析出来，并通过标准输入和环境变量传入到 URL 指定的 CGI 程序（PHP 应用 $_SERVER）。
* Web 应用程序处理完成后将返回数据写入到标准输出中，Web 服务器进程则从标准输出流中读取到响应，并采用 HTTP 协议返回给用户响应。

<font color=#ee7976>
一句话就是 Web 服务器中的 CGI 进程将接收到的 HTTP 请求数据读取到环境变量中，通过标准输入转发给 PHP 的 CGI 程序；当 PHP 程序处理完成后，Web 服务器中的 CGI 进程从标准输出中读取返回数据，并转换回 HTTP 响应消息格式，最终将页面呈献给用户。然后 Web 服务器关闭掉这个 CGI 进程。
</font>

可以说 CGI 协议特别擅长处理 Web 服务器和 Web 应用的通信问题。然而，它有一个严重缺陷，对于每个请求都需要重新 fork 出一个 CGI 进程，处理完成后立即关闭。

### CGI 协议的缺陷

* 每次处理用户请求，都需要重新 fork CGI 子进程、销毁 CGI 子进程。
* 一系列的 I/O 开销降低了网络的吞吐量，造成了资源的浪费，在大并发时会产生严重的性能问题。

## 为什么是 FastCGI 而非 CGI 协议

如果仅仅因为工作模式的不同，似乎并没有什么大不了的。并没到非要选择 FastCGI 协议不可的地步。

然而，对于这个看似微小的差异，但意义非凡，最终的结果是实现出来的 Web 应用架构上的差异。

### CGI 与 FastCGI 架构

在 CGI 协议中，Web 应用的生命周期完全依赖于 HTTP 请求的声明周期。

对每个接收到的 HTTP 请求，都需要重启一个 CGI 进程来进行处理，处理完成后必须关闭 CGI 进程，才能达到通知 Web 服务器本次 HTTP 请求处理完成的目的。

但是在 FastCGI 中完全不一样。

<font color=#ee7976>
FastCGI 进程是常驻型的，一旦启动就可以处理所有的 HTTP 请求，而无需直接退出。
</font>

## 再看 FastCGI 协议

通过前面的讲解，我们相比已经可以很准确的说出来 FastCGI 是一种通信协议 这样的结论。现在，我们就将关注的焦点挪到协议本身，来看看这个协议的定义。

同 HTTP 协议一样，FastCGI 协议也是有消息头和消息体组成。

### 消息头信息

主要的消息头信息如下：
* Version：用于表示 FastCGI 协议版本号。
* Type：用于标识 FastCGI 消息的类型 - 用于指定处理这个消息的方法。
* RequestID：标识出当前所属的 FastCGI 请求。
* Content Length: 数据包包体所占字节数。

### 消息类型定义

* BEGIN_REQUEST：从 Web 服务器发送到 Web 应用，表示开始处理新的请求。
* ABORT_REQUEST：从 Web 服务器发送到 Web 应用，表示中止一个处理中的请求。比如，用户在浏览器发起请求后按下浏览器上的「停止按钮」时，会触发这个消息。
* END_REQUEST：从 Web 应用发送给 Web 服务器，表示该请求处理完成。返回数据包里包含「返回的代码」，它决定请求是否成功处理。
* PARAMS：「流数据包」，从 Web 服务器发送到 Web 应用。此时可以发送多个数据包。发送结束标识为从 Web 服务器发出一个长度为 0 的空包。且 PARAMS 中的数据类型和 CGI 协议一致。即我们使用 $_SERVER 获取到的系统环境等。
* STDIN：「流数据包」，用于 Web 应用从标准输入中读取出用户提交的 POST 数据。
* STDOUT：「流数据报」，从 Web 应用写入到标准输出中，包含返回给用户的数据。

### Web 服务器和 FastCGI 交互过程

* Web 服务器接收用户请求，但最终处理请求由 Web 应用完成。此时，Web 服务器尝试通过套接字（UNIX 或 TCP 套接字，具体使用哪个由 Web 服务器配置决定）连接到 FastCGI 进程。
* FastCGI 进程查看接收到的连接。选择「接收」或「拒绝」连接。如果是「接收」连接，则从标准输入流中读取数据包。
* 如果 FastCGI 进程在指定时间内没有成功接收到连接，则该请求失败。否则，Web 服务器发送一个包含唯一的RequestID 的 BEGIN_REQUEST 类型消息给到 FastCGI 进程。后续所有数据包发送都包含这个 RequestID。 然后，Web 服务器发送任意数量的 PARAMS 类型消息到 FastCGI 进程。一旦发送完毕，Web 服务器通过发送一个空PARAMS 消息包，然后关闭这个流。 另外，如果用户发送了 POST 数据 Web 服务器会将其写入到 标准输入（STDIN） 发送给 FastCGI 进程。当所有 POST 数据发送完成，会发送一个空的 标准输入（STDIN） 来关闭这个流。
* 同时，FastCGI 进程接收到 BEGINREQUEST 类型数据包。它可以通过响应 ENDREQUEST 来拒绝这个请求。或者接收并处理这个请求。如果接收请求，FastCGI 进程会等待接收所有的 PARAMS 和 标准输入数据包。 然后，在处理请求并将返回结果写入 标准输出（STDOUT） 流。处理完成后，发送一个空的数据包到标准输出来关闭这个流，并且会发送一个 END_REQUEST 类型消息通知 Web 服务器，告知它是否发生错误异常。

### 为什么需要在消息头发送 RequestID 这个标识？

如果是每个连接仅处理一个请求，发送 RequestID 则略显多余。

但是我们的 Web 服务器和 FastCGI 进程之间的连接可能处理多个请求，即一个连接可以处理多个请求。所以才需要采用数据包协议而不是直接使用单个数据流的原因：以实现「多路复用」。

因此，由于每个数据包都包含唯一的 RequestID，所以 Web 服务器才能在一个连接上发送任意数量的请求，并且 FastCGI 进程也能够从一个连接上接收到任意数量的请求数据包。

另外我们还需要明确一点就是 Web 服务器 与 FastCGI 进程间通信是 无序的。即使我们在交互过程中看起来一个请求是有序的，但是我们的 Web 服务器也有可能在同一时间发出几十个 BEGIN_REQUEST 类型的数据包，以此类推。

## PHP-FPM

其实讲解完 CGI 和 FastCGI 协议，基本上我们就已经研究完 「Web 服务器与 PHP 应用之间的通信机制」这个问题了。但是对于我们 PHP 软件工程师来讲，可能还会遇到「什么是 PHP-FPM」及其相关问题。这里我们一并来稍微讲解一下。

[PHP-FPM 是 FastCGI 进程管理器（PHP FastCGI Process Manager）](http://php.net/manual/zh/install.fpm.php)，用于替换 PHP 内核的 FastCGI 的大部分附加功能（或者说一种替代的 PHP FastCGI 实现），对于高负载网站是非常有用的。

下面是官网中获取到的它所支持的特性：
> * 支持平滑停止 / 启动的高级进程管理功能；
> * 可以工作于不同的 uid/gid/chroot 环境下，并监听不同的端口和使用不同的 php.ini 配置文件（可取代 safe_mode 的设置）；
> * stdout 和 stderr 日志记录；
> * 在发生意外情况的时候能够重新启动并缓存被破坏的 opcode；
> * 文件上传优化支持；
> * "慢日志" - 记录脚本（不仅记录文件名，还记录 PHP backtrace 信息，可以使用 ptrace 或者类似工具读取和分析远程进程的运行数据）运行所导致的异常缓慢；
> * fastcgifinishrequest() - 特殊功能：用于在请求完成和刷新数据后，继续在后台执行耗时的工作（录入视频转换、统计处理等）；
> * 动态／静态子进程产生；
> * 基本 SAPI 运行状态信息（类似 Apache 的 mod_status）；
> * 基于 php.ini 的配置文件。

### 那么 PHP-FPM 是如何工作的呢？

PHP-FPM 进程管理器有两种进程组成，一个 Master 进程和多个 Worker 进程。Master 进程负责监听端口，接收来自 Web 服务器的请求，然后指派具体的 Worker 进程处理请求；worker 进程则一般有多个 (依据配置决定进程数)，每个进程内部都嵌入了一个 PHP 解释器，用来执行 PHP 代码。

## Nginx 服务器如何与 FastCGI 协同工作

Nginx 服务器无法直接与 FastCGI 服务器进行通信，需要启用 ngx_http_fastcgi_module 模块进行代理配置，才能将请求发送给 FastCGI 服务。

其中，包括我们熟知的配置指令：

* fastcgi_pass 用于设置 FastCGI 服务器的 IP 地址（TCT 套接字）或 UNIX 套接字。
* fastcgi_param 设置传入 FastCGI 服务器的参数。

你可以到 [PHP FastCGI 实例教程](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)学习一些基本使用。

## 总结

到这里我们基本就学习完 CGI、FastCGI、PHP-FPM以及 Nginx 服务器与 FastCGI 服务通信原理。一句话：

CGI 和 FastCGI 是一种协议和 HTTP 协议一样位于应用层，与语言无关；PHP-FPM 是一种 FastCGI 协议的实现，能够管理 FastCGI 进程。

> 扩展阅读
> * https://blog.cuiyongjian.com/fe/tencent-cgi/
> * https://zhuanlan.zhihu.com/p/20694204
> * https://stackoverflow.com/questions/2089271/what-is-common-gateway-interface-cgi
> * https://paper.seebug.org/289/
> * https://blog.csdn.net/shreck66/article/details/50355729
> * http://www.phppan.com/2011/05/php-cgi/
> * https://github.com/pangudashu/php7-internal/blob/master/1/fpm.md
> * https://github.com/YuanLianDu/YLD-with-Php/blob/master/articles/php/php-fpm.md
> * http://blog.51cto.com/13581826/2093473
> * http://haiyangxu.github.io/posts/2014/2014-05-11-HowwebworksHTTPand_CGI.html
> * https://www.cnblogs.com/xueweihan/p/5319893.html
> * https://www.zybuluo.com/phper/note/50231
> * https://www.awaimai.com/371.html
> * http://tiankonguse.com/blog/?p=896
> * https://www.digitalocean.com/community/tutorials/understanding-and-implementing-fastcgi-proxying-in-nginx
> * http://www.whizkidtech.redprince.net/cgi-bin/tutorial
> * https://fastcgi-archives.github.io/FastCGI_FAQ.html
> * http://php.net/manual/zh/install.unix.commandline.php
> * http://php.net/manual/zh/reserved.variables.server.php
> * http://www.php-internals.com/book/?p=chapt02/02-02-03-fastcgi
> * https://www.yanxurui.cc/posts/server/2017-01-04-write-a-cgi-program-in-c-language/