<?php
/**
 * Контроллер для работы с задачами
 */
class TaskController extends Controller
{
    /**
     * Модель задач
     * @var TaskModel
     */
    private $taskModel;
    
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
        
        // Инициализируем модели
        $this->taskModel = new TaskModel($db);
        $this->employeeModel = new EmployeeModel($db);
    }
    
    /**
     * Действие для отображения списка задач
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем фильтры из GET-параметров
        $filters = [
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'created_by' => $_GET['created_by'] ?? null
        ];
        
        // Получаем задачи, сгруппированные по статусу
        $tasksByStatus = $this->taskModel->getTasksByStatus($filters);
        
        // Получаем список сотрудников для фильтров
        $employees = $this->employeeModel->getAll('name', 'ASC');
        
        // Отображаем список задач
        $this->render('task/index', [
            'tasksByStatus' => $tasksByStatus,
            'employees' => $employees,
            'filters' => $filters
        ]);
    }
    
    /**
     * Действие для отображения формы создания задачи
     * 
     * @param array $params Параметры запроса
     */
    public function createAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем список сотрудников для выпадающего списка
        $employees = $this->employeeModel->getAll('name', 'ASC');
        
        // Отображаем форму создания задачи
        $this->render('task/create', [
            'employees' => $employees
        ]);
    }
    
    /**
     * Действие для обработки формы создания задачи
     * 
     * @param array $params Параметры запроса
     */
    public function storeAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на форму создания
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('task/create');
            return;
        }
        
        // Получаем данные из формы
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $assignedTo = $_POST['assigned_to'] ?? null;
        $deadline = $_POST['deadline'] ?? null;
        
        // Проверяем обязательные поля
        if (empty($title)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $employees = $this->employeeModel->getAll('name', 'ASC');
            $this->render('task/create', [
                'employees' => $employees,
                'error' => 'Заполните все обязательные поля',
                'task' => $_POST
            ]);
            return;
        }
        
        // Создаем задачу
        $taskId = $this->taskModel->create([
            'title' => $title,
            'description' => $description,
            'status' => 'todo',
            'assigned_to' => $assignedTo,
            'created_by' => $_SESSION['user_id'],
            'deadline' => $deadline,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Перенаправляем на список задач
        $this->redirect('task');
    }
    
    /**
     * Действие для обновления статуса задачи
     * 
     * @param array $params Параметры запроса
     */
    public function updateStatusAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на список задач
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('task');
            return;
        }
        
        // Получаем данные из формы
        $taskId = $_POST['task_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        // Проверяем валидность статуса
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($status, $validStatuses)) {
            $this->redirect('task');
            return;
        }
        
        // Обновляем статус задачи
        $this->taskModel->updateStatus($taskId, $status);
        
        // Перенаправляем на список задач
        $this->redirect('task');
    }
    
    /**
     * Действие для отображения формы редактирования задачи
     * 
     * @param array $params Параметры запроса
     */
    public function editAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID задачи из параметров
        $taskId = $params[0] ?? 0;
        
        // Получаем информацию о задаче
        $task = $this->taskModel->getById($taskId);
        
        // Если задача не найдена, перенаправляем на список задач
        if (!$task) {
            $this->redirect('task');
            return;
        }
        
        // Получаем список сотрудников для выпадающего списка
        $employees = $this->employeeModel->getAll('name', 'ASC');
        
        // Отображаем форму редактирования задачи
        $this->render('task/edit', [
            'task' => $task,
            'employees' => $employees
        ]);
    }
    
    /**
     * Действие для обработки формы редактирования задачи
     * 
     * @param array $params Параметры запроса
     */
    public function updateAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID задачи из параметров
        $taskId = $params[0] ?? 0;
        
        // Если метод запроса не POST, перенаправляем на форму редактирования
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('task/edit/' . $taskId);
            return;
        }
        
        // Получаем информацию о задаче
        $task = $this->taskModel->getById($taskId);
        
        // Если задача не найдена, перенаправляем на список задач
        if (!$task) {
            $this->redirect('task');
            return;
        }
        
        // Получаем данные из формы
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $assignedTo = $_POST['assigned_to'] ?? null;
        $deadline = $_POST['deadline'] ?? null;
        
        // Проверяем обязательные поля
        if (empty($title)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $employees = $this->employeeModel->getAll('name', 'ASC');
            $this->render('task/edit', [
                'task' => array_merge($task, $_POST),
                'employees' => $employees,
                'error' => 'Заполните все обязательные поля'
            ]);
            return;
        }
        
        // Обновляем задачу
        $this->taskModel->update($taskId, [
            'title' => $title,
            'description' => $description,
            'assigned_to' => $assignedTo,
            'deadline' => $deadline,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Перенаправляем на список задач
        $this->redirect('task');
    }
    
    /**
     * Действие для удаления задачи
     * 
     * @param array $params Параметры запроса
     */
    public function deleteAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID задачи из параметров
        $taskId = $params[0] ?? 0;
        
        // Удаляем задачу
        $this->taskModel->delete($taskId);
        
        // Перенаправляем на список задач
        $this->redirect('task');
    }
}
