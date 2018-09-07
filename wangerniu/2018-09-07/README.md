
最近经历了一次非常痛苦的填坑过程，这个坑实在整个项目开发之初就埋下的，这些坑都是因为我们在开发react组件时，因为掌握的知识不足，抽象的能力不足以及对组件设计的思想理解的不足，然后给自己挖的坑。


# 问题

首先先看看都给自己挖了那些坑。

1. 关于抽象组件的坑

在开发的过程中，我们遇到了一类卡片组件，用来描述设计师提交的设计图及其信息，这个组件大概长这样。

![](/img/card.png)

当时写的时候，没有**仔细认真的考虑这个组件是否会在其他的地方用到**，仅仅是把它作为一个单纯的页面组件，后来开发时，才发现网站中使用到这个组件的地方非常多，比如...

在某初赛页面
![](/img/card-usage-1.png)
在某决赛页面
![](/img/card-usage-2.png)
在个人中心
![](/img/card-usage-3.png)


这个卡片组件的展示几乎是一模一样的，但是因为自己抽象能力的不足，导致了每此使用到的地方都重新写了一遍，这样做带来了以下几个缺点：

1. 难以维护，当这个组件需要添加或修改时，需要每一个组件单独的去处理，浪费效率而且容易出错

2. ui展示不统一，这几个组件所属的模块由不同的人员开发，即便是一样的设计图，也很难做到ui的统一

3. 造成了代码的冗余，维护困难，而且效率低下

在很痛苦的更迭了几次这个组件之后，痛定思痛，长痛不如短痛，在这次迭代的过程中，将这个卡片抽象为全局组件。

有兴趣看效果的小伙伴可以点击[这里](https://www.whalesdesign.com/battle)查看线上的效果

2. 关于导航

首先当用户没有登录时这个导航长这样。

![](/img/header-1.png)

当设计师登录后，导航上的“发布需求”栏目是会隐藏掉的

![](/img/header-3.png)

然后会有一个蓝色的小滑块儿，初始在当前所在导航，当鼠标经过其他导航时，会跟随移动，当离开导航时，会回到原本的导航位置。

![](/img/header-5.gif)

当前页面如果不在导航栏上的路由时(例如个人中心)，蓝色滑块儿默认是不展示的，鼠标经过的效果依旧存在，当鼠标离开导航是，滑块儿消失。

![](/img/header-6.gif)

有兴趣看效果的小伙伴可以点击[这里](https://www.whalesdesign.com)查看导航，注册设计师账号后，可以去[这里](www.whalesdesign.com/designer/work)查看个人中心的导航。

在这里当初的写法呢，是直接是写死的DOM结构，针对于第一版本简单的需求是可以满足的，当后续的版本更迭后，二次维护的难度大大的增加，所以此次也进行了重写。


# 反思

为什么会出现这些问题？

我总结了两个方面：

1. 缺乏组件之间的抽象能力，这是一种全局观，写组件时，仅仅满足于**实现当前组件功能**，缺乏跳出这个组件，站在全局的角度看待组件的能力
2. 缺乏组件内部的构建能力，没有将数据，模板，配置分离，仅仅满足于**实现当前组件功能**，不考虑后续组件更迭带来的维护问题，导致后期维护愈发的困难。

**组件抽象能力**与**组件构建能力**这是两回事儿。

组件的抽象能力其实是一种经验，写的网站多了，遇到的场景多了，自然就知道哪些组件应该被抽离出来，下次在用到的时候调用就是了，开发迭代的时候省事儿。

组件的构建能力其实是一种思维，书写更加合理，优化的组件，数据归数据，模板归模板，逻辑归逻辑，思路清晰，后面的迭代过程也好维护。
 
这次遇到的这么多坑，就是因为我们在开发前，没有认真的，全面的审阅设计图，导致自己对组件的使用没有一个全局大体的认识；在开发时，没有做到数据，逻辑，模板分离，业务逻辑不清晰，组件臃肿，质量低下。

# 收获

在我思考以上问题的同时，总结出了组件开发时的一些思维，也可以叫做原则,我把它整理分为以下四部分。


## 复用/分离

首先，在开需求评审会的时候，就应该考虑到，新的功能由哪些组件组成，这些组件是否应该抽象到一起，这点做到比较难，因为这个时候，产品往往只是在原型上演示迭代的主要思路，具体的页面还需要等ui出图，只要在开发的时候，不要拿到ui图之后直接就去开发就行，就和考试一样，试卷发下来之后，应该通篇的过一遍试卷，对题目都有一些了解，看一眼基本上就能知道自己能考多少分了，放在开发中呢，就是拿到设计图时候，先过一遍自己负责的部分，看看这些图中是否有类似的部分(这里的类似指的是功能逻辑与UI展示类似)，这个时候就应该将这个组件抽为公用组件。


在构建每个组件之前需要考虑抽象问题，它有没有资格抽象成为一个公共组件，这个资格如何判定呢？这个组件现在，或者将来是否会在另外的一个或多个地方被用到，如果用到的话，就应该被抽离出来作为一个公用组件。

就我目前所了解的，大部分网站应该抽象的公用组件有:header,footer,modal,dialog,login,register，nav,各个列表的卡片等


## 专一/稳定

保持每一个组件的最小颗粒度，每一个组件只应该负责一件事情，并且把这件事情做好，例如<Thumb>组件负责图片的展示，那么就应该考虑到，图片原始宽高比例不一致带来的拉伸问题等，


在构建每一个组件的时候，需要考虑到专一性，当一个组件由多个小部分组成时，每一个小部分也应该被抽成组件，然后包装在一个大的组件里，数据由父组件传递到子组件中，在子组件中实现功能。

在父组件中糅杂了所有子组件的功能逻辑，会导致组件臃肿混乱，在开发的时候，也难得翻来翻去找代码，所以合理的抽象组件以及保证抽象出来的组件功能单一纯粹，是书写优雅合理的React组件必不可少的思维。

最后呢，有这样的一句话可以参考，**组件之内高内聚，组件之间低耦合**


## 解耦

解耦这个概念指的是将组件中的数据与模板抽离，将从props中或者异步获取到的数据存储在父组件的state中，然后以props的形式下发到每一个子组件。类似于这样。


```javascript
    class Name extends React.Component {
        constructor(props){
            super(props)
        }
        render(){
            const { name } = this.props;
            <h1>{name}</h1>
        }
    }
    class SwitchName extends React.Component {
        constructor(props){
            super(props)
        }
        render(){
            const { switch } = this.props;
            return(
                <button onClick={swtich} >switch name</button>
            )
        }
    }
    class Parent extends React.Component {
        constructor(props){
            super(props)
            this.state = {
                name:"123",
                isShowName:false,
            }
        }
        switchName = ()=>  {
            const { isShowName } = this.state;
            this.setState({
                isShowName:!isShowname
            })
        }
        render(){
            const { name, isShowName } = this.state;
            return(
                {
                    isShowName ?
                    <Name name={name} />
                    :
                    <HideName />
                }
                <SwitchName switch={this.switchName} />
            )
        }
    }
```

父组件与模板解耦，只负责数据的存储，分配以及数据流通的逻辑；
子组件与数据解耦，只负责数据的渲染，以及相应的业务功能逻辑。

## 面向对象

有的时候还需要用到面向对象的思维去开发组件，因为在开发一些公用组件的时候，往往在每一处地方都会有一些区别，比如说作品卡片在个人中心有勋章标识，在赛事列表页有模糊标识，有的卡片点击打开页面，有的开篇点击打开弹窗等等。

这个时候需要使用到面向对象的思维，将这些类似的需求在组件内部实现，然后将配置做为接口暴露给外部。

```javascript
    class MosaicThumb ...
    class Thumb ...
    class Info ...
    class Dta ...
    class Marker ...
    /**
    * @props config { object } 卡片配置
    * @props config -> mosaic { boolean } 展示模糊图片，true展示，false不展示，默认true
    * @props config -> marker { boolean } 展示勋章标识, true展示，false不展示，默认false    
    */
    class Card extends React.Component {
        const { config } = this.props;
        render(){
            const { cardData } = this.props;
            return(
                <Wrapper>
                    {
                        config.mosaic ?
                        <MosaicThumb />
                        :
                        <Thumb src={cardData.src}/>
                    }
                    <Info info={cardData.info}/>
                    <Data data={cardData.data}/>
                    {
                        config.marker ** <Marker />
                    }
                </Wrapper>
            )
        }
    }
    // 调用
    <Card {...this.props} config={ mosaic:false, marker:true, } />
```

# 例子

下面是我按照刚才总结的思维写的一个简单的todo例子，有兴趣的同学可以复制粘贴到本地运行一些，查看组件抽象思维，数据与模板分离思维，面向对象思维在日常组件开发中的应用。

```javascript
    import React from 'react';
    import { render } from 'react-dom';

// 容器
class Wrapper extends React.Component {
    constructor(props) {
        super(props)
    }
    render() {
        return (
            <div style={{ width: "300px", height: "400px", margin: "20px", border: "1px solid blue", float:'left' }}>
                {
                    this.props.children
                }
            </div>
        )
    }
}
// 添加
class Add extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            value: '',
        }
        this.addOne = this.addOne.bind(this);
        this.updateValue = this.updateValue.bind(this);
    }
    addOne() {
        if (this.props.addable) {
            this.props.add(this.state.value);
            this.setState({
                value:"",
            })
        }
    }
    updateValue(ev) {
        const _value = ev.target.value;
        this.setState({
            value: _value,
        })
    }
    render() {
        const { add, addable } = this.props;
        const { value } = this.state;
        return (
            <div>
                <input value={value} onChange={ev => this.updateValue(ev)} type='text' readOnly={!addable} />
                <button onClick={this.addOne}>添加</button>
            </div>
        )
    }
}
// 待完成列表
class List extends React.Component {
    constructor(props) {
        super(props)
    }
    complete(index){
        if(this.props.completable) {
            this.props.complete(index)
        }
    }
    render() {
        const { todo } = this.props;
        return (
            <ul>
                <h3>待完成列表</h3>
                {
                    todo.map((item, index) => !item.isComplete &&<li key={index}>
                        <span>{item.text}</span>
                        <button onClick={() =>this.complete(index)}>完成</button>
                    </li>)
                }
            </ul>
        )
    }
}

// 已完成列表

class Group extends React.Component {
    constructor(props){
        super(props)
        this.delete = this.delete.bind(this)
    } 
    delete(index){ 
        if(this.props.deletable) {
            this.props.delete(index)
        }
    }
    render() {
        const { todo } = this.props;
        return (
            <ul>
                <h3>已完成列表</h3>
                {
                    todo.map((item, index) => item.isComplete &&<li key={index}>
                        <span>{item.text}</span> 
                        <button onClick={() => this.delete(index)}>删除</button>
                    </li>)
                }
            </ul>
        )
    } 
}
// 展示配置
class Config extends React.Component {
    constructor(props){
        super(props)
    }
    render(){
        const { config } = this.props; 
        return(
            <div>
                <p>组件配置</p>
                <span>可添加:{config.addable?"true":"false"}</span>
                <span>可完成:{config.completable?"true":"false"}</span>
                <span>可删除:{config.deletable?"true":"false"}</span>
            </div>
        )
    }
}

/**
 * @props config tudo配置
 * @props config -> addable 可添加新项目
 * @props config -> deletable 可删除项目
 * @props config -> completable 可完成项目
*/

class Todo extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            todo: [
                {
                    text: "默认待办事项",
                    isComplete: false,
                }
            ]
        }
        this.add = this.add.bind(this);
        this.complete = this.complete.bind(this);
        this.delete = this.delete.bind(this)
    }
    add(text) { 
        let _todo = this.state.todo;
        _todo.push({
            text,
        });
        this.setState({
            todo: _todo,
        })
    }
    complete(index){
        const _todo = this.state.todo;
        _todo[index].isComplete = true;
        this.setState({
            todo:_todo,
        })
    }
    delete(index) { 
        const _todo = this.state.todo;
        _todo.splice(index,1);
        this.setState({
            todo:_todo,
        })
    }
    render() {
        const { config } = this.props;
        const { todo } = this.state;
        return (
            <Wrapper>
                <Config config={config} />
                <Add addable={config.addable} add={this.add} />
                <List complete={this.complete} todo={todo} completable={config.completable} />
                <Group todo={todo} deletable={config.deletable} delete={this.delete} />
            </Wrapper>
        )
    }
}

class App extends React.Component {
    constructor(props) {
        super(props)
    }
    render() {
        return (
            <div>
                <Todo config={{
                    addable: true,
                    completable: true,
                    deletable:true, 
                }} />

                <Todo config={{
                    addable: true,
                    completable: true,
                    deletable:false, 
                }} />                
                <Todo config={{
                    addable: false,
                    completable: true,
                    deletable:true, 
                }} />
            </div>
        )
    }
}

render(<App />, document.getElementById('root'))
```


