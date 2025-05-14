<?php
/**
 * Контроллер для работы с ИИ-модулем
 */
class AiController extends Controller
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
     * Действие для отображения аналитики ИИ
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем период из GET-параметров
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Получаем ID отдела из GET-параметров
        $departmentId = $_GET['department_id'] ?? null;
        
        // Получаем список отделов для фильтра
        $departments = $this->departmentModel->getAll('name', 'ASC');
        
        // Получаем аналитику от ИИ-модуля
        $analysis = $this->getAiAnalysis($dateFrom, $dateTo, $departmentId);
        
        // Отображаем страницу аналитики
        $this->render('ai/index', [
            'analysis' => $analysis,
            'departments' => $departments,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'departmentId' => $departmentId
        ]);
    }
    
    /**
     * Получает аналитику от ИИ-модуля
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     * @param int|null $departmentId ID отдела
     * @return array Результаты анализа
     */
    private function getAiAnalysis($dateFrom, $dateTo, $departmentId = null)
    {
        // Получаем данные о посещаемости
        $attendanceData = $this->prepareAttendanceData($dateFrom, $dateTo, $departmentId);
        
        // Анализируем данные
        $analysis = $this->analyzeAttendanceData($attendanceData);
        
        // Получаем рекомендации от ИИ
        $recommendations = $this->getAiRecommendations($analysis);
        
        return [
            'attendance_data' => $attendanceData,
            'analysis' => $analysis,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Подготавливает данные о посещаемости для анализа
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     * @param int|null $departmentId ID отдела
     * @return array Данные о посещаемости
     */
    private function prepareAttendanceData($dateFrom, $dateTo, $departmentId = null)
    {
        // Получаем журнал посещений с применением фильтров
        $logs = $this->attendanceModel->getAttendanceLogs([
            'department_id' => $departmentId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        // Группируем данные по сотрудникам
        $employeeData = [];
        foreach ($logs as $log) {
            $employeeId = $log['employee_id'];
            
            if (!isset($employeeData[$employeeId])) {
                $employeeData[$employeeId] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $log['employee_name'],
                    'department_name' => $log['department_name'],
                    'logs' => []
                ];
            }
            
            $employeeData[$employeeId]['logs'][] = [
                'time' => $log['time'],
                'type' => $log['type']
            ];
        }
        
        return array_values($employeeData);
    }
    
    /**
     * Анализирует данные о посещаемости
     * 
     * @param array $attendanceData Данные о посещаемости
     * @return array Результаты анализа
     */
    private function analyzeAttendanceData($attendanceData)
    {
        $analysis = [
            'late_employees' => [],
            'disciplined_employees' => [],
            'risk_employees' => []
        ];
        
        foreach ($attendanceData as $employee) {
            $lateCount = 0;
            $earlyLeaveCount = 0;
            $totalDays = 0;
            $lastDate = null;
            
            // Сортируем логи по времени
            usort($employee['logs'], function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
            
            foreach ($employee['logs'] as $log) {
                $time = strtotime($log['time']);
                $date = date('Y-m-d', $time);
                $hour = (int)date('H', $time);
                $minute = (int)date('i', $time);
                
                // Если это новый день, увеличиваем счетчик дней
                if ($date !== $lastDate) {
                    $totalDays++;
                    $lastDate = $date;
                }
                
                // Проверяем, опоздал ли сотрудник
                if ($log['type'] === 'in' && ($hour > 9 || ($hour === 9 && $minute > 0))) {
                    $lateCount++;
                }
                
                // Проверяем, ушел ли сотрудник раньше времени
                if ($log['type'] === 'out' && $hour < 18) {
                    $earlyLeaveCount++;
                }
            }
            
            // Вычисляем процент опозданий и ранних уходов
            $latePercent = $totalDays > 0 ? ($lateCount / $totalDays) * 100 : 0;
            $earlyLeavePercent = $totalDays > 0 ? ($earlyLeaveCount / $totalDays) * 100 : 0;
            
            // Определяем категорию сотрудника
            if ($latePercent >= 30 || $earlyLeavePercent >= 30) {
                $analysis['risk_employees'][] = [
                    'employee_name' => $employee['employee_name'],
                    'department_name' => $employee['department_name'],
                    'late_percent' => $latePercent,
                    'early_leave_percent' => $earlyLeavePercent
                ];
            } elseif ($latePercent >= 10 || $earlyLeavePercent >= 10) {
                $analysis['late_employees'][] = [
                    'employee_name' => $employee['employee_name'],
                    'department_name' => $employee['department_name'],
                    'late_percent' => $latePercent,
                    'early_leave_percent' => $earlyLeavePercent
                ];
            } else {
                $analysis['disciplined_employees'][] = [
                    'employee_name' => $employee['employee_name'],
                    'department_name' => $employee['department_name'],
                    'late_percent' => $latePercent,
                    'early_leave_percent' => $earlyLeavePercent
                ];
            }
        }
        
        return $analysis;
    }
    
    /**
     * Получает рекомендации от ИИ на основе анализа
     * 
     * @param array $analysis Результаты анализа
     * @return array Рекомендации
     */
    private function getAiRecommendations($analysis)
    {
        // Здесь должен быть вызов ИИ-модуля через API
        // В данном примере просто возвращаем заглушку
        
        $recommendations = [
            'general' => 'На основе анализа посещаемости рекомендуется обратить внимание на сотрудников с высоким процентом опозданий и ранних уходов.',
            'specific' => []
        ];
        
        // Добавляем рекомендации для сотрудников из группы риска
        foreach ($analysis['risk_employees'] as $employee) {
            $recommendations['specific'][] = [
                'employee_name' => $employee['employee_name'],
                'recommendation' => 'Провести беседу с сотрудником о важности соблюдения рабочего графика.'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Действие для отправки запроса к ИИ-модулю через Python-сервис
     * 
     * @param array $params Параметры запроса
     */
    public function analyzeAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на страницу аналитики
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ai');
            return;
        }
        
        // Получаем данные из формы
        $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $departmentId = $_POST['department_id'] ?? null;
        
        // Получаем данные о посещаемости
        $attendanceData = $this->prepareAttendanceData($dateFrom, $dateTo, $departmentId);
        
        // Отправляем запрос к Python-сервису
        $analysis = $this->sendRequestToPythonService($attendanceData);
        
        // Сохраняем результаты анализа в сессии
        $_SESSION['ai_analysis'] = $analysis;
        
        // Перенаправляем на страницу результатов
        $this->redirect('ai/results');
    }
    
    /**
     * Отправляет запрос к Python-сервису для анализа данных
     * 
     * @param array $attendanceData Данные о посещаемости
     * @return array Результаты анализа
     */
    private function sendRequestToPythonService($attendanceData)
    {
        // URL Python-сервиса
        $url = $this->config['ai_module']['python_service_url'] . '/analyze_attendance';
        
        // Подготавливаем данные для запроса
        $data = json_encode($attendanceData);
        
        // Настраиваем контекст запроса
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $data
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Отправляем запрос
        $result = @file_get_contents($url, false, $context);
        
        // Если запрос выполнен успешно, декодируем результат
        if ($result !== false) {
            return json_decode($result, true);
        }
        
        // В случае ошибки возвращаем заглушку
        return [
            'error' => 'Не удалось получить анализ от ИИ-модуля'
        ];
    }
    
    /**
     * Действие для отображения результатов анализа ИИ
     * 
     * @param array $params Параметры запроса
     */
    public function resultsAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем результаты анализа из сессии
        $analysis = $_SESSION['ai_analysis'] ?? null;
        
        // Если результаты не найдены, перенаправляем на страницу аналитики
        if (!$analysis) {
            $this->redirect('ai');
            return;
        }
        
        // Отображаем страницу результатов
        $this->render('ai/results', [
            'analysis' => $analysis
        ]);
    }
}
