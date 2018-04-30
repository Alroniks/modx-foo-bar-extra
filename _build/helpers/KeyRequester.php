<?php
/**
 * Copyright (c) 2018 Alroniks Experts LLC
 * @author: Ivan Klimchuk <ivan@klimchuk.com>
 * @package: demoshop
 */

include_once __DIR__ . '/ArrayXMLConverter.php';

/**
 * Class KeyRequester
 * Класс для получения пароля шифрования пакета от магазина дополнения
 * В конкретном частном случае - modstore.pro
 */
class KeyRequester
{
    /** @var string Ключ API, выдаваемый магазином после оплаты. Для автора - генерируется бесплатно. */
    const PARAM_API_KEY = 'api_key';
    /** @var string Имя пользователя для доступа к магазину. В случае modstore.pro - email пользователя. */
    const PARAM_USERNAME = 'username';
    /** @var string Хост сайта. В случае реального MODX - выдается домен, но можно заменить на mysite.dev или mysite.docker.*/
    const PARAM_HTTP_HOST = 'http_host';
    /** @var string Имя пакета (бещ версии) */
    const PARAM_PACKAGE = 'package';
    /** @var string Версия пакета вида 1.0.0-pl */
    const PARAM_VERSION = 'version';
    /** @var string Версия протокола взаимодействия с репозиторием. Обычно 1.1, но это новые специальные запросы, поэтому 2.0.0 */
    const PARAM_VEHICLE_VERSION = 'vehicle_version';

    private $xpdo;

    /** @var array Массив с параметрами для доступа к репозиторию */
    private $params = [];

    /**
     * KeyRequester constructor.
     * @param $xpdo
     * @param array $params
     */
    public function __construct(& $xpdo, $params = [])
    {
        $this->xpdo= & $xpdo;

        $this->params = [
            'api_key' => '',
            'username' => '',
            'http_host' => 'anysite.docker',
            'package' => '',
            'version' => '',
            'vehicle_version' => '2.0.0'
        ];

        // Переопределяем параметры теми, которые пришли из кода, которые использует этот класс.
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        // Отправляем запрос к репозиторию и конвертируем ответный XML в массив для удобной работы
        $answer = ArrayXMLConverter::toArray($this->request());

        // Если есть сообщение, значит это сообщение об ошибке.
        // Выводим сообщение и прекращаем работу скрипта сборки (так как без пароля она бесмысслена)
        if (isset($answer['message'])) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $answer['message']);
            echo $answer['message'];
            exit;
        }

        // Если сообщений нет, возвращаем ключ, т.е. пароль, который сгенерировал и выдал репозиторий.
        return $answer['key'];
    }

    /**
     * @return string
     */
    protected function request()
    {
        // Стандартный запрос из кода к удаленному сервису через библиотеку cURL.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://modstore.pro/extras/package/encode');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ArrayXMLConverter::toXML($this->params, 'request'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = trim(curl_exec($ch));
        curl_close($ch);

        return $result;
    }
}
