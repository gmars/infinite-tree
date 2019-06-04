# infinite-tree
php的无限树工具包

>之前我已经写过一个无限级分类的PHP包名称叫tp5-nestedsets在packagist上的安装量还是挺大的。但是tp5-nestedsets是基于tp5的很显然其灵活性不够高。而infinite-tree是一个不受框架限制的无限级分类的包。

## 关于应用场景
之前发布的tp5-nestedsets有人给我留言说一般来说分类最多也只有三级而三级分类用id和parent_id来约束就可以了何必要这种无限级的包呢？

首先这个包中对于分类这种具有树关系的数据是以树关系来描述的，查找，操作都是按照树的操作方式，显然比我们根据id和parent_id来遍历要方便，而且高效。就拿简单的来说。你查一个三级分类的顶级分类的所有后代分类最多需要查询3次，递归也效率不高。但是使用本工具包一次就可以查完。其实现实中分类的层级还真的不止三级那么简单，我们所看到的三级一般都是创建了虚拟分类让用户感受到就是三级分类，事实上比这复杂得多，也可能不是这个包就能解决的问题，可能会用到特征值，搜索等。

## 使用
### 条件
使用之前要确保你有mysqli扩展，一般PHP都有这个扩展，本来打算兼容mysql扩展的但是后续的PHP版本也抛弃了mysql扩展所有没有兼容。

### 安装
```php
composer require gmars/infinite-tree
```
如果还没有使用composer请查看composer安装的相关教程

### 实例化InfiniteTree
>因为要脱离具体框架所以数据库配置需要自己配置，建议大家使用时可以再稍作封装，当然不封装也可以

```php
//这一部分是数据库配置数组
$dbConfig = [
    'hostname' => '127.0.0.1',
    'username' => 'root',
    'password' => 'root',
    'database' => 'test',
    'hostport' => 3306
];

//这一部分是数据表中的键配置如果和默认一致可以不用配置
$keyConfig = [
    'left_key' => 'left_key',
    'right_key' => 'right_key',
    'level_key' => 'level',
    'primary_key' => 'id',
    'parent_key' => 'parent_id'
];
$infiniteTree = new InfiniteTree('tree', $dbConfig, $keyConfig);
```
上边实例化时第一个参数是你的表名，第二个是数据库配置，第三个是键对应关系

### 创建数据表
如果你还没有创建数据表调用此方法会创建一个数据表。具体其他需要的字段你可以在创建完手动添加。
```php
$infiniteTree->checkTable()
```

### 方法说明

获取整个树结构
```php
$infiniteTree->getTree()
```

获取$id的所有后代节点（不包含自己）
```php
$infiniteTree->getBranch($id)
```

获取$id的所有子节点(注意只是子节点)
```php
$infiniteTree->getChildren($id)
```

获取节点$id及所有的后代节点
```php
$infiniteTree->getPath($id)
```

在节点$id下插入子节点包含扩展数据name,并且是在$id的所有子节点最后(bottom)插入如果第三个参数是top就是在所有子节点之前插入
```php
$infiniteTree->insert($id, ['name' => '测试节点'], 'bottom')
```

把id为8的节点移动到id为1的节点下
```php
$infiniteTree->moveUnder(8, 1)
```

把id为8的节点移动到id为2的节点前面（before）如果要移到后边是after
```php
$infiniteTree->moveNear(8, 2, "before")
```

### 注意事项

1. 在之前的版本中getItem有加对象中的静态缓存，感觉没有必要这个版本中没有加，缓存请自行控制
2. 关于左值和右值来定义树的知识如果不是很清楚请先查阅相应的资料
3. 如果有任何问题请提issue
4. 如果感觉用着很方便就star一下

