<?php
/**
 * Базовый класс контроллера для проекта SmartKyzmet
 * 
 * Предоставляет общую функциональность для всех контроллеров
 */
class Controller
{
    /**
     * Конфигурация приложения
     * @var array
     */
    protected $config;
    
    /**
     * Экземпляр подключения к базе данных
     * @var Database
     */
    protected $db;
    
    /**
     * Данные для представления
     * @var array
     */
    protected $viewData = [];
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация приложения
     * @param Database $db Экземпляр подключения к базе данных
     */
    public function __construct($config, $db)
    {
        $this->config = $config;
        $this->db = $db;
    }
    
    /**
     * Отображает представление
     * 
     * @param string $view Имя представления
     * @param array $data Данные для представления
     */
    protected function render($view, $data = [])
    {
        // Объединяем данные представления
        $this->viewData = array_merge($this->viewData, $data);
        
        // Извлекаем переменные из массива данных
        extract($this->viewData);
        
        // Определяем путь к файлу представления
        $viewFile = ROOT_DIR . '/views/' . $view . '.php';
        
        // Проверяем существование файла представления
        if (!file_exists($viewFile)) {
            throw new Exception('Представление "' . $view . '" не найдено');
        }
        
        // Начинаем буферизацию вывода
        ob_start();
        
        // Подключаем файл представления
        require_once $viewFile;
        
        // Получаем содержимое буфера
        $content = ob_get_clean();
        
        // Отображаем содержимое
        echo $content;
    }
    
    /**
     * Перенаправляет на указанный URL
     * 
     * @param string $url URL для перенаправления
     */
    protected function redirect($url)
    {
        header('Location: ' . $this->config['app']['base_url'] . '/' . $url);
        exit;
    }
    
    /**
     * Проверяет, авторизован ли пользователь
     * 
     * @return bool Авторизован ли пользователь
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Проверяет, имеет ли пользователь указанную роль
     * 
     * @param string|array $roles Роль или массив ролей
     * @return bool Имеет ли пользователь указанную роль
     */
    protected function hasRole($roles)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Если передана одна роль, преобразуем ее в массив
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Требует авторизации пользователя
     * 
     * Если пользователь не авторизован, перенаправляет на страницу входа
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('auth/login');
        }
    }
    
    /**
     * Требует наличия у пользователя указанной роли
     * 
     * Если у пользователя нет указанной роли, перенаправляет на страницу ошибки
     * 
     * @param string|array $roles Роль или массив ролей
     */
    protected function requireRole($roles)
    {
        if (!$this->hasRole($roles)) {
            $this->redirect('error/forbidden');
        }
    }
}
