<?php
/**
 * Контроллер для работы с сотрудниками
 */
class EmployeeController extends Controller
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
    }
    
    /**
     * Действие для отображения списка сотрудников
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем список сотрудников с информацией об отделах
        $employees = $this->employeeModel->getAllWithDepartment();
        
        // Отображаем список сотрудников
        $this->render('employee/index', [
            'employees' => $employees
        ]);
    }
    
    /**
     * Действие для отображения формы создания сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function createAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем список отделов для выпадающего списка
        $departments = $this->departmentModel->getAll('name', 'ASC');
        
        // Отображаем форму создания сотрудника
        $this->render('employee/create', [
            'departments' => $departments
        ]);
    }
    
    /**
     * Действие для обработки формы создания сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function storeAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на форму создания
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('employee/create');
            return;
        }
        
        // Получаем данные из формы
        $name = $_POST['name'] ?? '';
        $departmentId = $_POST['department_id'] ?? '';
        
        // Проверяем обязательные поля
        if (empty($name) || empty($departmentId)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $departments = $this->departmentModel->getAll('name', 'ASC');
            $this->render('employee/create', [
                'departments' => $departments,
                'error' => 'Заполните все обязательные поля',
                'employee' => $_POST
            ]);
            return;
        }
        
        // Обрабатываем загрузку фото
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $this->uploadPhoto($_FILES['photo']);
        }
        
        // Создаем сотрудника
        $employeeId = $this->employeeModel->createEmployee([
            'name' => $name,
            'department_id' => $departmentId,
            'photo' => $photo
        ]);
        
        // Перенаправляем на страницу сотрудника
        $this->redirect('employee/view/' . $employeeId);
    }
    
    /**
     * Действие для отображения информации о сотруднике
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
        
        // Получаем информацию об отделе
        $department = $this->departmentModel->getById($employee['department_id']);
        
        // Отображаем информацию о сотруднике
        $this->render('employee/view', [
            'employee' => $employee,
            'department' => $department
        ]);
    }
    
    /**
     * Действие для отображения формы редактирования сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function editAction($params)
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
        
        // Получаем список отделов для выпадающего списка
        $departments = $this->departmentModel->getAll('name', 'ASC');
        
        // Отображаем форму редактирования сотрудника
        $this->render('employee/edit', [
            'employee' => $employee,
            'departments' => $departments
        ]);
    }
    
    /**
     * Действие для обработки формы редактирования сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function updateAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID сотрудника из параметров
        $employeeId = $params[0] ?? 0;
        
        // Если метод запроса не POST, перенаправляем на форму редактирования
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('employee/edit/' . $employeeId);
            return;
        }
        
        // Получаем информацию о сотруднике
        $employee = $this->employeeModel->getById($employeeId);
        
        // Если сотрудник не найден, перенаправляем на список сотрудников
        if (!$employee) {
            $this->redirect('employee');
            return;
        }
        
        // Получаем данные из формы
        $name = $_POST['name'] ?? '';
        $departmentId = $_POST['department_id'] ?? '';
        
        // Проверяем обязательные поля
        if (empty($name) || empty($departmentId)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $departments = $this->departmentModel->getAll('name', 'ASC');
            $this->render('employee/edit', [
                'employee' => array_merge($employee, $_POST),
                'departments' => $departments,
                'error' => 'Заполните все обязательные поля'
            ]);
            return;
        }
        
        // Подготавливаем данные для обновления
        $data = [
            'name' => $name,
            'department_id' => $departmentId
        ];
        
        // Обрабатываем загрузку фото
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $data['photo'] = $this->uploadPhoto($_FILES['photo']);
        }
        
        // Обновляем сотрудника
        $this->employeeModel->update($employeeId, $data);
        
        // Перенаправляем на страницу сотрудника
        $this->redirect('employee/view/' . $employeeId);
    }
    
    /**
     * Действие для удаления сотрудника
     * 
     * @param array $params Параметры запроса
     */
    public function deleteAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID сотрудника из параметров
        $employeeId = $params[0] ?? 0;
        
        // Удаляем сотрудника
        $this->employeeModel->delete($employeeId);
        
        // Перенаправляем на список сотрудников
        $this->redirect('employee');
    }
    
    /**
     * Загружает фото сотрудника
     * 
     * @param array $file Информация о загруженном файле
     * @return string|null Путь к загруженному файлу или null в случае ошибки
     */
    private function uploadPhoto($file)
    {
        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        // Создаем директорию для загрузки, если она не существует
        $uploadDir = ROOT_DIR . '/public/uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Генерируем уникальное имя файла
        $fileName = uniqid('photo_') . '_' . $file['name'];
        $filePath = $uploadDir . $fileName;
        
        // Перемещаем загруженный файл
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return 'uploads/photos/' . $fileName;
        }
        
        return null;
    }
}
