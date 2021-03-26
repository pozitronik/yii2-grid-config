GridConfig
==========
Конфигурирование GridView с сохранением настроек каждого пользователя.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run

```
php composer.phar require pozitronik/yii2-grid-config "dev-master"
```

or add

```
"pozitronik/yii2-grid-config": "dev-master"
```

to the require section of your `composer.json` file.


Requirements
------------

Yii2,
PHP >= 7.4.0

Описание и использование
------------------------

Идея модуля в том, чтобы позволить в рантайме конфигурировать *любой* экземпляр GridView, сохранить конфигурацию и применить её при следующем рендере GridView.
Это достигается тем, что вместо 
```php
GridView::widget([
    ...
]);
```
вызывается
```php
GridConfig::widget([
    'grid' => GridView::begin([//<==обратите внимание, нужно использовать именно ::begin() а не ::widget()
        ...
    ])
]);
```

а модуль инъектирует в GridView панель конфигурации и всю обвязку.

Для того, чтобы связать сохраняемые настройки с определённым GridView, потребуется вручную задать идентификатор виджету. Это можно сделать, определив параметр `id` либо для GridView, либо для GridConfig:

```php
GridConfig::widget([
    'id' => 'users-grid',//<==так
    'grid' => GridView::begin([
        'id' => 'users-grid',//<==либо так
        ...
    ])
]);
```

Этого достаточно, чтобы всё заработало.

Настраивать можно набор скрытых/отображаемых колонок, их порядок, пажинацию (количество отображаемых на одной странице записей) и наличие плавающего заголовка (если это поддерживает класс GridView). 

Поддерживаются как `yii\grid\GridView`, так и все наследуемые расширения, в т.ч. `kartik\grid\GridView`. 

Конфигурация
------------

Для хранения персональных настроек модуль опирается на расширение [pozitronik/yii2-users-options](https://github.com/pozitronik/yii2-users-options). После установки может потребоваться конфигурация этого расширения согласно документации и применение миграции:  

`yii migrate --migrationPath=@vendor/pozitronik/yii2-users-options/migrations`

Конфигурация модуля GridConfig в базовом случае сводится к его подключению в web.php: 

```php
'modules' => [
    'gridconfig' => [
        'class' => GridConfigModule::class
    ]
]
```

При необходимости, в параметрах модуля можно задать ключ saveUrl, отвечающий за адрес постинга применяемых настроек (например, если вы решили переопределить контроллер модуля своим):

```php
'gridconfig' => [
    'class' => GridConfigModule::class,
    'params' => [
        'saveUrl' => '/my-controller-id/my-save-action'    
    ]
]
```

Изменение layout GridView
-------------------------

Для базового `yii\grid\GridView` модуль модифицирует свойство $layout, добавляя к нему отрендеренную кнопку вызова модального окна с настройками. Её расположение изменить нельзя.

Для `kartik\grid\GridView` модуль добавляет тег {options}, при рендеринге виджета заменяемый на кнопку вызова модального окна с настройками. Этот тег можно использовать в свойстве $layout стандартным способом. По умолчанию он добавляется к $layout автоматически.   