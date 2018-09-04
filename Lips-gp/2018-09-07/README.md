## 变量在 PHP7 内部的实现（一）

> 本文首发于 [[译]变量在 PHP7 内部的实现（一）](https://0x1.im/blog/php/Internal-value-representation-in-PHP-7-part-1.html)


*本文第一部分和第二部分均翻译自Nikita Popov(nikic，PHP 官方开发组成员，柏林科技大学的学生) 的博客。为了更符合汉语的阅读习惯，文中并不会逐字逐句的翻译。*

*要理解本文，你应该对 PHP5 中变量的实现有了一些了解，本文重点在于解释 PHP7 中 zval 的变化。*

由于大量的细节描述，本文将会分成两个部分：第一部分主要描述 zval(zend value) 的实现在 PHP5 和 PHP7 中有何不同以及引用的实现。第二部分将会分析单独类型（strings、objects）的细节。

### PHP5 中的 zval
PHP5中`zval`结构体定义如下：
```c
typedef struct _zval_struct {
    zvalue_value value;
    zend_uint refcount__gc;
    zend_uchar type;
    zend_uchar is_ref__gc;
} zval;
```
如上，`zval`包含一个`value`、一个`type`以及两个`__gc`后缀的字段。`value`是个联合体，用于存储不同类型的值：
```c
typedef union _zvalue_value {
    long lval;                 // 用于 bool 类型、整型和资源类型
    double dval;               // 用于浮点类型
    struct {                   // 用于字符串
        char *val;
        int len;
    } str;
    HashTable *ht;             // 用于数组
    zend_object_value obj;     // 用于对象
    zend_ast *ast;             // 用于常量表达式(PHP5.6 才有)
} zvalue_value;
```
C语言联合体的特征是一次只有一个成员是有效的并且分配的内存与需要内存最多的成员匹配（也要考虑内存对齐）。所有成员都存储在内存的同一个位置，根据需要存储不同的值。当你需要`lval`的时候，它存储的是有符号整形，需要`dval`时，会存储双精度浮点数。

需要指出的是是联合体中当前存储的数据类型会记录到 type 字段，用一个整型来标记：
```c
#define IS_NULL     0      /* Doesn't use value */
#define IS_LONG     1      /* Uses lval */
#define IS_DOUBLE   2      /* Uses dval */
#define IS_BOOL     3      /* Uses lval with values 0 and 1 */
#define IS_ARRAY    4      /* Uses ht */
#define IS_OBJECT   5      /* Uses obj */
#define IS_STRING   6      /* Uses str */
#define IS_RESOURCE 7      /* Uses lval, which is the resource ID */

/* Special types used for late-binding of constants */
#define IS_CONSTANT 8
#define IS_CONSTANT_AST 9
```


### PHP5 中的引用计数
在PHP5中，`zval`的内存是单独从堆（heap）中分配的（有少数例外情况），PHP需要知道哪些`zval`是正在使用的，哪些是需要释放的。所以这就需要用到引用计数`zval`中`refcount__gc`的值用于保存`zval`本身被引用的次数，比如`$a = $b = 42`语句中，42被两个变量引用，所以它的引用计数就是2。如果引用计数变成0，就意味着这个变量已经没有用了，内存也就可以释放了。

注意这里提及到的引用计数指的不是PHP代码中的引用（&），而是变量的使用次数。后面两者需要同时出现时会使用『PHP 引用』和『引用』来区分两个概念，这里先忽略掉PHP的部分。

一个和引用计数紧密相关的概念是『写时复制』：对于多个引用来说，`zaval`只有在没有变化的情况下才是共享的，一旦其中一个引用改变`zval`的值，就需要复制（”separated”）一份`zval`，然后修改复制后的`zval`。

下面是一个关于『写时复制』和`zval`的销毁的例子：
```php
<?php
	$a = 42;   // $a         -> zval_1(type=IS_LONG, value=42, refcount=1)
	$b = $a;   // $a, $b     -> zval_1(type=IS_LONG, value=42, refcount=2)
	$c = $b;   // $a, $b, $c -> zval_1(type=IS_LONG, value=42, refcount=3)

	// 下面几行是关于 zval 分离的
	$a += 1;   // $b, $c -> zval_1(type=IS_LONG, value=42, refcount=2)
	           // $a     -> zval_2(type=IS_LONG, value=43, refcount=1)

	unset($b); // $c -> zval_1(type=IS_LONG, value=42, refcount=1)
	           // $a -> zval_2(type=IS_LONG, value=43, refcount=1)

	unset($c); // zval_1 is destroyed, because refcount=0
	           // $a -> zval_2(type=IS_LONG, value=43, refcount=1)
```
引用计数有个致命的问题：无法检查并释放循环引用（使用的内存）。为了解决这问题，PHP使用了循环回收的方法。当一个`zval`的计数减一时，就有可能属于循环的一部分，这时将`zval`写入到『根缓冲区』中。当缓冲区满时，潜在的循环会被打上标记并进行回收。

因为要支持循环回收，实际使用的`zval`的结构实际上如下：
```c
typedef struct _zval_gc_info {
    zval z;
    union {
        gc_root_buffer       *buffered;
        struct _zval_gc_info *next;
    } u;
} zval_gc_info;
```
`zval_gc_info`结构体中嵌入了一个正常的`zval`结构，同时也增加了两个指针参数，但是共属于同一个联合体`u`，所以实际使用中只有一个指针是有用的。`buffered`指针用于存储`zval`在根缓冲区的引用地址，所以如果在循环回收执行之前`zval`已经被销毁了，这个字段就可能被移除了。`next`在回收销毁值的时候使用，这里不会深入。


### 修改动机
下面说说关于内存使用上的情况，这里说的都是指在64位的系统上。首先，由于`str`和`obj`占用的大小一样，`zvalue_value`这个联合体占用16个字节（bytes）的内存。整个`zval`结构体占用的内存是`24`个字节（考虑到内存对齐），`zval_gc_info`的大小是32个字节。综上，在堆（相对于栈）分配给`zval`的内存需要额外的16个字节，所以每个`zval`在不同的地方一共需要用到48个字节（要理解上面的计算方式需要注意每个指针在64位的系统上也需要占用8个字节）。

在这点上不管从什么方面去考虑都可以认为`zval`的这种设计效率是很低的。比如`zval`在存储整型的时候本身只需要8个字节，即使考虑到需要存一些附加信息以及内存对齐，额外8个字节应该也是足够的。

在存储整型时本来确实需要16个字节，但是实际上还有16个字节用于引用计数、16个字节用于循环回收。所以说`zval`的内存分配和释放都是消耗很大的操作，我们有必要对其进行优化。

从这个角度思考：一个整型数据真的需要存储引用计数、循环回收的信息并且单独在堆上分配内存吗？答案是当然不，这种处理方式一点都不好。

这里总结一下PHP5中`zval`实现方式存在的主要问题：

* `zval`总是单独从堆中分配内存；
* `zval`总是存储引用计数和循环回收的信息，即使是整型这种可能并不需要此类信息的数据；
* 在使用对象或者资源时，直接引用会导致两次计数（原因会在下一部分讲）；
* 某些间接访问需要一个更好的处理方式。比如现在访问存储在变量中的对象间接使用了四个指针（指针链的长度为四）。这个问题也放到下一部分讨论；
* 直接计数也就意味着数值只能在`zval`之间共享。如果想在`zval`和`hashtable key`之间共享一个字符串就不行（除非`hashtable key`也是`zval`）。


### PHP7 中的 zval
在PHP7中`zval`有了新的实现方式。最基础的变化就是`zval` 需要的内存不再是单独从堆上分配，不再自己存储引用计数。复杂数据类型（比如字符串、数组和对象）的引用计数由其自身来存储。这种实现方式有以下好处：

* 简单数据类型不需要单独分配内存，也不需要计数；
* 不会再有两次计数的情况。在对象中，只有对象自身存储的计数是有效的；
* 由于现在计数由数值自身存储，所以也就可以和非`zval`结构的数据共享，比如`zval`和`hashtable key`之间；
* 间接访问需要的指针数减少了。

我们看看现在`zval`结构体的定义（现在在zend_types.h文件中）：
```c
struct _zval_struct {
	zend_value        value;			/* value */
	union {
		struct {
			ZEND_ENDIAN_LOHI_4(
				zend_uchar    type,			/* active type */
				zend_uchar    type_flags,
				zend_uchar    const_flags,
				zend_uchar    reserved)	    /* call info for EX(This) */
		} v;
		uint32_t type_info;
	} u1;
	union {
		uint32_t     var_flags;
		uint32_t     next;                 /* hash collision chain */
		uint32_t     cache_slot;           /* literal cache slot */
		uint32_t     lineno;               /* line number (for ast nodes) */
		uint32_t     num_args;             /* arguments number for EX(This) */
		uint32_t     fe_pos;               /* foreach position */
		uint32_t     fe_iter_idx;          /* foreach iterator index */
	} u2;
};
```
结构体的第一个元素没太大变化，仍然是一个`value`联合体。第二个成员是由一个表示类型信息的整型和一个包含四个字符变量的结构体组成的联合体（可以忽略`ZEND_ENDIAN_LOHI_4`宏，它只是用来解决跨平台大小端问题的）。这个子结构中比较重要的部分是`type`（和以前类似）和`type_flags`，这个接下来会解释。

上面这个地方也有一点小问题：`value`本来应该占8个字节，但是由于内存对齐，哪怕只增加一个字节，实际上也是占用16个字节（使用一个字节就意味着需要额外的8个字节）。但是显然我们并不需要8个字节来存储一个`type`字段，所以我们在`u1`的后面增加了了一个名为`u2`的联合体。默认情况下是用不到的，需要使用的时候可以用来存储4个字节的数据。这个联合体可以满足不同场景下的需求。

PHP7中`value`的结构定义如下：
```c
typedef union _zend_value {
	zend_long         lval;				/* long value */
	double            dval;				/* double value */
	zend_refcounted  *counted;
	zend_string      *str;
	zend_array       *arr;
	zend_object      *obj;
	zend_resource    *res;
	zend_reference   *ref;
	zend_ast_ref     *ast;
	zval             *zv;
	void             *ptr;
	zend_class_entry *ce;
	zend_function    *func;
	struct {
		uint32_t w1;
		uint32_t w2;
	} ww;
} zend_value;
```
首先需要注意的是现在`value`联合体需要的内存是8个字节而不是16。它只会直接存储整型（lval）或者浮点型（dval）数据，其他情况下都是指针（上面提到过，指针占用8个字节，最下面的结构体由两个4字节的无符号整型组成）。上面所有的指针类型（除了特殊标记的）都有一个同样的头（zend_refcounted）用来存储引用计数：
```c
typedef struct _zend_refcounted_h {
	uint32_t         refcount;			/* reference counter 32-bit */
	union {
		struct {
			ZEND_ENDIAN_LOHI_3(
				zend_uchar    type,
				zend_uchar    flags,    /* used for strings & objects */
				uint16_t      gc_info)  /* keeps GC root number (or 0) and color */
		} v;
		uint32_t type_info;
	} u;
} zend_refcounted_h;
```
现在，这个结构体肯定会包含一个存储引用计数的字段。除此之外还有`type`、`flags`和`gc_info`。`type`存储的和`zval`中的`type`相同的内容，这样GC在不存储`zval`的情况下单独使用引用计数。`flags`在不同的数据类型中有不同的用途，这个放到下一部分讲。

`gc_info`和PHP5中的`buffered`作用相同，不过不再是位于根缓冲区的指针，而是一个索引数字。因为以前根缓冲区的大小是固定的（10000个元素），所以使用一个16位（2字节）的数字代替64位（8字节）的指针足够了。`gc_info`中同样包含一个『颜色』位用于回收时标记结点。


### zval 内存管理

上文提到过`zval`需要的内存不再单独从堆上分配。但是显然总要有地方来存储它，所以会存在哪里呢？实际上大多时候它还是位于堆中（所以前文中提到的地方重点不是堆，而是单独分配），只不过是嵌入到其他的数据结构中的，比如`hashtable`和`bucket`现在就会直接有一个`zval`字段而不是指针。所以函数表编译变量和对象属性在存储时会是一个`zval`数组并得到一整块内存而不是散落在各处的`zval`指针。之前的`zval *`现在都变成了`zval`。

之前当`zval`在一个新的地方使用时会复制一份`zval *`并增加一次引用计数。现在就直接复制`zval`的值（忽略`u2`），某些情况下可能会增加其结构指针指向的引用计数（如果在进行计数）。

那么PHP怎么知道`zval`是否正在计数呢？不是所有的数据类型都能知道，因为有些类型（比如字符串或数组）并不是总需要进行引用计数。所以`type_info`字段就是用来记录`zval`是否在进行计数的，这个字段的值有以下几种情况：
```c
#define IS_TYPE_CONSTANT            (1<<0)   /* special */
#define IS_TYPE_IMMUTABLE           (1<<1)   /* special */
#define IS_TYPE_REFCOUNTED          (1<<2)
#define IS_TYPE_COLLECTABLE         (1<<3)
#define IS_TYPE_COPYABLE            (1<<4)
#define IS_TYPE_SYMBOLTABLE         (1<<5)   /* special */
```
注：在`7.0.0`的正式版本中，上面这一段宏定义的注释这几个宏是供`zval.u1.v.type_flags`使用的。这应该是注释的错误，因为这个上述字段是`zend_uchar`类型。

`type_info`的三个主要的属性就是『可计数』（refcounted）、『可回收』（collectable）和『可复制』（copyable）。计数的问题上面已经提过了。『可回收』用于标记`zval`是否参与循环，比如字符串通常是可计数的，但是你却没办法给字符串制造一个循环引用的情况。

是否可复制用于表示在复制时是否需要在复制时制造（原文用的 “duplication” 来表述，用中文表达出来可能不是很好理解）一份一模一样的实体。”duplication” 属于深度复制，比如在复制数组时，不仅仅是简单增加数组的引用计数，而是制造一份全新值一样的数组。但是某些类型（比如对象和资源）即使 “duplication” 也只能是增加引用计数，这种就属于不可复制的类型。这也和对象和资源现有的语义匹配（现有，PHP7也是这样，不单是PHP5）。

下面的表格上标明了不同的类型会使用哪些标记（`x`标记的都是有的特性）。『简单类型』（simple types）指的是整型或布尔类型这些不使用指针指向一个结构体的类型。下表中也有『不可变』（immutable）的标记，它用来标记不可变数组的，这个在下一部分再详述。

`interned string`（保留字符）在这之前没有提过，其实就是函数名、变量名等无需计数、不可重复的字符串。

|                | refcounted | collectable | copyable | immutable|
|----------------|:----------:|:-----------:|:--------:|:--------:|
|simple types    |            |             |          |		  |
|string          |      x     |             |     x    |		  |
|interned string |            |             |          |	      |
|array           |      x     |      x      |     x    |		  |
|immutable array |            |             |          |     x	  |
|object          |      x     |      x      |          |		  |
|resource        |      x     |             |          |		  |
|reference       |      x     |             |          |		  |

要理解这一点，我们可以来看几个例子，这样可以更好的认识`zval`内存管理是怎么工作的。

下面是整数行为模式，在上文中PHP5的例子的基础上进行了一些简化 ：
```php
<?php
	$a = 42;   // $a = zval_1(type=IS_LONG, value=42)

	$b = $a;   // $a = zval_1(type=IS_LONG, value=42)
	           // $b = zval_2(type=IS_LONG, value=42)

	$a += 1;   // $a = zval_1(type=IS_LONG, value=43)
	           // $b = zval_2(type=IS_LONG, value=42)

	unset($a); // $a = zval_1(type=IS_UNDEF)
	           // $b = zval_2(type=IS_LONG, value=42)
```
这个过程其实挺简单的。现在整数不再是共享的，变量直接就会分离成两个单独的`zval`，由于现在`zval`是内嵌的所以也不需要单独分配内存，所以这里的注释中使用`=`来表示的而不是指针符号`->``unset`时变量会被标记为`IS_UNDEF`。下面看一下更复杂的情况：
```php
<?php
	$a = [];   // $a = zval_1(type=IS_ARRAY) -> zend_array_1(refcount=1, value=[])

	$b = $a;   // $a = zval_1(type=IS_ARRAY) -> zend_array_1(refcount=2, value=[])
	           // $b = zval_2(type=IS_ARRAY) ---^

	// zval 分离在这里进行
	$a[] = 1;   // $a = zval_1(type=IS_ARRAY) -> zend_array_2(refcount=1, value=[1])
	           // $b = zval_2(type=IS_ARRAY) -> zend_array_1(refcount=1, value=[])

	unset($a); // $a = zval_1(type=IS_UNDEF),   zend_array_2 被销毁
	           // $b = zval_2(type=IS_ARRAY) -> zend_array_1(refcount=1, value=[])
```
这种情况下每个变量变量有一个单独的`zval`，但是是指向同一个（有引用计数）`zend_array`的结构体。修改其中一个数组的值时才会进行复制。这点和PHP5的情况类似。


### 类型（Types）

我们大概看一下PHP7支持哪些类型（zval 使用的类型标记）：
```c
/* regular data types */
#define IS_UNDEF					0
#define IS_NULL						1
#define IS_FALSE					2
#define IS_TRUE						3
#define IS_LONG						4
#define IS_DOUBLE					5
#define IS_STRING					6
#define IS_ARRAY					7
#define IS_OBJECT					8
#define IS_RESOURCE					9
#define IS_REFERENCE				10

/* constant expressions */
#define IS_CONSTANT					11
#define IS_CONSTANT_AST				12

/* internal types */
#define IS_INDIRECT					15
#define IS_PTR						17
```
这个列表和PHP5使用的类似，不过增加了几项：

* `IS_UNDEF`用来标记之前为`NULL`的`zval`指针（和`IS_NULL`并不冲突）。比如在上面的例子中使用`unset`注销变量；
* `IS_BOOL`现在分割成了`IS_FALSE`和`IS_TRUE`两项。现在布尔类型的标记是直接记录到`type`中，这么做可以优化类型检查。不过这个变化对用户是透明的，还是只有一个『布尔』类型的数据（PHP脚本中）。
* PHP引用不再使用`is_ref`来标记，而是使用`IS_REFERENCE`类型。这个也要放到下一部分讲；
* `IS_INDIRECT`和`IS_PTR`是特殊的内部标记。

*实际上上面的列表中应该还存在两个`fake,types`，这里忽略了。*

`IS_LONG`类型表示的是一个`zend_long`的值，而不是原生的C语言的`long`类型。原因是Windows的64位系统（LLP64）上的long类型只有32位的位深度。所以PHP5在Windows上只能使用32位的数字。PHP7允许你在64位的操作系统上使用64位的数字，即使是在Windows上面也可以。

`zend_refcounted`的内容会在下一部分讲。下面看看PHP引用的实现。


### 引用

PHP7使用了和PHP5中完全不同的方法来处理`PHP &`符号引用的问题（这个改动也是PHP7开发过程中大量bug的根源）。我们先从PHP5中PHP引用的实现方式说起。

通常情况下， 写时复制原则意味着当你修改一个`zval`之前需要对其进行分离来保证始终修改的只是某一个PHP变量的值。这就是传值调用的含义。

但是使用PHP引用时这条规则就不适用了。如果一个PHP变量是PHP引用，就意味着你想要在将多个PHP变量指向同一个值。PHP5中的`is_ref`标记就是用来注明一个PHP变量是不是PHP引用，在修改时需不需要进行分离的。比如：
```php
<?php
$a = [];  // $a     -> zval_1(type=IS_ARRAY, refcount=1, is_ref=0) -> HashTable_1(value=[])
$b =& $a; // $a, $b -> zval_1(type=IS_ARRAY, refcount=2, is_ref=1) -> HashTable_1(value=[])

$b[] = 1; // $a = $b = zval_1(type=IS_ARRAY, refcount=2, is_ref=1) -> HashTable_1(value=[1])
          // 因为 is_ref 的值是 1, 所以 PHP 不会对 zval 进行分离
```
但是这个设计的一个很大的问题在于它无法在一个PHP引用变量和PHP非引用变量之间共享同一个值。比如下面这种情况：
```php
<?php
$a = [];  // $a         -> zval_1(type=IS_ARRAY, refcount=1, is_ref=0) -> HashTable_1(value=[])
$b = $a;  // $a, $b     -> zval_1(type=IS_ARRAY, refcount=2, is_ref=0) -> HashTable_1(value=[])
$c = $b;   // $a, $b, $c -> zval_1(type=IS_ARRAY, refcount=3, is_ref=0) -> HashTable_1(value=[])

$d =& $c; // $a, $b -> zval_1(type=IS_ARRAY, refcount=2, is_ref=0) -> HashTable_1(value=[])
          // $c, $d -> zval_1(type=IS_ARRAY, refcount=2, is_ref=1) -> HashTable_2(value=[])
          // $d 是 $c 的引用, 但却不是 $a 的 $b, 所以这里 zval 还是需要进行复制
          // 这样我们就有了两个 zval, 一个 is_ref 的值是 0, 一个 is_ref 的值是 1.

$d[] = 1; // $a, $b -> zval_1(type=IS_ARRAY, refcount=2, is_ref=0) -> HashTable_1(value=[])
          // $c, $d -> zval_1(type=IS_ARRAY, refcount=2, is_ref=1) -> HashTable_2(value=[1])
          // 因为有两个分离了的 zval, $d[] = 1 的语句就不会修改 $a 和 $b 的值.
```
这种行为方式也导致在PHP中使用引用比普通的值要慢。比如下面这个例子：
```
<?php
$array = range(0, 1000000);
$ref =& $array;
var_dump(count($array)); // <-- 这里会进行分离
```
因为`count()`只接受传值调用，但是`$array`是一个PHP引用，所以`count()`在执行之前实际上会有一个对数组进行完整的复制的过程。如果`$array`不是引用，这种情况就不会发生了。

现在我们来看看PHP7中PHP引用的实现。因为`zval`不再单独分配内存，也就没办法再使用和`PHP5`中相同的实现了。所以增加了一个`IS_REFERENCE`类型，并且专门使用`zend_reference`来存储引用值：
```c
struct _zend_reference {
    zend_refcounted   gc;
    zval              val;
};
```
本质上`zend_reference`只是增加了引用计数的`zval`。所有引用变量都会存储一个`zval`指针并且被标记为`IS_REFERENCE`。`val`和其他的`zval`的行为一样，尤其是它也可以在共享其所存储的复杂变量的指针，比如数组可以在引用变量和值变量之间共享。

我们还是看例子，这次是PHP7中的语义。为了简洁明了这里不再单独写出`zval`，只展示它们指向的结构体：
```php
<?php
$a = [];  // $a                                     -> zend_array_1(refcount=1, value=[])
$b =& $a; // $a, $b -> zend_reference_1(refcount=2) -> zend_array_1(refcount=1, value=[])

$b[] = 1; // $a, $b -> zend_reference_1(refcount=2) -> zend_array_1(refcount=1, value=[1])
```
上面的例子中进行引用传递时会创建一个`zend_reference`，注意它的引用计数是2（因为有两个变量在使用这个PHP引用）。但是值本身的引用计数是1（因为`zend_reference`只是有一个指针指向它）。下面看看引用和非引用混合的情况：
```php
<?php
$a = [];  // $a         -> zend_array_1(refcount=1, value=[])
$b = $a;  // $a, $b,    -> zend_array_1(refcount=2, value=[])
$c = $b;   // $a, $b, $c -> zend_array_1(refcount=3, value=[])

$d =& $c; // $a, $b                                 -> zend_array_1(refcount=3, value=[])
          // $c, $d -> zend_reference_1(refcount=2) ---^
          // 注意所有变量共享同一个 zend_array, 即使有的是 PHP 引用有的不是

$d[] = 1; // $a, $b                                 -> zend_array_1(refcount=2, value=[])
          // $c, $d -> zend_reference_1(refcount=2) -> zend_array_2(refcount=1, value=[1])
          // 只有在这时进行赋值的时候才会对 zend_array 进行赋值
```
这里和PHP5最大的不同就是所有的变量都可以共享同一个数组，即使有的是PHP引用有的不是。只有当其中某一部分被修改的时候才会对数组进行分离。这也意味着使用`count()`时即使给其传递一个很大的引用数组也是安全的，不会再进行复制。不过引用仍然会比普通的数值慢，因为内存需要为`zend_reference`结构体分配内存（间接）并且引擎本身处理这一块儿也不快的的原因。


### 结语

总结一下PHP7中最重要的改变就是`zval`不再单独从堆上分配内存并且不自己存储引用计数。需要使用`zval`指针的复杂类型（比如字符串、数组和对象）会自己存储引用计数。这样就可以有更少的内存分配操作、更少的间接指针使用以及更少的内存分配。

文章的第二部分我们会讨论复杂类型的问题。