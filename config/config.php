<?php
/**
 * Файл конфигурации проекта SmartKyzmet
 * 
 * Содержит основные настройки приложения, параметры подключения к БД,
 * пути к директориям и другие конфигурационные параметры
 */

return [
    // Настройки базы данных
    'database' => [
        'host' => 'localhost',
        'dbname' => 'smartkyzmet',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    
    // Настройки приложения
    'app' => [
        'name' => 'SmartKyzmet',
        'debug' => true,
        'timezone' => 'Asia/Almaty',
        'base_url' => 'http://localhost/smartkyzmet'
    ],
    
    // Настройки маршрутизации
    'routes' => [
        'default_controller' => 'Auth',
        'default_action' => 'login',
        'error_controller' => 'Error',
        'error_action' => 'notFound'
    ],
    
    // Настройки безопасности
    'security' => [
        'salt' => 'SmartKyzmet2025SecureKey',
        'session_lifetime' => 3600 // 1 час
    ],
    
    // Настройки для ИИ-модуля
    'ai_module' => [
        'python_service_url' => 'http://localhost:5000',
        'ollama_api_url' => 'http://localhost:11434/api/generate',
        'model' => 'gemma3:1b'
    ]
];
