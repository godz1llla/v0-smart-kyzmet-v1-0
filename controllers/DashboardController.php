<?php
/**
 * Контроллер для главной страницы (дашборда)
 */
class DashboardController extends Controller
{
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
     * Модель посещений
     * @var AttendanceModel
     */
    private $attendanceModel;
    
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
        $this->employeeModel = new EmployeeModel($db);
        $this->departmentModel = new DepartmentModel($db);
        $this->attendanceModel = new AttendanceModel($db);
    }
    
    /**
     * Действие для отображения главной страницы
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем статистику
        $totalEmployees = $this->employeeModel->count();
        $totalDepartments = $this->departmentModel->count();
        
        // Получаем статистику посещений за сегодня
        $today = date('Y-m-d');
        $attendanceToday = $this->attendanceModel->getAttendanceLogs([
            'date_from' => $today,
            'date_to' => $today
        ]);
        
        // Получаем последние проходы
        $recentAttendance = $this->attendanceModel->getAttendanceLogs(
            [],
            'a.time',
            'DESC'
        );
        $recentAttendance = array_slice($recentAttendance, 0, 10);
        
        // Отображаем главную страницу
        $this->render('dashboard/index', [
            'totalEmployees' => $totalEmployees,
            'totalDepartments' => $totalDepartments,
            'attendanceToday' => $attendanceToday,
            'recentAttendance' => $recentAttendance
        ]);
    }
}
