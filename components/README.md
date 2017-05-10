# Yii2 Components

These extensions provides aliyun components, redis component and some other components support for the [Yii framework 2.0](http://www.yiiframework.com/)



## Overall

### installation

```shell
# install components
git subtree add -P components git@github.com:xiaoliwang/yii2_components.git master --squash

# update components
git subtree pull -P components git@github.com:xiaoliwang/yii2_components.git master
```

 

## Redis

### Requirements

- PHP >= 7.1
- ext-redis >=3.1.1
- yii2 >= 2.0.5

### Configuration

```php
// vendor/yiisoft/yii2/base/Application.php
/**
 * ...
 * @property \app\components\redis\Connection $redis The redis connection. This property is read-only.
 */

// config/console.php or config/web.php
return [
	// ...
    'components' => [
        'redis' => [
            'class' => 'app\components\redis\Connection',
          	'hostname' => 'localhost',
          	'port' => 6379,
            'database' => 0
        ]
    ]
];
```

