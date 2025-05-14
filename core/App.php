<?php
/**
 * Основной класс приложения SmartKyzmet
 * 
 * Отвечает за инициализацию компонентов приложения,
 * обработку запросов и маршрутизацию
 */
class App
{
    /**
     * Конфигурация приложения
     * @var array
     */
    private $config;
    
    /**
     * Экземпляр маршрутизатора
     * @var Router
     */
    private $router;
    
    /**
     * Экземпляр подключения к базе данных
     * @var Database
     */
    private $db;
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация приложения
     */
    public function __construct($config)
    {
        $this->config = $config;
        
        // Устанавливаем временную зону
        date_default_timezone_set($config['app']['timezone']);
        
        // Инициализируем компоненты приложения
        $this->initComponents();
    }
    
    /**
     * Инициализирует основные компоненты приложения
     */
    private function initComponents()
    {
        // Инициализируем подключение к базе данных
        $this->db = new Database(
            $this->config['database']['host'],
            $this->config['database']['dbname'],
            $this->config['database']['username'],
            $this->config['database']['password'],
            $this->config['database']['charset']
        );
        
        // Инициализируем маршрутизатор
        $this->router = new Router($this->config['routes']);
    }
    
    /**
     * Запускает приложение
     */
    public function run()
    {
        try {
            // Обрабатываем текущий запрос
            $this->processRequest();
        } catch (Exception $e) {
            // Обрабатываем исключения
            $this->handleException($e);
        }
    }
    
    /**
     * Обрабатывает текущий HTTP-запрос
     */
    private function processRequest()
    {
        // Получаем текущий URL
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        
        // Определяем контроллер и действие на основе URL
        $route = $this->router->resolve($url);
        
        // Создаем экземпляр контроллера
        $controllerName = $route['controller'] . 'Controller';
        $controller = new $controllerName($this->config, $this->db);
        
        // Вызываем действие контроллера
        $actionName = $route['action'] . 'Action';
        $controller->$actionName($route['params']);
    }
    
    /**
     * Обрабатывает исключения, возникшие в процессе работы приложения
     * 
     * @param Exception $e Исключение
     */
    private function handleException($e)
    {
        if ($this->config['app']['debug']) {
            // В режиме отладки выводим подробную информацию об ошибке
            echo '<h1>Ошибка</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            // В производственном режиме перенаправляем на страницу ошибки
            $errorController = $this->config['routes']['error_controller'] . 'Controller';
            $errorAction = $this->config['routes']['error_action'] . 'Action';
            
            $controller = new $errorController($this->config, $this->db);
            $controller->$errorAction(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Возвращает экземпляр подключения к базе данных
     * 
     * @return Database
     */
    public function getDb()
    {
        return $this->db;
    }
    
    /**
     * Возвращает конфигурацию приложения
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
