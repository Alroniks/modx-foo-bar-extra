<?php
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Ivan Klimchuk <ivan@klimchuk.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * FooBar package builder
 *
 * @author Ivan Klimchuk <ivan@klimchuk.com>
 * @package foorbar
 */

// Удаляем стандартные ограничения времени выполнения скрипта,
// чтобы все долгие операции сборки гарантированно выполнились.
set_time_limit(0);

// xPDO требует, чтобы была установлена правильная временная зона в настройках PHP
ini_set('date.timezone', 'Europe/Minsk');

// Здесь описывается блок констант с информацией о нашем пакете.
define('PKG_NAME', 'FooBar'); // Название пакета для людей
define('PKG_NAME_LOWER', strtolower(PKG_NAME)); // Название для репозитория и прочих программ
define('PKG_VERSION', '1.0.0'); // Версия пакета
define('PKG_RELEASE', 'pl'); // Релиз пакета (MODX все еще требует этот рудимент, можно всегда оставлять pl)

// Здесь мы подключаем классы, которые нам понадобятся при сборке пакета.
require_once __DIR__ . '/xpdo/xpdo/xpdo.class.php';
require_once __DIR__ . '/xpdo/xpdo/transport/xpdotransport.class.php';
require_once __DIR__ . '/xpdo/xpdo/transport/xpdovehicle.class.php';
require_once __DIR__ . '/xpdo/xpdo/transport/xpdofilevehicle.class.php';
require_once __DIR__ . '/xpdo/xpdo/transport/xpdoscriptvehicle.class.php';
require_once __DIR__ . '/xpdo/xpdo/transport/xpdoobjectvehicle.class.php';

// Здесь мы подключаем вспомогательные классы, которые используются во время сборки пакета.
require_once __DIR__ . '/helpers/KeyRequester.php'; // Класс для получения пароля для шифрования пакета.
require_once __DIR__ . '/implants/encryptedvehicle.class.php'; // Класс шифровальщика.
// Можно писать имя класса в виде строки вот так 'EncryptedVehicle',
// но удобнее пользоваться новыми возможностями PHP и писать так 'EncryptedVehicle::class'.
// Для этого класс должен быть подключен явно или через автозагрузчик.

// Создаем реальный объект класса xPDO.
// Параметры указывать обязательно, но настоящая базад данных не требуется.
// Будет работать и так, разве что с предупреждениями. Но это никак не влияет на результат.
$xpdo = xPDO::getInstance('db', [
    xPDO::OPT_CACHE_PATH => __DIR__ . '/../cache/',
    xPDO::OPT_HYDRATE_FIELDS => true,
    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    xPDO::OPT_CONNECTIONS => [
        [
            'dsn' => 'mysql:host=localhost;dbname=xpdotest;charset=utf8',
            'username' => 'test',
            'password' => 'test',
            'options' => [xPDO::OPT_CONN_MUTABLE => true],
            'driverOptions' => [],
        ]
    ]
]);

// Устанавливаем логирование ошибок. Можете тут поэкспериментировать.
$xpdo->setLogLevel(xPDO::LOG_LEVEL_FATAL);
$xpdo->setLogTarget();

// xPDO знает только о своих классах, а чтобы создавать сущности MODX - нужно покдлючать классы из MODX.
// Но у нас его нет, так что нужно эти классы сделать искуственно. Нам не нужы настоящие классы, только их заглушки,
// чтобы xPDO не ругался на несуществующие классы. Небольшой хак, так как по другому во 2 версии нельзя.
class modNamespace extends xPDOObject {}
class modCategory extends xPDOObject {
    public function getFKDefinition($alias)
    {
        $aggregates = [
            'Plugins' => [
                'class' => 'modPlugin',
                'local' => 'id',
                'foreign' => 'category',
                'cardinality' => 'many',
                'owner' => 'local',
            ]
        ];

        return isset($aggregates[$alias]) ? $aggregates[$alias] : [];
    }
}
class modPlugin extends xPDOObject {
    public function getFKDefinition($alias)
    {
        $aggregates = [
            'PluginEvents' => [
                'class' => 'modPluginEvent',
                'local' => 'id',
                'foreign' => 'pluginid',
                'cardinality' => 'one',
                'owner' => 'local',
            ]
        ];

        return isset($aggregates[$alias]) ? $aggregates[$alias] : [];
    }
}
class modPluginEvent extends xPDOObject {}
class modSystemSetting extends xPDOObject {}
class msPayment extends xPDOObject {}

// Создаем массив с некоторыми настройками для сборки пакета.
// Острой необходимссти нет, но просто удобнее работать, когда пакет большой и нужно что-то поменять.
// Поменять в одном месте намного проще чем в 10 по всему файлу. И ошибок меньше.
$root = dirname(dirname(__FILE__)) . '/';
$sources = [
    'build' => $root . '_build/',
    'data' => $root . '_build/data/',
    'docs' => $root . 'docs/',
    'resolvers' => $root . '_build/resolvers/',
    'validators' => $root . '_build/validators/',
    'implants' => $root . '_build/implants/',
    'plugins' => $root . 'core/components/' . PKG_NAME_LOWER . '/elements/plugins/',
    'assets' => ['components/' . PKG_NAME_LOWER . '/'],
    'core' => ['components/' . PKG_NAME_LOWER . '/'],
];

// Полная сигнатура пакета, т.е. foobar-1.0.0-pl
$signature = join('-', [PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE]);
// Каталог, куда будет собираться пакет (архив)
$directory = $root . '_packages/';
// Имя файла уже собранного пакета (нужно, чтобы удалять уже собранные перед новой сборкой)
$filename = $directory . $signature . '.transport.zip';

// Если файл пакета уже есть, то удаляем перед новой сборкой
if (file_exists($filename)) {
    unlink($filename);
}
// Кроме файла удаляем и папку с элементами пакета и чистим кеш xPDO (да, кеш - это фишка xPDO, а не MODX)
if (file_exists($directory . $signature) && is_dir($directory . $signature)) {
    $cacheManager = $xpdo->getCacheManager();
    if ($cacheManager) {
        $cacheManager->deleteTree($directory . $signature, true, false, []);
    }
}

$keyRequester = new KeyRequester([
    KeyRequester::PARAM_API_KEY => 'api_key_from_modstore.pro', // ключ из modstore.pro
    KeyRequester::PARAM_USERNAME => 'email@on-mostore.pro', // email аккаунта на modstore.pro
    KeyRequester::PARAM_PACKAGE => PKG_NAME_LOWER, // имя пакета
    KeyRequester::PARAM_VERSION => PKG_VERSION . '-' . PKG_RELEASE // версия пакета
]);

// Когда получили пароль для шифрования, сохраняем его в константу,
// значение которой будет использоваться в классе шифровальщика
// при шифровании уже отдельных элементов пакета.
define('PKG_ENCODE_KEY', $keyRequester->getKey());

// Создаем объект самого транспортного пакета
// Передаем xpdo, сигнатуру и каталог, куда положить файл с пакетом
$package = new xPDOTransport($xpdo, $signature, $directory);

// Сначала добавляем транспортный объект файлового типа
// И в параметрах указываем источник, откуда брать файл с классом (в папке implants нашего пакета)
// а при установке кладем его в папку с нашим компонентов.
// За это отвечает команда, описанная в 'target'
$package->put(new xPDOFileVehicle, [
    'vehicle_class' => 'xPDOFileVehicle',
    'object' => [
        'source' => $sources['implants'] . 'encryptedvehicle.class.php',
        'target' => "return MODX_CORE_PATH . 'components/" . PKG_NAME_LOWER . "/';"
    ]
]);

// Затем, когда файл скопирован, добавляем специальный скрипт,
// который запустится следом за копированием файла и зарегистрирует класс шифровальщика в MODX
$package->put(new xPDOScriptVehicle, [
    'vehicle_class' => 'xPDOScriptVehicle',
    'object' => [
        'source' => $sources['resolvers'] . 'resolve.encryption.php'
    ]
]);

// Теперь начинаем добавлять настоящие элементы в пакет,
// которые после установки будут созданы и доступны в системе

// Создаем объект пространства имен для нашего пакета
$namespace = new modNamespace($xpdo);
$namespace->fromArray([
    'id' => PKG_NAME_LOWER,
    'name' => PKG_NAME_LOWER,
    'path' => '{core_path}components/' . PKG_NAME_LOWER . '/',
]);

// И тут же его добавляем в наш пакет
$package->put($namespace, [
    // в качестве типа транспортного объекта указываем EncryptedVehicle
    // это значит, что данный объект зашифрует свое содержимое
    // и при установке будет пытаться его расшифровать
    'vehicle_class' => EncryptedVehicle::class,
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::NATIVE_KEY => PKG_NAME_LOWER,
    'namespace' => PKG_NAME_LOWER
]);

// Подключаем наш массив объектов системных настроек
$settings = include $sources['data'] . 'transport.settings.php';
// И точно так же создаем транспортные объекты для каждой настройки
// и кладем их в пакет с шифрованным типом
foreach ($settings as $setting) {
    $package->put($setting, [
        'vehicle_class' => EncryptedVehicle::class,
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => true,
        'class' => 'modSystemSetting',
        'namespace' => PKG_NAME_LOWER
    ]);
}

// Так как пакет у нас простой, у нас тут нет специальных файловых резолверов, поэтому они здесь пропущены
// Но есть вызов резолвера в виде скрипта, который будет выполняться при удалении пакета
// и удалять установленные ранее пакетом системные настройки.
array_push($resolvers,
    ['type' => 'php', 'source' => $sources['resolvers'] . 'resolve.settings.php']
);

// Здесь в пакет добавляем мета-ифнормацию. Эта информация используется репозиторием и
// механизмом управления пакетами в самом MODX
// Файл изменений для пакета
$package->setAttribute('changelog', file_get_contents($sources['docs'] . 'changelog.txt'));
// Файл с лицензией
$package->setAttribute('license', file_get_contents($sources['docs'] . 'license.txt'));
// Файл описания пакета
$package->setAttribute('readme', file_get_contents($sources['docs'] . 'readme.txt'));
// Если нужно, указываем зависимости для пакета (минимальная версия PHP, MODX и/или наличие других компонентов)
$package->setAttribute('requires', [
    'php' => '>=5.5',
    'modx' => '>=2.6'
]);

// Ну и наконец, упаковываем наш пакет. Результат будет доступен в папке _packages, если вы не меняли настройки выше.
if ($package->pack()) {
    $xpdo->log(xPDO::LOG_LEVEL_INFO, "Package built");
}
