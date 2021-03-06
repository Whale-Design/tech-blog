作者：康斌 

链接：https://www.jianshu.com/p/d313f1108862 

來源：简书 

![image](https://upload-images.jianshu.io/upload_images/1839822-d10930c25da17cd2.png?imageMogr2/auto-orient/strip%7CimageView2/2/w/650/format/webp)

#### 背景介绍

渐进增强和优雅降级这两个概念是在 CSS3 出现之后火起来的。由于低级浏览器不支持 CSS3，但是 CSS3 特效太优秀不忍放弃，所以在高级浏览器中使用CSS3，而在低级浏览器只保证最基本的功能。二者的目的是关注不同浏览器下的不同体验，但是它们侧重点有所不同。

#### 名词解释

##### 渐进增强
在网页开发中，渐进增强认为应该专注于内容本身。一开始针对低版本的浏览器构建页面，满足最基本的功能，再针对高级浏 览器进行效果，交互，追加各种功能以达到更好用户体验,换句话说，就是以最低要求，实现最基础功能为基本，向上兼容。
##### 优雅降级
在网页开发中，优雅降级指的是一开始针对一个高版本的浏览器构建页面，先完善所有的功能。然后针对各个不同的浏览器进行测试，修复，保证低级浏览器也有基本功能 就好，低级浏览器被认为“简陋却无妨 (poor, but passable)” 可以做一些小的调整来适应某个特定的浏览器。但由于它们并非我们所关注的焦点，因此除了修复较 大的错误之外，其它的差异将被直接忽略。也就是以高要求，高版本为基准，向下兼容。

#### 二者区别
渐进增强观点认为应关注于内容本身。内容是我们建立网站的诱因。有的网站展示它，有的则收集它，有的寻求，有的操作，还有的网站甚至会包含以上的种种，但相同点是它们全都涉及到内容。这使得渐进增强成为一种更为合理的设计范例。这也是它立即被 采纳并用以构建“分级式浏览器支持 (Graded Browser Support)”策略的原因所在。

优雅降级观点认为应该针对那些最高级、最完善的浏览器来设计网站。而将那些被认为“过时”或有功能缺失的浏览器下的测试工作安排在开发周期的最后阶段，并把测试对象限定为主流浏览器（如 IE、Mozilla 等）的前一个版本。在这种设计范例下，旧版的浏览器被认为仅能提供“简陋却无妨 (poor, but passable)” 的浏览体验。你可以做一些小的调整来适应某个特定的浏览器。但由于它们并非我们所关注的焦点，因此除了修复较大的错误之外，其它的差异将被直接忽略。

#### 案例分析
##### 渐进增强写法
![image](http://upload-images.jianshu.io/upload_images/5837348-c8b4e503e92f5eaa.png?imageMogr2/auto-orient/strip%7CimageView2/2/w/1240)

##### 优雅降级写法
![image](http://upload-images.jianshu.io/upload_images/5837348-a8d0316bc2316a68.png?imageMogr2/auto-orient/strip%7CimageView2/2/w/1240)

前缀CSS3   
（
-Trident内核：前缀为-ms
Gecko内核：前缀为-moz
Presto内核：前缀为-o
Webkit内核：前缀为-webkit）   
和正常CSS3在浏览器中的支持情况是这样的：

1. 很久以前：浏览器前缀CSS3和正常CSS3都不支持；
2. 不久之前：浏览器只支持前缀CSS3，不支持正常CSS3；
3. 现在：浏览器既支持前缀CSS3，又支持正常CSS3；
4. 未来：浏览器不支持前缀CSS3，仅支持正常CSS3.


渐进增强的写法，优先考虑老版本浏览器的可用性，最后才考虑新版本的可用性。在时期3前缀CSS3和正常CSS3都可用的情况下，正常CSS3会覆盖前缀CSS3。优雅降级的写法，优先考虑新版本浏览器的可用性，最后才考虑老版本的可用性。在时期3前缀CSS3和正常CSS3都可用的情况下，前缀CSS3会覆盖正常的CSS3。

就CSS3这种例子而言，我更加推崇渐进增强的写法。因为前缀CSS3的某些属性在浏览器中的实现效果有可能与正常的CSS3实现效果不太一样，所以最终还是得以正常CSS3为准。如果你好奇究竟是什么属性在前缀CSS3和正常CSS3中显式效果不一样，可以看看这篇文章《需警惕CSS3属性的书写顺序》。

#### 如何抉择
根据你的用户所使用的客户端的版本来做决定。因为渐进增强和优雅降级的概念本质上是软件开发过程中低版本软件与高版本软件面对新功能的兼容抉择问题。客户端程序则不是开发者所能控制的（你总不能强制用户去升级它们的浏览器吧）。我们所谓的客户端，可以指浏览器，移动终端设备（如：手机，平板电脑，智能手表等）以及它们对应的应用程序（浏览器对应的是网站，移动终端设备对应的是相应的APP）。

现在有很成熟的技术，能够让你分析使用你客户端程序的版本比例。如果低版本用户居多，当然优先采用渐进增强的开发流程；如果高版本用户居多，为了提高大多数用户的使用体验，那当然优先采用优雅降级的开发流程。

