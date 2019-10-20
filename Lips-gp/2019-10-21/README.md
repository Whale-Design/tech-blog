## PHP的观察者模式

观察者模式是PHP的设计模式中使用较多的几种模式之一。观察者模式可以让代码更优雅，更容易维护，能更好的应对需求的改变，让代码往高内聚低耦合的道路上更进一步。

php提供的两个接口，一个被观察者接口SplSubject，一个或多个观察者接口SPLObserver，和一个可以储存对象的类SplObjectStorage。

被观察者接口SplSubject有三个方法：
```
SplSubject {  
	/* 方法 */  
	abstract public void attach ( SplObserver $observer )  
	abstract public void detach ( SplObserver $observer )  
	abstract public void notify ( void )  
}  
```

观察者接口SPLObserver有一个方法：
```
SplObserver {  
	/* 方法 */  
	abstract public void update ( SplSubject $subject )  
}  
```

观察者模式适用场景

当一个抽象模型有两个方面，其中一个方面依赖于另一个方面。

当对一个对象的改变需要同时改变其它对象，而不知道具体有多少个对象待改变。

当一个对象必须通知其它对象，而它又不能假定其它对象是谁。换句话说，你不希望这些对象是紧密耦合的。

http://www.zeroplace.cn/article.asp?id=905