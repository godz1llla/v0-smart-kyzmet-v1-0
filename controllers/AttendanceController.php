<?php
/**
 * Контроллер для работы с журналом посещений
 */
class AttendanceController extends Controller
{
    /**
     * Модель посещений
     * @var AttendanceModel
     */
    private $attendanceModel;
    
    /**
     * Модель сотрудников
     * @var EmployeeModel
     */
    private $employeeModel;
    
    /**
     * Модель отделов
     * @var DepartmentModel
     */
    private $departmentModel;
    
    /**
     * Конструктор класса
     * 
     * @param array $config Конфигурация приложения
     * @param Database $db Экземпляр подключения к базе данных
     */
    public function __construct($config, $db)
    {
        parent::__construct($config, $db);
        
        // Инициализируем модели
        $this->attendanceModel = new AttendanceModel($db);
        $this->employeeModel = new EmployeeModel($db);
        $this->departmentModel = new DepartmentModel($db);
    }
    
    /**
     * Действие для отображения журнала посещений
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем фильтры из GET-параметров
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];
        
        // Получаем журнал посещений с применением фильтров
        $logs = $this->attendanceModel->getAttendanceLogs($filters);
        
        // Получаем список сотрудников и отделов для фильтров
        $employees = $this->employeeModel->getAll('name', 'ASC');
        $departments = $this->departmentModel->getAll('name', 'ASC');
        
        // Отображаем журнал посещений
        $this->render('attendance/index', [
            'logs' => $logs,
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $filters
        ]);
    }
    
    /**
     * Действие для сканирования QR-кода
     * 
     * @param array $params Параметры запроса
     */
    public function scanAction($params)
    {
        // Отображаем страницу сканирования QR-кода
        $this->render('attendance/scan');
    }
    
    /**
     * Действие для обработки отсканированного QR-кода
     * 
     * @param array $params Параметры запроса
     */
    public function processQrAction($params)
    {
        // Если метод запроса не POST, перенаправляем на страницу сканирования
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance/scan');
            return;
        }
        
        // Получаем QR-код из POST-данных
        $qrCode = $_POST['qr_code'] ?? '';
        
        // Если QR-код не указан, перенаправляем на страницу сканирования с ошибкой
        if (empty($qrCode)) {
            $_SESSION['error'] = 'QR-код не указан';
            $this->redirect('attendance/scan');
            return;
        }
        
        // Получаем сотрудника по QR-коду
        $employee = $this->employeeModel->getByQrCode($qrCode);
        
        // Если сотрудник не найден, перенаправляем на страницу сканирования с ошибкой
        if (!$employee) {
            $_SESSION['error'] = 'Сотрудник с указанным QR-кодом не найден';
            $this->redirect('attendance/scan');
            return;
        }
        
        // Получаем последний проход сотрудника
        $lastAttendance = $this->attendanceModel->getLastAttendance($employee['id']);
        
        // Определяем тип прохода (вход или выход)
        $type = 'in';
        if ($lastAttendance && $lastAttendance['type'] === 'in' && date('Y-m-d') === date('Y-m-d', strtotime($lastAttendance['time']))) {
            $type = 'out';
        }
        
        // Регистрируем проход
        $this->attendanceModel->logAttendance($employee['id'], $type);
        
        // Получаем информацию об отделе
        $department = $this->departmentModel->getById($employee['department_id']);
        
        // Отображаем страницу успешного сканирования
        $this->render('attendance/success', [
            'employee' => $employee,
            'department' => $department,
            'type' => $type,
            'time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Действие для отображения статистики посещаемости
     * 
     * @param array $params Параметры запроса
     */
    public function statisticsAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем период из GET-параметров
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Получаем статистику посещаемости по отделам
        $departmentStats = $this->attendanceModel->getDepartmentStatistics($dateFrom, $dateTo);
        
        // Отображаем статистику посещаемости
        $this->render('attendance/statistics', [
            'departmentStats' => $departmentStats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }
}
