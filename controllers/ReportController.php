<?php
/**
 * Контроллер для работы с отчетами
 */
class ReportController extends Controller
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
     * Действие для отображения формы генерации отчета
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем список отделов для фильтра
        $departments = $this->departmentModel->getAll('name', 'ASC');
        
        // Отображаем форму генерации отчета
        $this->render('report/index', [
            'departments' => $departments
        ]);
    }
    
    /**
     * Действие для генерации отчета
     * 
     * @param array $params Параметры запроса
     */
    public function generateAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на форму генерации отчета
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('report');
            return;
        }
        
        // Получаем параметры отчета из формы
        $reportType = $_POST['report_type'] ?? '';
        $departmentId = $_POST['department_id'] ?? null;
        $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        
        // Проверяем тип отчета
        if (!in_array($reportType, ['attendance', 'employee', 'department'])) {
            $this->redirect('report');
            return;
        }
        
        // Генерируем отчет в зависимости от типа
        switch ($reportType) {
            case 'attendance':
                $this->generateAttendanceReport($dateFrom, $dateTo, $departmentId);
                break;
            case 'employee':
                $this->generateEmployeeReport($dateFrom, $dateTo, $departmentId);
                break;
            case 'department':
                $this->generateDepartmentReport($dateFrom, $dateTo);
                break;
        }
    }
    
    /**
     * Генерирует отчет о посещаемости
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     * @param int|null $departmentId ID отдела
     */
    private function generateAttendanceReport($dateFrom, $dateTo, $departmentId = null)
    {
        // Получаем журнал посещений с применением фильтров
        $logs = $this->attendanceModel->getAttendanceLogs([
            'department_id' => $departmentId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        // Получаем название отдела, если указан ID отдела
        $departmentName = 'Все отделы';
        if ($departmentId) {
            $department = $this->departmentModel->getById($departmentId);
            if ($department) {
                $departmentName = $department['name'];
            }
        }
        
        // Генерируем Excel-файл
        $this->generateExcel(
            'Отчет о посещаемости',
            [
                'Отдел: ' . $departmentName,
                'Период: с ' . $dateFrom . ' по ' . $dateTo
            ],
            [
                'Сотрудник',
                'Отдел',
                'Дата и время',
                'Тип'
            ],
            array_map(function($log) {
                return [
                    $log['employee_name'],
                    $log['department_name'],
                    $log['time'],
                    $log['type'] === 'in' ? 'Вход' : 'Выход'
                ];
            }, $logs)
        );
    }
    
    /**
     * Генерирует отчет по сотрудникам
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     * @param int|null $departmentId ID отдела
     */
    private function generateEmployeeReport($dateFrom, $dateTo, $departmentId = null)
    {
        // Получаем список сотрудников
        $employees = $departmentId
            ? $this->employeeModel->getByDepartment($departmentId)
            : $this->employeeModel->getAll();
        
        // Получаем название отдела, если указан ID отдела
        $departmentName = 'Все отделы';
        if ($departmentId) {
            $department = $this->departmentModel->getById($departmentId);
            if ($department) {
                $departmentName = $department['name'];
            }
        }
        
        // Подготавливаем данные для отчета
        $data = [];
        foreach ($employees as $employee) {
            // Получаем журнал посещений сотрудника
            $logs = $this->attendanceModel->getAttendanceLogs([
                'employee_id' => $employee['id'],
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
            // Считаем количество входов и выходов
            $inCount = 0;
            $outCount = 0;
            $lateCount = 0;
            
            foreach ($logs as $log) {
                if ($log['type'] === 'in') {
                    $inCount++;
                    
                    // Проверяем, опоздал ли сотрудник
                    $time = strtotime($log['time']);
                    $hour = (int)date('H', $time);
                    $minute = (int)date('i', $time);
                    
                    if ($hour > 9 || ($hour === 9 && $minute > 0)) {
                        $lateCount++;
                    }
                } else {
                    $outCount++;
                }
            }
            
            // Добавляем данные сотрудника в отчет
            $data[] = [
                $employee['name'],
                $employee['department_id'] ? $this->getDepartmentName($employee['department_id']) : '',
                $inCount,
                $outCount,
                $lateCount
            ];
        }
        
        // Генерируем Excel-файл
        $this->generateExcel(
            'Отчет по сотрудникам',
            [
                'Отдел: ' . $departmentName,
                'Период: с ' . $dateFrom . ' по ' . $dateTo
            ],
            [
                'Сотрудник',
                'Отдел',
                'Количество входов',
                'Количество выходов',
                'Количество опозданий'
            ],
            $data
        );
    }
    
    /**
     * Генерирует отчет по отделам
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     */
    private function generateDepartmentReport($dateFrom, $dateTo)
    {
        // Получаем статистику посещаемости по отделам
        $departmentStats = $this->attendanceModel->getDepartmentStatistics($dateFrom, $dateTo);
        
        // Подготавливаем данные для отчета
        $data = [];
        foreach ($departmentStats as $stat) {
            $data[] = [
                $stat['name'],
                $stat['employee_count'],
                $stat['attendance_count'],
                $stat['late_count']
            ];
        }
        
        // Генерируем Excel-файл
        $this->generateExcel(
            'Отчет по отделам',
            [
                'Период: с ' . $dateFrom . ' по ' . $dateTo
            ],
            [
                'Отдел',
                'Количество сотрудников',
                'Количество посещений',
                'Количество опозданий'
            ],
            $data
        );
    }
    
    /**
     * Генерирует Excel-файл и отправляет его клиенту
     * 
     * @param string $title Заголовок отчета
     * @param array $subtitle Подзаголовки отчета
     * @param array $headers Заголовки столбцов
     * @param array $data Данные отчета
     */
    private function generateExcel($title, $subtitle, $headers, $data)
    {
        // Подключаем библиотеку для работы с Excel
        require_once ROOT_DIR . '/lib/phpspreadsheet/vendor/autoload.php';
        
        // Создаем новый документ Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Устанавливаем заголовок
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) - 1) . '1');
        
        // Устанавливаем подзаголовки
        $row = 2;
        foreach ($subtitle as $text) {
            $sheet->setCellValue('A' . $row, $text);
            $sheet->mergeCells('A' . $row . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) - 1) . $row);
            $row++;
        }
        
        // Устанавливаем заголовки столбцов
        $col = 0;
        foreach ($headers as $header) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, $header);
            $col++;
        }
        
        // Устанавливаем данные
        $row++;
        foreach ($data as $rowData) {
            $col = 0;
            foreach ($rowData as $cellData) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, $cellData);
                $col++;
            }
            $row++;
        }
        
        // Устанавливаем стили
        $styleArray = [
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];
        
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) - 1) . '1')->applyFromArray($styleArray);
        $sheet->getStyle('A' . ($row - count($data) - 1) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) - 1) . ($row - count($data) - 1))->applyFromArray($styleArray);
        
        // Автоматически устанавливаем ширину столбцов
        foreach (range(0, count($headers) - 1) as $col) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
        
        // Создаем объект для записи Excel-файла
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Устанавливаем заголовки для скачивания файла
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="report.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Отправляем файл клиенту
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Получает название отдела по ID
     * 
     * @param int $departmentId ID отдела
     * @return string Название отдела
     */
    private function getDepartmentName($departmentId)
    {
        $department = $this->departmentModel->getById($departmentId);
        return $department ? $department['name'] : '';
    }
}
