## Redis 排序

在上一章[Redis 数据类型及应用场景](https://github.com/Whale-Design/tech-blog/tree/master/Lips-gp/2019-11-24)中提到过一种可以排序的数据类型：有序集合。

有序集合的排序也有其弊端，无法像我们常用的 mysql 关系数据库一样完成连接查询功能。例如：
```
// 添加学生期末成绩
127.0.0.1:6379> zadd student:scores 99 1 89 2 96 3 74 4 88 5
(integer) 5
// 根据分数正序排列
127.0.0.1:6379> zrange student:scores 0 -1
1) "4"
2) "5"
3) "2"
4) "3"
5) "1"
// 根据分数倒序排列
127.0.0.1:6379> zrevrange student:scores 0 -1
1) "1"
2) "3"
3) "2"
4) "5"
5) "4"
```
如上我们需要先根据成绩排序获取学生ID，然后在根据学生ID获取相应学生信息。

### SORT 命令
SORT 命令可以对列表类型、集合类型和游戏集合类型键进行排序，并且可以完成与关系数据库中连接查询相类似的任务。

#### 1. SORT 命令一般用法
最简单的 SORT 使用方法是 SORT key 和 SORT key DESC ：
* SORT key 返回键值从小到大排序的结果。
* SORT key DESC 返回键值从大到小排序的结果。

如上学生期末成绩的有序集合中，根据学生ID分别进行正序和倒序：
```
// 正序
127.0.0.1:6379> sort student:scores
1) "1"
2) "2"
3) "3"
4) "4"
5) "5"
// 倒序
127.0.0.1:6379> sort student:scores desc
1) "5"
2) "4"
3) "3"
4) "2"
5) "1"
```

#### 2. 使用 ALPHA 修饰符对字符串进行排序
SORT 排序默认以数字作为对象，值被解释为双精度浮点数，然后进行比较。 也可以通过 ALPHA 修饰符对字符串进行排序。例如：
```
// 网址列表
127.0.0.1:6379> LPUSH website www.reddit.com www.infoq.com www.slashdot.com
(integer) 3
// 对网址列表进行默认排序时报错
127.0.0.1:6379> sort website
"(error) ERR One or more scores can't be converted into double"
// 通过 ALPHA 修饰符实现对字符串排序
127.0.0.1:6379> sort website alpha
1) "www.infoq.com"
2) "www.reddit.com"
3) "www.slashdot.com"
```

#### 3. 通过 BY 参数可以对其他键排序
如上学生期末成绩的有序集合中，进行排序是默认是对学生ID排序的，可以通过 BY 关键字对 score 键排序：
```
// 正序
127.0.0.1:6379> sort student:scores by score
1) "4"
2) "5"
3) "2"
4) "3"
5) "1"
// 倒序
127.0.0.1:6379> sort student:scores desc by score
1) "1"
2) "3"
3) "2"
4) "5"
5) "4"
```

BY 参数还可以使用外部 key 的数据作为权重来进行排序。如下表：
| uid | user_name_{uid} | user_level_{uid} |
| -- | -- | -- |
| 1	| admin | 9999 |
| 2 | jack | 10 |
| 3 | peter	| 25 |
| 4 | mary | 70 |

将用户名和用户权限均以字符串类型存储到 redis 中：
```
127.0.0.1:6379> LPUSH uid 1 2 3 4
(integer) 4
127.0.0.1:6379> SET user_name_1 admin
OK
127.0.0.1:6379> SET user_level_1 9999
OK
127.0.0.1:6379> SET user_name_2 jack
OK
127.0.0.1:6379> SET user_level_2 10
OK
127.0.0.1:6379> SET user_name_3 peter
OK
127.0.0.1:6379> SET user_level_3 25
OK
127.0.0.1:6379> SET user_name_4 mary
OK
127.0.0.1:6379> SET user_level_4 70
OK
// 使用外部key进行排序
127.0.0.1:6379> sort uid by user_level_*
1) "2"
2) "3"
3) "4"
4) "1"
```

除了可以将字符串键之外， 哈希表也可以作为 BY 选项的参数来使用。将上表存为 redis 哈希表：
```
127.0.0.1:6379> HMSET user_info_1 name admin level 9999
OK
127.0.0.1:6379> HMSET user_info_2 name jack level 10
OK
127.0.0.1:6379> HMSET user_info_3 name peter level 25
OK
127.0.0.1:6379> HMSET user_info_4 name mary level 70
OK
// 以哈希表的level字段值排序
127.0.0.1:6379> sort uid by user_info_*->level
1) "2"
2) "3"
3) "4"
4) "1"
```

#### 4. 通过 GET 参数返回其他键的值
使用 GET 选项，可以根据排序的结果来取出相应的键值。如：
```
// 获取字符串值
127.0.0.1:6379> sort uid get user_name_*
1) "admin"
2) "jack"
3) "peter"
4) "mary"
// 同样也可以获取哈希表相应键值
127.0.0.1:6379> sort uid by user_info_*->level get user_info_*->name
1) "jack"
2) "peter"
3) "mary"
4) "admin"
```

#### 5. STORE 参数
默认情况下 SORT 命令会直接返回排序结果，如果希望保存排序结果，可以使用 store 参数。如：
```
127.0.0.1:6379> sort uid by user_info_*->level get user_info_*->name store sort:user:level
(integer) 4
127.0.0.1:6379> lrange sort:user:level 0 -1
1) "jack"
2) "peter"
3) "mary"
4) "admin"
```
保存后的键的类型为列表类型，如果键已经存在则会覆盖它。加上 store 参数后 sort 命令返回值为结果的个数。