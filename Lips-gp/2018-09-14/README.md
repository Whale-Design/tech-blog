## 变量在 PHP7 内部的实现（二）

> 本文首发于 [[译]变量在 PHP7 内部的实现（二）](https://0x1.im/blog/php/Internal-value-representation-in-PHP-7-part-2.html)


*本文第一部分和第二均翻译自Nikita Popov(nikic，PHP 官方开发组成员，柏林科技大学的学生) 的博客。为了更符合汉语的阅读习惯，文中并不会逐字逐句的翻译。*

*要理解本文，你应该对 PHP5 中变量的实现有了一些了解，本文重点在于解释 PHP7 中`zval`的变化。*

[第一部分](https://0x1.im/blog/php/Internal-value-representation-in-PHP-7-part-1.html)讲了 PHP5 和 PHP7 中关于变量最基础的实现和变化。这里再重复一下，主要的变化就是`zval`不再单独分配内存，不自己存储引用计数。整型浮点型等简单类型直接存储在`zval`中。复杂类型则通过指针指向一个独立的结构体。  


复杂的`zval`数据值有一个共同的头，其结构由`zend_refcounted`定义：
```c
struct _zend_refcounted {
    uint32_t refcount;
    union {
        struct {
            ZEND_ENDIAN_LOHI_3(
                zend_uchar    type,
                zend_uchar    flags,
                uint16_t      gc_info)
        } v;
        uint32_t type_info;
    } u;
};
```

这个头存储有`refcount`（引用计数），值的类型`type`和循环回收的相关信息`gc_info`以及类型标志位`flags`。

接下来会对每种复杂类型的实现单独进行分析并和 PHP5 的实现进行比较。引用虽然也属于复杂类型，但是上一部分已经介绍过了，这里就不再赘述。另外这里也不会讲到资源类型（因为作者觉得资源类型没什么好讲的）。


### 字符串

PHP7 中定义了一个新的结构体`zend_string`用于存储字符串变量：
```c
struct _zend_string {
    zend_refcounted   gc;
    zend_ulong        h;        /* hash value */
    size_t            len;
    char              val[1];
};
```

除了引用计数的头以外，字符串还包含哈希缓存`h`，字符串长度`len`以及字符串的值`val`。哈希缓存的存在是为了防止使用字符串做为`hashtable`的`key`在查找时需要重复计算其哈希值，所以这个在使用之前就对其进行初始化。

如果你对 C 语言了解的不是很深入的话，可能会觉得`val`的定义有些奇怪：这个声明只有一个元素，但是显然我们想存储的字符串长度肯定大于一个字符的长度。这里其实使用的是结构体的一个『黑』方法：在声明数组时只定义一个元素，但是实际创建`zend_string`时再分配足够的内存来存储整个字符串。这样我们还是可以通过`val`访问完整的字符串。

当然这属于非常规的实现手段，因为我们实际的读和写的内容都超过了单字符数组的边界。但是 C 语言编译器却不知道你是这么做的。虽然 C99 也曾明确规定过支持『柔性数组』，但是感谢我们的好朋友微软，没人能在不同的平台上保证 C99 的一致性（所以这种手段是为了解决 Windows 平台下柔性数组的支持问题）。

新的字符串类型的结构比原生的 C 字符串更方便使用：第一是因为直接存储了字符串的长度，这样就不用每次使用时都去计算。第二是字符串也有引用计数的头，这样也就可以在不同的地方共享字符串本身而无需使用`zval`。一个经常使用的地方就是共享`hashtable`的`key`。

但是新的字符串类型也有一个很不好的地方：虽然可以很方便的从`zend_string`中取出 C 字符串（使用`str->val`即可），但反过来，如果将 C 字符串变成`zend_string`就需要先分配`zend_string`需要的内存，再将字符串复制到`zend_string`中。这在实际使用的过程中并不是很方便。

字符串也有一些特有的标志（存储在 GC 的标志位中的）：
```c 
#define IS_STR_PERSISTENT           (1<<0) /* allocated using malloc */
#define IS_STR_INTERNED             (1<<1) /* interned string */
#define IS_STR_PERMANENT            (1<<2) /* interned string surviving request boundary */
```

持久化的字符串需要的内存直接从系统本身分配而不是`zend`内存管理器（ZMM），这样它就可以一直存在而不是只在单次请求中有效。给这种特殊的分配打上标记便于`zval`使用持久化字符串。在 PHP5 中并不是这样处理的，是在使用前复制一份到`ZMM`中。

保留字符（interned strings）有点特殊，它会一直存在直到请求结束时才销毁，所以也就无需进行引用计数。保留字符串也不可重复（duplicate），所以在创建新的保留字符时也会先检查是否有同样字符的已经存在。所有 PHP 源码中不可变的字符串都是保留字符（包括字符串常量、变量名函数名等）。持久化字符串也是请求开始之前已经创建好的保留字符。但普通的保留字符在请求结束后会销毁，持久化字符串却始终存在。

如果使用了`opcache`的话保留字符会被存储在共享内存（SHM）中这样就可以在所有 PHP 进程质检共享。这种情况下持久化字符串也就没有存在的意义了，因为保留字符也是不会被销毁的。


### 数组

因为之前的文章有讲过新的数组实现，所以这里就不再详细描述了。虽然最近有些变化导致之前的描述不是十分准确了，但是基本的概念还是一致的。

这里要说的是之前的文章中没有提到的数组相关的概念：不可变数组。其本质上和保留字符类似：没有引用计数且在请求结束之前一直存在（也可能在请求结束之后还存在）。

因为某些内存管理方便的原因，不可变数组只会在开启`opcache`时会使用到。我们来看看实际使用的例子，先看以下的脚本：
```c 
<?php
for ($i = 0; $i < 1000000; ++$i) {
    $array[] = ['foo'];
}
var_dump(memory_get_usage());
```
开启`opcache`时，以上代码会使用`32MB`的内存，不开启的情况下因为`$array`每个元素都会复制一份`['foo']`，所以需要`390MB`。这里会进行完整的复制而不是增加引用计数值的原因是防止`zend`虚拟机操作符执行的时候出现共享内存出错的情况。我希望不使用`opcache`时内存暴增的问题以后能得到改善。


### PHP5 中的对象

在了解 PHP7 中的对象实现直线我们先看一下 PHP5 的并且看一下有什么效率上的问题。PHP5 中的`zval`会存储一个`zend_object_value`结构，其定义如下：
```c 
typedef struct _zend_object_value {
    zend_object_handle handle;
    const zend_object_handlers *handlers;
} zend_object_value;
```
`handle`是对象的唯一 ID，可以用于查找对象数据。`handles`是保存对象各种属性方法的虚函数表指针。通常情况下 PHP 对象都有着同样的`handler`表，但是 PHP 扩展创建的对象也可以通过操作符重载等方式对其行为自定义。

对象句柄（handler）是作为索引用于『对象存储』，对象存储本身是一个存储容器（bucket）的数组，`bucket`定义如下：
```c 
typedef struct _zend_object_store_bucket {
    zend_bool destructor_called;
    zend_bool valid;
    zend_uchar apply_count;
    union _store_bucket {
        struct _store_object {
            void *object;
            zend_objects_store_dtor_t dtor;
            zend_objects_free_object_storage_t free_storage;
            zend_objects_store_clone_t clone;
            const zend_object_handlers *handlers;
            zend_uint refcount;
            gc_root_buffer *buffered;
        } obj;
        struct {
            int next;
        } free_list;
    } bucket;
} zend_object_store_bucket;
```
这个结构体包含了很多东西。前三个成员只是些普通的元数据（对象的析构函数是否被调用过、`bucke`是否被使用过以及对象被递归调用过多少次）。接下来的联合体用于区分`bucket`是处于使用中的状态还是空闲状态。上面的结构中最重要的是`struct _store_object`子结构体：

第一个成员`object`是指向实际对象（也就是对象最终存储的位置）的指针。对象实际并不是直接嵌入到对象存储的`bucket`中的，因为对象不是定长的。对象指针下面是三个用于管理对象销毁、释放与克隆的操作句柄（handler）。这里要注意的是 PHP 销毁和释放对象是不同的步骤，前者在某些情况下有可能会被跳过（不完全释放）。克隆操作实际上几乎几乎不会被用到，因为这里包含的操作不是普通对象本身的一部分，所以（任何时候）他们在每个对象中他们都会被单独复制（duplicate）一份而不是共享。

这些对象存储操作句柄后面是一个普通的对象`handlers`指针。存储这几个数据是因为有时候可能会在`zval`未知的情况下销毁对象（通常情况下这些操作都是针对`zval`进行的）。

`bucket`也包含了`refcount`的字段，不过这种行为在 PHP5 中显得有些奇怪，因为`zval`本身已经存储了引用计数。为什么还需要一个多余的计数呢？问题在于虽然通常情况下`zval`的『复制』行为都是简单的增加引用计数即可，但是偶尔也会有深度复制的情况出现，比如创建一个全新的`zval`但是保存同样的`zend_object_value`。这种情况下两个不同的`zval`就用到了同一个对象存储的`bucket`，所以`bucket`自身也需要进行引用计数。这种『双重计数』的方式是 PHP5 的实现内在的问题。GC 根缓冲区中的`buffered`指针也是由于同样的原因才需要进行完全复制（duplicate）。

现在看看对象存储中指针指向的实际的 object 的结构，通常情况下用户层面的对象定义如下：
```c 
typedef struct _zend_object {
    zend_class_entry *ce;
    HashTable *properties;
    zval **properties_table;
    HashTable *guards;
} zend_object;
```
`zend_class_entry`指针指向的是对象实现的类原型。接下来的两个元素是使用不同的方式存储对象属性。动态属性（运行时添加的而不是在类中定义的）全部存在`properties`中，不过只是属性名和值的简单匹配。

不过这里有针对已经声明的属性的一个优化：编译期间每个属性都会被指定一个索引并且属性本身是存储在`properties_table`的索引中。属性名称和索引的匹配存储在类原型的`hashtable`中。这样就可以防止每个对象使用的内存超过`hashtable`的上限，并且属性的索引会在运行时有多处缓存。

`guards`的哈希表是用于实现魔术方法的递归行为的，比如`__get`，这里我们不深入讨论。

除了上文提到过的双重计数的问题，这种实现还有一个问题是一个最小的只有一个属性的对象也需要`136`个字节的内存（这还不算`zval`需要的内存）。而且中间存在很多间接访问动作：比如要从对象`zval`中取出一个元素，先需要取出对象存储`bucket`，然后是`zend_object`，然后才能通过指针找到对象属性表和`zval`。这样这里至少就有 4 层间接访问（并且实际使用中可能最少需要七层）。


### PHP7 中的对象

PHP7 的实现中试图解决上面这些问题，包括去掉双重引用计数、减少内存使用以及间接访问。新的`zend_object`结构体如下：
```c 
struct _zend_object {
    zend_refcounted   gc;
    uint32_t          handle;
    zend_class_entry *ce;
    const zend_object_handlers *handlers;
    HashTable        *properties;
    zval              properties_table[1];
};
```
可以看到现在这个结构体几乎就是一个对象的全部内容了：`zend_object_value`已经被替换成一个直接指向对象和对象存储的指针，虽然没有完全移除，但已经是很大的提升了。

除了 PHP7 中惯用的`zend_refcounted`头以外，`handle`和对象的`handlers`现在也被放到了`zend_object`中。这里的`properties_table`同样用到了 C 结构体的小技巧，这样`zend_object`和属性表就会得到一整块内存。当然，现在属性表是直接嵌入到`zval`中的而不是指针。

现在对象结构体中没有了`guards`表，现在如果需要的话这个字段的值会被存储在`properties_table`的第一位中，也就是使用`__get`等方法的时候。不过如果没有使用魔术方法的话，`guards`表会被省略。

`dtor`、`free_storage`和`clone`三个操作句柄之前是存储在对象操作`bucket`中，现在直接存在`handlers`表中，其结构体定义如下：
```c 
struct _zend_object_handlers {
    /* offset of real object header (usually zero) */
    int                                     offset;
    /* general object functions */
    zend_object_free_obj_t                  free_obj;
    zend_object_dtor_obj_t                  dtor_obj;
    zend_object_clone_obj_t                 clone_obj;
    /* individual object functions */
    // ... rest is about the same in PHP 5
};
```
`handler`表的第一个成员是`offset`，很显然这不是一个操作句柄。这个`offset`是现在的实现中必须存在的，因为虽然内部的对象总是嵌入到标准的`zend_object`中，但是也总会有添加一些成员进去的需求。在 PHP5 中解决这个问题的方法是添加一些内容到标准的对象后面：
```c 
struct custom_object {
    zend_object std;
    uint32_t something;
    // ...
};
```
这样如果你可以轻易的将`zend_object*`添加到`struct custom_object*`中。这也是 C 语言中常用的结构体继承的做法。但是在 PHP7 中这种实现会有一个问题：因为`zend_object`在存储属性表时用了结构体`hack`的技巧，`zend_object`尾部存储的 PHP 属性会覆盖掉后续添加进去的内部成员。所以 PHP7 的实现中会把自己添加的成员添加到标准对象结构的前面：
```c 
struct custom_object {
    uint32_t something;
    // ...
    zend_object std;
};
```
不过这样也就意味着现在无法直接在`zend_object*`和`struct custom_object*`进行简单的转换了，因为两者都一个偏移分割开了。所以这个偏移量就需要被存储在对象`handler`表中的第一个元素中，这样在编译时通过`offsetof()`宏就能确定具体的偏移值。

也许你会好奇既然现在已经直接（在`zend_value`中）存储了`zend_object`的指针，那现在就不需要再到对象存储中去查找对象了，为什么 PHP7 的对象者还保留着`handle`字段呢？

这是因为现在对象存储仍然存在，虽然得到了极大的简化，所以保留`handle`仍然是有必要的。现在它只是一个指向对象的指针数组。当对象被创建时，会有一个指针插入到对象存储中并且其索引会保存在`handle`中，当对象被释放时，索引也会被移除。

那么为什么现在还需要对象存储呢？因为在请求结束的阶段会在存在某个节点，在这之后再去执行用户代码并且取指针数据时就不安全了。为了避免这种情况出现 PHP 会在更早的节点上执行所有对象的析构函数并且之后就不再有此类操作，所以就需要一个活跃对象的列表。

并且`handle`对于调试也是很有用的，它让每个对象都有了一个唯一的 ID，这样就很容易区分两个对象是同一个还是只是有相同的内容。虽然`HHVM`没有对象存储的概念，但它也存了对象的`handle`。

和 PHP5 相比，现在的实现中只有一个引用计数（`zval`自身不计数），并且内存的使用量有了很大的缩减：40 个字节用于基础对象，每个属性需要 16 个字节，并且这还是算了`zval`之后的。间接访问的情况也有了显著的改善，因为现在中间层的结构体要么被去掉了，要么就是直接嵌入的，所以现在读取一个属性只有一层访问而不再是四层。


### 简介 zval

到现在我们已经基本提到过了所有正常的`zval`类型，但是也有一对特殊类型用于某些特定的情况的，其中之一就是 PHP7 新添加的`IS_INDIRECT`。

间接`zval`指的就是其真正的值是存储在其他地方的。注意这个`IS_REFERENCE`类型是不同的，间接`zval`是直接指向另外一个`zval`而不是像`zend_reference`结构体一样嵌入`zval`。

为了理解在什么时候会出现这种情况，我们来看一下 PHP 中变量的实现（实际上对象属性的存储也是一样的情况）。

所有在编译过程中已知的变量都会被指定一个索引并且其值会被存在编译变量（CV）表的相应位置中。但是 PHP 也允许你动态的引用变量，不管是局部变量还是全局变量（比如 $GLOBALS），只要出现这种情况，PHP 就会为脚本或者函数创建一个符号表，这其中包含了变量名和它们的值之间的映射关系。

但是问题在于：怎么样才能实现两个表的同时访问呢？我们需要在 CV 表中能够访问普通变量，也需要能在符号表中访问编译变量。在 PHP5 中 CV 表用了双重指针`zval**`，通常这些指针指向中间的`zval*`的表，`zval*`最终指向的才是实际的`zval`:
```c 
+------ CV_ptr_ptr[0]
| +---- CV_ptr_ptr[1]
| | +-- CV_ptr_ptr[2]
| | |
| | +-> CV_ptr[0] --> some zval
| +---> CV_ptr[1] --> some zval
+-----> CV_ptr[2] --> some zval
```
当需要使用符号表时存储`zval*`的中间表其实是没有用到的而`zval**`指针会被更新到`hashtable buckets`的响应位置中。我们假定有`$a`、`$b`和`$c`三个变量，下面是简单的示意图：
```c 
CV_ptr_ptr[0] --> SymbolTable["a"].pDataPtr --> some zval
CV_ptr_ptr[1] --> SymbolTable["b"].pDataPtr --> some zval
CV_ptr_ptr[2] --> SymbolTable["c"].pDataPtr --> some zval
```
间接`zval`也可以是一个指向`IS_UNDEF`类型`zval`的指针，当`hashtable`没有和它关联的`key`时就会出现这种情况。所以当使用`unset($a)`将`CV[0]`的类型标记为`UNDEF`时就会判定符号表不存在键值为`a`的数据。


### 常量和 AST

还有两个需要说一下的在 PHP5 和 PHP7 中都存在的特殊类型`IS_CONSTANT`和`IS_CONSTANT_AST`。要了解他们我们还是先看以下的例子：
```php
<?php
function test($a = ANSWER,
              $b = ANSWER * ANSWER) {
    return $a + $b;
}

define('ANSWER', 42);
var_dump(test()); // int(42 + 42 * 42)·
```
`test()`函数的两个参数的默认值都是由常量`ANSWER`构成，但是函数声明时常量的值尚未定义。常量的具体值只有通过`define()`定义时才知道。

由于以上问题的存在，参数和属性的默认值、常量以及其他接受『静态表达式』的东西都支持『延时绑定』直到首次使用时。

常量（或者类的静态属性）这些需要『延时绑定』的数据就是最常需要用到`IS_CONSTANT`类型`zval`的地方。如果这个值是表达式，就会使用`IS_CONSTANT_AST`类型的`zval`指向表达式的抽象语法树（AST）。

到这里我们就结束了对 PHP7 中变量实现的分析。后面我可能还会写两篇文章来介绍一些虚拟机优化、新的命名约定以及一些编译器基础结构的优化的内容（这是作者原话）。

*译者注：两篇文章篇幅较长，翻译中可能有疏漏或不正确的地方，如果发现了请及时指正。*