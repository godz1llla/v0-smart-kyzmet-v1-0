<?php
/**
 * Контроллер для авторизации пользователей
 */
class AuthController extends Controller
{
    /**
     * Модель пользователей
     * @var UserModel
     */
    private $userModel;
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация приложения
     * @param Database $db Экземпляр подключения к базе данных
     */
    public function __construct($config, $db)
    {
        parent::__construct($config, $db);
        
        // Инициализируем модель пользователей
        $this->userModel = new UserModel($db);
    }
    
    /**
     * Действие для отображения формы входа
     * 
     * @param array $params Параметры запроса
     */
    public function loginAction($params)
    {
        // Если пользователь уже авторизован, перенаправляем на главную страницу
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }
        
        // Отображаем форму входа
        $this->render('auth/login');
    }
    
    /**
     * Действие для обработки формы входа
     * 
     * @param array $params Параметры запроса
     */
    public function authenticateAction($params)
    {
        // Если метод запроса не POST, перенаправляем на форму входа
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
            return;
        }
        
        // Получаем данные из формы
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Проверяем учетные данные
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            // Если учетные данные верны, сохраняем информацию о пользователе в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Перенаправляем на главную страницу
            $this->redirect('dashboard');
        } else {
            // Если учетные данные неверны, отображаем форму входа с ошибкой
            $this->render('auth/login', [
                'error' => 'Неверное имя пользователя или пароль',
                'username' => $username
            ]);
        }
    }
    
    /**
     * Действие для выхода из системы
     * 
     * @param array $params Параметры запроса
     */
    public function logoutAction($params)
    {
        // Удаляем информацию о пользователе из сессии
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_role']);
        
        // Перенаправляем на форму входа
        $this->redirect('auth/login');
    }
}
