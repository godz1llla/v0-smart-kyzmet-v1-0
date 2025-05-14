<?php
/**
 * Главный входной файл проекта SmartKyzmet
 * 
 * Этот файл инициализирует приложение, загружает конфигурацию,
 * обрабатывает запросы и маршрутизирует их к соответствующим контроллерам
 */

// Определяем корневую директорию проекта
define('ROOT_DIR', __DIR__);

// Загружаем автозагрузчик классов
require_once ROOT_DIR . '/core/Autoloader.php';

// Инициализируем автозагрузчик
$autoloader = new Autoloader();
$autoloader->register();

// Загружаем конфигурацию
$config = require_once ROOT_DIR . '/config/config.php';

// Инициализируем сессию
session_start();

// Создаем экземпляр приложения
$app = new App($config);

// Запускаем приложение
$app->run();
