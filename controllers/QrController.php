<?php
/**
 * Контроллер для работы с QR-кодами
 */
class QrController extends Controller
{
    /**
     * Модель сотрудников
     * @var EmployeeModel
     */
    private $employeeModel;
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация приложения
     * @param Database $db Экземпляр подключения к базе данных
     */
    public function __construct($config, $db)
    {
        parent::__construct($config, $db);
        
        // Инициализируем модель сотрудников
        $this->employeeModel = new EmployeeModel($db);
    }
    
    /**
     * Действие для отображения QR-кода сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function viewAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID сотрудника из параметров
        $employeeId = $params[0] ?? 0;
        
        // Получаем информацию о сотруднике
        $employee = $this->employeeModel->getById($employeeId);
        
        // Если сотрудник не найден, перенаправляем на список сотрудников
        if (!$employee) {
            $this->redirect('employee');
            return;
        }
        
        // Отображаем QR-код сотрудника
        $this->render('qr/view', [
            'employee' => $employee
        ]);
    }
    
    /**
     * Действие для генерации QR-кода
     * 
     * @param array $params Параметры запроса
     */
    public function generateAction($params)
    {
        // Получаем данные для QR-кода
        $data = $_GET['data'] ?? '';
        
        // Если данные не указаны, возвращаем ошибку
        if (empty($data)) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Данные для QR-кода не указаны';
            exit;
        }
        
        // Генерируем QR-код
        $this->generateQrCode($data);
    }
    
    /**
     * Генерирует QR-код и выводит его в браузер
     * 
     * @param string $data Данные для QR-кода
     */
    private function generateQrCode($data)
    {
        // Подключаем библиотеку для генерации QR-кодов
        require_once ROOT_DIR . '/lib/phpqrcode/qrlib.php';
        
        // Устанавливаем заголовок для изображения
        header('Content-Type: image/png');
        
        // Генерируем QR-код и выводим его в браузер
        QRcode::png($data, null, QR_ECLEVEL_L, 10, 2);
    }
    
    /**
     * Действие для печати QR-кода сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function printAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID сотрудника из параметров
        $employeeId = $params[0] ?? 0;
        
        // Получаем информацию о сотруднике
        $employee = $this->employeeModel->getById($employeeId);
        
        // Если сотрудник не найден, перенаправляем на список сотрудников
        if (!$employee) {
            $this->redirect('employee');
            return;
        }
        
        // Отображаем страницу для печати QR-кода
        $this->render('qr/print', [
            'employee' => $employee
        ]);
    }
}
