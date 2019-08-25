## LRU算法
LRU是Least Recently Used的缩写，即最近最少使用，是一种常用的页面置换算法，选择最近最久未使用的页面予以淘汰。该算法赋予每个页面一个访问字段，用来记录一个页面自上次被访问以来所经历的时间 t，当须淘汰一个页面时，选择现有页面中其 t 值最大的，即最近最少使用的页面予以淘汰。  

最近最少使用算法（LRU）是大部分操作系统及缓存系统（Memcache,Redis）为最大化页面命中率而广泛采用的一种页面置换算法（淘汰策略）。

如图所示：

![GitHub Logo](./lru.png)

## JAVA实现LRU算法
``` java
/**
 * java实现LRU算法
 */

import java.util.Scanner;
import java.util.Arrays;

public class LRU {
	// 内存大小
	private static int N = 5;

	// 实例化内存
	private static int[] storage = new int[N];

	// 当前内存占用
	private static int size = 0;

	public static void main(String[] args){
		while(true){
			Scanner sc = new Scanner(System.in);
			System.out.println("请输入一个整数");

			int input = sc.nextInt();
			push(input);
			System.out.println(Arrays.toString(storage));
		}
	}

	/**
	 * 新数据插入内存
	 * @param element 要插入内存的数据
	 */
	public static void push(int element){
		if (!isOutOfBoundary() && indexOfElement(element) == -1) {
			// 内存未满 且 内存中没有准备进入内存的元素
			storage[size] = element;
			size ++;	
		} else if(isOutOfBoundary() && indexOfElement(element) == -1){
			// 内存已满 且 内存中没有准备进入内存的元素
			for(int i=0; i<size-1; i++) {
				storage[i] = storage[i+1];				
			}
			storage[size-1] = element;
		} else {
			// 内存已满 且 内存中已有准备进入内存的元素
			int t = indexOfElement(element);
			for(int i=t; i<size-1; i++) {
				storage[i] = storage[i+1];
			}
			storage[size-1] = element;
		}
	}

	/**
	 * 判断内存区是否达到最大值
	 * @return
	 */
	public static boolean isOutOfBoundary() {
		if(size >=N) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 查找元素element在数组中的位置
	 * @param element
	 * @return
	 */
	public static int indexOfElement(int element) {
		for(int i=0; i<N; i++) { 
			if(element == storage[i]) {
				return i;
			}
		}
		return -1;
	}	

}
```