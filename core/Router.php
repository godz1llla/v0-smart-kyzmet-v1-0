<?php
/**
 * Класс маршрутизатора для проекта SmartKyzmet
 * 
 * Отвечает за разбор URL и определение контроллера и действия
 */
class Router
{
    /**
     * Конфигурация маршрутизации
     * @var array
     */
    private $config;
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация маршрутизации
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Разрешает URL в контроллер и действие
     * 
     * @param string $url URL для разрешения
     * @return array Массив с контроллером, действием и параметрами
     */
    public function resolve($url)
    {
        // Разбиваем URL на части
        $urlParts = explode('/', trim($url, '/'));
        
        // Определяем контроллер
        $controller = !empty($urlParts[0]) ? ucfirst($urlParts[0]) : $this->config['default_controller'];
        
        // Определяем действие
        $action = !empty($urlParts[1]) ? $urlParts[1] : $this->config['default_action'];
        
        // Определяем параметры
        $params = array_slice($urlParts, 2);
        
        // Проверяем существование контроллера и действия
        if (!$this->controllerExists($controller)) {
            $controller = $this->config['error_controller'];
            $action = $this->config['error_action'];
            $params = ['message' => 'Контроллер не найден'];
        } elseif (!$this->actionExists($controller, $action)) {
            $controller = $this->config['error_controller'];
            $action = $this->config['error_action'];
            $params = ['message' => 'Действие не найдено'];
        }
        
        return [
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        ];
    }
    
    /**
     * Проверяет существование контроллера
     * 
     * @param string $controller Имя контроллера
     * @return bool Существует ли контроллер
     */
    private function controllerExists($controller)
    {
        $controllerName = $controller . 'Controller';
        $controllerFile = ROOT_DIR . '/controllers/' . $controllerName . '.php';
        
        return file_exists($controllerFile);
    }
    
    /**
     * Проверяет существование действия в контроллере
     * 
     * @param string $controller Имя контроллера
     * @param string $action Имя действия
     * @return bool Существует ли действие
     */
    private function actionExists($controller, $action)
    {
        $controllerName = $controller . 'Controller';
        $actionName = $action . 'Action';
        
        // Если контроллер не существует, действие тоже не существует
        if (!$this->controllerExists($controller)) {
            return false;
        }
        
        // Загружаем файл контроллера, если он еще не загружен
        $controllerFile = ROOT_DIR . '/controllers/' . $controllerName . '.php';
        if (!class_exists($controllerName)) {
            require_once $controllerFile;
        }
        
        // Проверяем наличие метода в классе
        return method_exists($controllerName, $actionName);
    }
}
