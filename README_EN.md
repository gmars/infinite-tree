# infinite-tree

[中文文档](README.md) | [English manual](README_EN.md)

Php infinite tree toolkit

>I have written an infinite-level classification of the PHP package name tp5-nestedsets installed on the packagist is still quite large. But tp5-nestedsets is based on tp5 framework and it is obviously not flexible enough. Infinite-tree is an infinite-level classification package that is not restricted by the framework.

## About the application scenario
Someone who posted the tp5-nestedsets gave me a message saying that in general, there are only three levels of classification, and the three-level classification is bound by id and parent_id. Why is this infinite package necessary?

First of all, the classification of this tree-related data in the package is described by the tree relationship. The search and operation are all in accordance with the operation mode of the tree. Obviously, it is more convenient and efficient than traversing according to id and parent_id. Just take it simple. You need to query up to 3 times for all descendant classifications of a top-level classification of a three-level classification, and recursion is not efficient. But you can use this toolkit once to check it out. In fact, the level of classification in reality is really more than just three levels. The three levels we see are generally created by virtual classifications. The user feels that it is a three-level classification. In fact, it is much more complicated than this. It may not be this. The problem that the package can solve may be to use feature values, search, etc.
## Use
### Condition
Before using it, make sure you have the mysqli extension. Generally, PHP has this extension. It was originally intended to be compatible with the mysql extension, but the subsequent PHP version also abandoned the mysql extension and all are not compatible.
### Installation
```php
composer require gmars/infinite-tree
```
If you haven't used composer yet, please see the related tutorial for composer installation.

### Instantiating InfiniteTree
>Because you want to leave the specific framework, the database configuration needs to be configured by yourself. 

```php
/ / This part is the database configuration array
$dbConfig = [
    'hostname' => '127.0.0.1',
    'username' => 'root',
    'password' => 'root',
    'database' => 'test',
    'hostport' => 3306
];

/ / This part is the key configuration in the data table. If it is consistent with the default, you can configure it.
$keyConfig = [
    'left_key' => 'left_key',
    'right_key' => 'right_key',
    'level_key' => 'level',
    'primary_key' => 'id',
    'parent_key' => 'parent_id'
];
$infiniteTree = new InfiniteTree('tree', $dbConfig, $keyConfig);
```
The first parameter when instantiating is your table name, the second is the database configuration, and the third is the key mapping.

### Create a data table
If you haven't created a data table yet, this method will create a data table. Specific other required fields you can add manually after creating.
```php
$infiniteTree->checkTable()
```

### Method Description

Get the entire tree structure
```php
$infiniteTree->getTree()
```

Get all descendant nodes of $id (do not include themselves)
```php
$infiniteTree->getBranch($id)
```

Get all child nodes of $id (note that only child nodes)
```php
$infiniteTree->getChildren($id)
```

Get node $id and all descendant nodes
```php
$infiniteTree->getPath($id)
```

The child node inserted under the node $id contains the extended data name, and is inserted at the bottom of all child nodes of $id. If the third parameter is top, it is inserted before all child nodes.
```php
$infiniteTree->insert($id, ['name' => 'test node'], 'bottom')
```

Move the node with id 8 to the node with id 1
```php
$infiniteTree->moveUnder(8, 1)
```

Move the node with id 8 to the front of the node with id 2 (before) if you want to move to the back is after
```php
$infiniteTree->moveNear(8, 2, "before")
```

### Precautions

1. In the previous version, getItem has a static cache in the object. It feels unnecessary to add it in this version. Please control the cache yourself.
2. For the left and right values ​​to define the tree knowledge, if you are not very clear, please consult the corresponding information.
3. If you have any questions, please submit an issue.
4. If it feels convenient to use, please click on the star above.