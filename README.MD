GridConfig
==========
Конфигурирование GridView с сохранением настроек каждого пользователя.

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pozitronik/yii2-grid-config/CI%20with%20PostgreSQL)

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

а модуль инжектирует в GridView панель конфигурации и всю обвязку.

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

Версии Bootstrap
----------------

Модуль поддерживает Bootstrap третьей (по умолчанию) и четвёртой версий. Переключение между версиями задаётся через параметр `Yii::$app->params->bsVersion`.

```php
'params' => [
    'bsVersion' => '3'//или '4'
]
```

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

Другие настройки модуля:

```php
'modules' => [
    'gridconfig' => [
        'class' => GridConfigModule::class,
        'params' => [
            'saveUrl' => '/my-controller-id/my-save-action', /* Адрес постинга применяемых настроек (например, если вы решили переопределить контроллер модуля своим) */
            'fixKartikFloatStyles' => false, /* Заменяет для всех «плавающих» элементов в kartik-v/yii2-grid стиль position: sticky; на position:fixed */
            'maxPageSize' => 20 /* Лимит разрешённого количества элементов на одну страницу (может быть переопределён в конфигурации каждого отдельного GridConfig::widget) */
        ]
    ]
]
```

Изменение layout GridView
-------------------------

Для базового `yii\grid\GridView` модуль модифицирует свойство $layout, добавляя к нему отрендеренную кнопку вызова модального окна с настройками. Её расположение изменить нельзя.

Для `kartik\grid\GridView` модуль добавляет тег {options}, при рендеринге виджета заменяемый на кнопку вызова модального окна с настройками. Этот тег можно использовать в свойстве $layout стандартным способом. По умолчанию он добавляется к $layout автоматически.   