# 如何实现可以获取最小值的栈？

> 作者：channingbreeze

> 来源：互联网侦察 | 微信公众号

### 题目：我现在需要实现一个栈，这个栈除了可以进行普通的push、pop操作以外，还可以进行getMin的操作，getMin方法被调用后，会返回当前栈的最小值，你会怎么做呢？你可以假设栈里面存的都是int整数。

#### 方法1：用一个变量保存最小值，在push的时候更新这个最小值

1. push(5)

min: 5

|data|
|----|
|5   |

2. push(6)

min: 5

|data|
|----|
|6   |
|5   |

3. push(4)

min: 4

|data|
|----|
|4   |
|6   |
|5   |

4. getMin()  直接返回min

问：pop的时候将最小值4出栈了，谁是下一个最小值？

答：遍历栈内元素，再更新最小值

问：时间复杂度是多少？

答：pop是o(1)，其他操作是o(1)，空间复杂度是o(1)

问：如果要求时间尽可能短，怎么办？

答：用空间换时间

#### 方法2：使用辅助栈，辅助栈mins存储每次push时当时的最小值，pop时两栈同步pop

1. push(5)

|data|mins|
|----|----|
|5   |5   |

2. push(6)

|data|mins|
|----|----|
|6   |5   |
|5   |5   |

3. push(4)

|data|mins|
|----|----|
|4   |4   |
|6   |5   |
|5   |5   |

4. getMin()  直接返回mins的栈顶元素4

5. pop()

|data|mins|
|----|----|
|6   |5   |
|5   |5   |

6. getMin()  直接返回mins的栈顶元素5

问：这样的复杂度是？

答：所有操作时间复杂度o(1)，空间复杂度o(n)

问：能再优化么？

答：……

问：能写出代码么？

答：

```
public class MinStack {

    private List<Integer> data = new ArrayList<Integer>();
    private List<Integer> mins = new ArrayList<Integer>();

    public void push(int num) {
        data.add(num);
        if(mins.size() == 0) {
            // 初始化mins
            mins.add(num);
        } else {
            // 辅助栈mins每次push当时最小值
            int min = getMin();
            if (num >= min) {
                mins.add(min);
            } else {
                mins.add(num);
            }
        }
    }

    public int pop() {
        // 栈空，异常，返回-1
        if(data.size() == 0) {
            return -1;
        }
        // pop时两栈同步pop
        mins.remove(mins.size() - 1);
        return data.remove(data.size() - 1);
    }

    public int getMin() {
        // 栈空，异常，返回-1
        if(mins.size() == 0) {
            return -1;
        }
        // 返回mins栈顶元素
        return mins.get(mins.size() - 1);
    }

}
```

问：这个代码的异常处理有没有问题？

答：可以大家商量，比如定-1为异常返回值。

问：当栈内为空的时候，你返回-1，但是如果用户push过-1，那么你返回-1的时候，是用户push进来的值，还是栈为空，就不得而知了。

答：再定义一个类，包含一个int的data和一个boolean的isSuccess，正常情况下isSuccess是true，栈为空的话，isSuccess是false。

问：这样问题复杂化了，调用者需要用一个类去接收返回值，然后再从里边取真正的数。

答：可以用一个包装类Integer来定义返回值，如果是空，代表栈为空。它和int的区别就是它多了一个null，正好用来返回异常情况。

问：还是有点问题。你并没有站在使用者的角度考虑问题。使用你这个栈的人，在pop的时候，他并不知道可能返回null，如果他不做判断，后面的代码就可能抛出空指针了。为什么不用java推荐的标准方法？其他语言也有类似的处理方式。显式的抛出异常，如果调用者不捕获，编译就会报错，这样把错误暴露在编译阶段，并且不需要和任何人商量所谓的特殊返回值了。空间占用能不能再小？如果依次入栈2、1、2、3、4、5、6，辅助栈会怎样？

答：

|data|mins|
|----|----|
|6   |1   |
|5   |1   |
|4   |1   |
|3   |1   |
|2   |1   |
|1   |1   |
|2   |2   |

问：辅助栈后面全是1，且大量重复，可以优化。

答：可以在push的时候判断一下，如果比最小值还大，就不加入辅助栈，这样mins栈中不会保存大量冗余的最小值。pop的时候同样进行判断，只有pop出的数就是当前最小值的时候，才让mins出栈。这样一来，辅助栈就不会有大量重复元素了。

1. push(2)  两边都push

|data|mins|
|----|----|
|2   |2   |

2. push(1)  1比2小，两边都push

|data|mins|
|----|----|
|1   |1   |
|2   |2   |

3. push(2)  2比1大，mins不push

|data|mins|
|----|----|
|2   |1   |
|1   |2   |
|2   |    |

4. pop()  出栈的2不等于1，mins不动

|data|mins|
|----|----|
|1   |1   |
|2   |2   |

5. pop()  出栈的1等于最小值1，mins出栈

|data|mins|
|----|----|
|2   |2   |

6. getMin()  直接返回mins栈顶元素2

问：如果来了一个和最小值相等的数，要不要进辅助栈？

答：如果push一个和最小值相等的元素，还是要入mins栈。不然当这个最小值pop出去的时候。data中还会有一个最小值元素，而mins中却已经没有最小值元素了。

1. push(2)

|data|mins|
|----|----|
|2   |2   |

2. push(1)  1比2小，两边都push

|data|mins|
|----|----|
|1   |1   |
|2   |2   |

3. push(1)  1等于1，如果不进mins栈

|data|mins|
|----|----|
|1   |1   |
|1   |2   |
|2   |    |

4. pop()  pop出的1等于最小值1，mins出栈

|data|mins|
|----|----|
|1   |2   |
|2   |    |

5. getMin()  直接返回mins栈顶元素2，出错

问：如果入栈顺序是2、1、1、1、1、1、1、1，辅助栈里还是一堆1，还可以优化，如果辅助栈里不存值，而是存索引呢？

#### 方法3：辅助栈不存值，而是存最小值的索引

1. push(2) mins索引0

|data|mins|
|----|----|
|2   |0   |

2. push(1)  1比2小，mins要push索引1

|data|mins|
|----|----|
|1   |1   |
|2   |0   |

3. push(1)  mins栈不动

|data|mins|
|----|----|
|1   |1   |
|1   |0   |
|2   |    |

4. pop()  pop出的1索引为2，mins不动

|data|mins|
|----|----|
|1   |1   |
|2   |0   |

5. getMin()  mins栈顶索引1，从data中找到索引1的数据

mins栈中改存最小值在data数组中的索引。当push了与最小值相同元素的时候，就不需要动mins栈。而pop的时候，pop出的元素的索引如果不是mins栈顶元素，mins也不出栈。同时，获取最小值的时候，需要拿到mins栈顶元素作为索引，再去data数组中找到相应的数作为最小值。

```
public class MinStack {

    private List<Integer> data = new ArrayList<Integer>();
    private List<Integer> mins = new ArrayList<Integer>();

    public void push(int num) {
        data.add(num);
        if(mins.size() == 0) {
            // 初始化mins
            mins.add(0);
        } else {
            // 辅助栈mins push最小值的索引
            int min = getMin();
            if (num < min) {
                mins.add(data.size() - 1);
            }
        }
    }

    public int pop() {
        // 栈空，抛出异常
        if(data.size() == 0) {
            throw new EmptyStackException();
        }
        // pop时先获取索引
        int popIndex = data.size() - 1;
        // 获取mins栈顶元素，它是最小值索引
        int minIndex = mins.get(mins.size() - 1);
        // 如果pop出去的索引就是最小值索引，mins才出栈
        if(popIndex == minIndex) {
            mins.remove(mins.size() - 1);
        }
        return data.remove(data.size() - 1);
    }

    public int getMin() {
        // 栈空，抛出异常
        if(data.size() == 0) {
            throw new EmptyStackException();
        }
        // 获取mins栈顶元素，它是最小值索引
        int minIndex = mins.get(mins.size() - 1);
        return data.get(minIndex);
    }
}
```

数据结构和算法的设计是一个程序员的内功，工作时虽然用不到这么细，但是你在学习其他知识的底层原理的时候，到处都是数据结构和算法。