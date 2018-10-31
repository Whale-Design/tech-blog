## 一、对于大流量的网站,您采用什么样的方法来解决访问量问题?

当一个网站发展为知名网站的时候（如新浪，腾讯，网易，雅虎），网站的访问量通常都会非常大，如果使用虚拟主机的话，网站就会因为访问量过大而引起 
服务器性能问题，这是很多人的烦恼，有人使用取消RSS等错误的方法来解决问题，显然是下错药，那么对于大流量的网站,需要采用什么样的方法来解决访问量 
问题? 解决方法参考如下：

###首先，确认服务器硬件是否足够支持当前的流量。
```
普通的P4服务器一般最多能支持每天10万独立IP，如果访问量比这个还要大，那么必须首先配置一台更高性能的专用服务器才能解决问题，否则怎么优化都不可能彻底解决性能问题。
```

###其次，优化数据库访问。
```
服务器的负载过大，一个重要的原因是CPU负荷过大，降低服务器CPU的负荷，才能够有效打破瓶颈。而使用静态页面可以使得CPU的负荷最小化。前台实现完全的静态化当然最好，可以完全不用访问数据库，不过对于频繁更新的网站，静态化往往不能满足某些功能。

缓存技术就是另一个解决方案，就是将动态数据存储到缓存文件中，动态网页直接调用这些文件，而不必再访问数据库，WordPress和Z-Blog都大量使用这种缓存技术。

如果确实无法避免对数据库的访问，那么可以尝试优化数据库的查询SQL.避免使用Select * like from这样的语句，每次查询只返回自己需要的结果，避免短时间内的大量SQL查询
```

###第三，禁止外部的盗链。
```
外部网站的图片或者文件盗链往往会带来大量的负载压力，因此应该严格限制外部对于自身的图片或者文件盗链，好在目前可以简单地通过refer来控制盗 链，Apache自己就可以通过配置来禁止盗链，IIS也有一些第三方的ISAPI可以实现同样的功能。当然，伪造refer也可以通过代码来实现盗链， 
不过目前蓄意伪造refer盗链的还不多，可以先不去考虑，或者使用非技术手段来解决，比如在图片上增加水印。
```

###第四，控制大文件的下载。
```
大文件的下载会占用很大的流量，并且对于非SCSI硬盘来说，大量文件下载会消耗CPU，使得网站响应能力下降。因此，尽量不要提供超过2M的大文件下载， 
如果需要提供，建议将大文件放在另外一台服务器上。目前有不少免费的Web 
2.0网站提供图片分享和文件分享功能，因此可以尽量将图片和文件上传到这些分享网站。
```

###第五，使用不同主机分流主要流量
```
将文件放在不同的主机上，提供不同的镜像供用户下载。比如如果觉得RSS文件占用流量大，那么使用FeedBurner或者FeedSky等服务将RSS输出放在其他主机上，这样别人访问的流量压力就大多集中在FeedBurner的主机上，RSS就不占用太多资源了。
```

###第六，使用流量分析统计软件
```
在 网站上安装一个流量分析统计软件，可以即时知道哪些地方耗费了大量流量，哪些页面需要再进行优化，因此，解决流量问题还需要进行精确的统计分析才可以。 
推荐使用的流量分析统计软件是Google Analytics（Google分析）。这个软件非常的不错哦！
```


## 二、php 获取客户端 IP 和服务器端 IP

### php 获取客户端 IP

在PHP获取客户端IP时，常使用`$_SERVER["REMOTE_ADDR"]`。但如果客户端是使用代理服务器来访问，那取到的是代理服务器的 IP 地址，而不是真正的客户端 IP 地址。要想透过代理服务器取得客户端的真实 IP 地址，就要使用`$_SERVER["HTTP_X_FORWARDED_FOR"]`来读取。

但只有客户端使用“透明代理”的情况下，`$_SERVER["HTTP_X_FORWARDED_FOR"]`的值才是客户端真正的 IP（如果是多层代理，该值可能是由客户端真正 IP 和多个代理服务器的 IP 组成，由逗号“,”分隔）；而在“匿名代理”、“欺骗性代理”的情况下是代理服务器的 IP 值（如果是多层代理，该值可能由多个代理服务器的 IP 组成，由逗号“,”分隔）；在“高匿名代理”的情况下是空值。

`REMOTE_ADDR`是你的客户端跟你的服务器“握手”时候的 IP。如果使用了“匿名代理”，`REMOTE_ADDR`将显示代理服务器的 IP。 
`HTTP_CLIENT_IP`是代理服务器发送的 HTTP 头。如果是“超级匿名代理”，则返回`none`值。同样，`REMOTE_ADDR`也会被替换为这个代理服务器的 IP。

`$_SERVER['REMOTE_ADDR'];` //访问端 IP（有可能是用户，有可能是代理服务器的，也有可能是反向代理服务器的）
`$_SERVER['HTTP_CLIENT_IP'];` //代理端的（有可能存在，可伪造），未成标准，不一定服务器都实现了。
`$_SERVER['HTTP_X_FORWARDED_FOR'];` //用户是在哪个 IP 使用的代理（有可能存在，也可以伪造），有标准定义，用来识别经过 HTTP 代理后的客户端IP地址，格式：clientip,proxy1,proxy2。详细解释见 http://zh.wikipedia.org/wiki/X-Forwarded-For。

三个值区别如下：

一、没有使用代理服务器的情况：

REMOTE_ADDR = 您的 IP
HTTP_VIA = 没数值或不显示
HTTP_X_FORWARDED_FOR = 没数值或不显示

二、使用透明代理服务器的情况：Transparent Proxies

REMOTE_ADDR = 最后一个代理服务器 IP 
HTTP_VIA = 代理服务器 IP
HTTP_X_FORWARDED_FOR = 您的真实 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.98.182.163, 203.129.72.215。

这类代理服务器还是将您的信息转发给您的访问对象，无法达到隐藏真实身份的目的。

三、使用普通匿名代理服务器的情况：Anonymous Proxies

REMOTE_ADDR = 最后一个代理服务器 IP 
HTTP_VIA = 代理服务器 IP
HTTP_X_FORWARDED_FOR = 代理服务器 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.98.182.163, 203.129.72.215。

隐藏了您的真实IP，但是向访问对象透露了您是使用代理服务器访问他们的。

四、使用欺骗性代理服务器的情况：Distorting Proxies

REMOTE_ADDR = 代理服务器 IP 
HTTP_VIA = 代理服务器 IP 
HTTP_X_FORWARDED_FOR = 随机的 IP ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.98.182.163, 203.129.72.215。

告诉了访问对象您使用了代理服务器，但编造了一个虚假的随机IP代替您的真实IP欺骗它。

五、使用高匿名代理服务器的情况：High Anonymity Proxies (Elite proxies)

REMOTE_ADDR = 代理服务器 IP
HTTP_VIA = 没数值或不显示
HTTP_X_FORWARDED_FOR = 没数值或不显示 ，经过多个代理服务器时，这个值类似如下：203.98.182.163, 203.98.182.163, 203.129.72.215。

完全用代理服务器的信息替代了您的所有信息，就象您就是完全使用那台代理服务器直接访问对象。

示例代码：
```php
//获取用户IP， 定义一个函数getClientIP()
/**
 * 获取客户端IP地址
 * @param integer   $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean   $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function getClientIP($type = 0, $adv = true){
	$type      = $type ? 1 : 0;
	if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim(current($arr));
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
    return $ip[$type];
}
```

### php 获取服务器端 IP

服务器端 IP 相关的变量
a. `$_SERVER["SERVER_NAME"]`，需要使用函数gethostbyname()获得。这个变量无论在服务器端还是客户端均能正确显示。

b. `$_SERVER["SERVER_ADDR"]`，在服务器端测试：127.0.0.1（这个与httpd.conf中BindAddress的设置值相关）。在客户端测试结果正确。

示例代码：
```php
/**
 * 获取服务器端IP地址
 * @return string
 */
function getServerIp() { 
    if (isset($_SERVER)) { 
        if($_SERVER['SERVER_ADDR']) {
            $server_ip = $_SERVER['SERVER_ADDR']; 
        } else { 
            $server_ip = $_SERVER['LOCAL_ADDR']; // On Windows IIS 7 you must use $_SERVER['LOCAL_ADDR'] rather than $_SERVER['SERVER_ADDR'] to get the server's IP address.
        } 
    } else { 
        $server_ip = getenv('SERVER_ADDR');
    } 
    return $server_ip; 
}

/**
 * 获取服务器端IP地址
 * @return string
 */
function getServerIP(){    
    return gethostbyname($_SERVER["SERVER_NAME"]);    
} 
```