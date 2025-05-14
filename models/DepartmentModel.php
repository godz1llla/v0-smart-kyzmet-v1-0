<?php
/**
 * Модель для работы с отделами
 */
class DepartmentModel extends Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table = 'departments';
    
    /**
     * Получает отделы с количеством сотрудников
     * 
     * @return array Массив отделов с количеством сотрудников
     */
    public function getAllWithEmployeeCount()
    {
        $sql = "SELECT d.*, COUNT(e.id) as employee_count 
                FROM {$this->table} d
                LEFT JOIN employees e ON d.id = e.department_id
                GROUP BY d.id
                ORDER BY d.name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Проверяет, можно ли удалить отдел
     * 
     * Отдел нельзя удалить, если в нем есть сотрудники
     * 
     * @param int $departmentId ID отдела
     * @return bool Можно ли удалить отдел
     */
    public function canDelete($departmentId)
    {
        $sql = "SELECT COUNT(*) FROM employees WHERE department_id = :department_id";
        $count = $this->db->fetchColumn($sql, ['department_id' => $departmentId]);
        
        return $count == 0;
    }
}
