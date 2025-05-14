<?php
/**
 * Модель для работы с задачами
 */
class TaskModel extends Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table = 'tasks';
    
    /**
     * Получает задачи с информацией о сотрудниках
     * 
     * @param array $filters Фильтры (status, assigned_to, created_by)
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив задач
     */
    public function getTasks($filters = [], $orderBy = 't.deadline', $order = 'ASC')
    {
        $sql = "SELECT t.*, 
                e1.name as assigned_to_name, 
                e2.name as created_by_name
                FROM {$this->table} t
                LEFT JOIN employees e1 ON t.assigned_to = e1.id
                LEFT JOIN users u ON t.created_by = u.id
                LEFT JOIN employees e2 ON u.employee_id = e2.id
                WHERE 1";
        
        $params = [];
        
        // Применяем фильтры
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND t.created_by = :created_by";
            $params['created_by'] = $filters['created_by'];
        }
        
        // Добавляем сортировку
        $sql .= " ORDER BY $orderBy $order";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Обновляет статус задачи
     * 
     * @param int $taskId ID задачи
     * @param string $status Новый статус
     * @return int Количество обновленных записей
     */
    public function updateStatus($taskId, $status)
    {
        return $this->update($taskId, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Получает задачи, сгруппированные по статусу
     * 
     * @param array $filters Фильтры (assigned_to, created_by)
     * @return array Задачи, сгруппированные по статусу
     */
    public function getTasksByStatus($filters = [])
    {
        // Получаем все задачи
        $tasks = $this->getTasks($filters);
        
        // Группируем задачи по статусу
        $tasksByStatus = [
            'todo' => [],
            'in_progress' => [],
            'done' => []
        ];
        
        foreach ($tasks as $task) {
            $tasksByStatus[$task['status']][] = $task;
        }
        
        return $tasksByStatus;
    }
}
