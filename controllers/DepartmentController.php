<?php
/**
 * Контроллер для работы с отделами
 */
class DepartmentController extends Controller
{
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
        
        // Инициализируем модель отделов
        $this->departmentModel = new DepartmentModel($db);
    }
    
    /**
     * Действие для отображения списка отделов
     * 
     * @param array $params Параметры запроса
     */
    public function indexAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем список отделов с количеством сотрудников
        $departments = $this->departmentModel->getAllWithEmployeeCount();
        
        // Отображаем список отделов
        $this->render('department/index', [
            'departments' => $departments
        ]);
    }
    
    /**
     * Действие для отображения формы создания отдела
     * 
     * @param array $params Параметры запроса
     */
    public function createAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Отображаем форму создания отдела
        $this->render('department/create');
    }
    
    /**
     * Действие для обработки формы создания отдела
     * 
     * @param array $params Параметры запроса
     */
    public function storeAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Если метод запроса не POST, перенаправляем на форму создания
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('department/create');
            return;
        }
        
        // Получаем данные из формы
        $name = $_POST['name'] ?? '';
        
        // Проверяем обязательные поля
        if (empty($name)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $this->render('department/create', [
                'error' => 'Заполните все обязательные поля',
                'department' => $_POST
            ]);
            return;
        }
        
        // Создаем отдел
        $departmentId = $this->departmentModel->create([
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Перенаправляем на список отделов
        $this->redirect('department');
    }
    
    /**
     * Действие для отображения формы редактирования отдела
     * 
     * @param array $params Параметры запроса
     */
    public function editAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID отдела из параметров
        $departmentId = $params[0] ?? 0;
        
        // Получаем информацию об отделе
        $department = $this->departmentModel->getById($departmentId);
        
        // Если отдел не найден, перенаправляем на список отделов
        if (!$department) {
            $this->redirect('department');
            return;
        }
        
        // Отображаем форму редактирования отдела
        $this->render('department/edit', [
            'department' => $department
        ]);
    }
    
    /**
     * Действие для обработки формы редактирования отдела
     * 
     * @param array $params Параметры запроса
     */
    public function updateAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID отдела из параметров
        $departmentId = $params[0] ?? 0;
        
        // Если метод запроса не POST, перенаправляем на форму редактирования
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('department/edit/' . $departmentId);
            return;
        }
        
        // Получаем информацию об отделе
        $department = $this->departmentModel->getById($departmentId);
        
        // Если отдел не найден, перенаправляем на список отделов
        if (!$department) {
            $this->redirect('department');
            return;
        }
        
        // Получаем данные из формы
        $name = $_POST['name'] ?? '';
        
        // Проверяем обязательные поля
        if (empty($name)) {
            // Если обязательные поля не заполнены, отображаем форму с ошибкой
            $this->render('department/edit', [
                'department' => array_merge($department, $_POST),
                'error' => 'Заполните все обязательные поля'
            ]);
            return;
        }
        
        // Обновляем отдел
        $this->departmentModel->update($departmentId, [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Перенаправляем на список отделов
        $this->redirect('department');
    }
    
    /**
     * Действие для удаления отдела
     * 
     * @param array $params Параметры запроса
     */
    public function deleteAction($params)
    {
        // Требуем авторизации пользователя
        $this->requireAuth();
        
        // Получаем ID отдела из параметров
        $departmentId = $params[0] ?? 0;
        
        // Проверяем, можно ли удалить отдел
        if (!$this->departmentModel->canDelete($departmentId)) {
            // Если отдел нельзя удалить, перенаправляем на список отделов с ошибкой
            $_SESSION['error'] = 'Невозможно удалить отдел, в котором есть сотрудники';
            $this->redirect('department');
            return;
        }
        
        // Удаляем отдел
        $this->departmentModel->delete($departmentId);
        
        // Перенаправляем на список отделов
        $this->redirect('department');
    }
}
