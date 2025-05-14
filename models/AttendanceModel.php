<?php
/**
 * Модель для работы с журналом посещений
 */
class AttendanceModel extends Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table = 'attendance_logs';
    
    /**
     * Регистрирует проход сотрудника
     * 
     * @param int $employeeId ID сотрудника
     * @param string $type Тип прохода (in/out)
     * @return int ID созданной записи
     */
    public function logAttendance($employeeId, $type = 'in')
    {
        return $this->create([
            'employee_id' => $employeeId,
            'time' => date('Y-m-d H:i:s'),
            'type' => $type
        ]);
    }
    
    /**
     * Получает журнал посещений с информацией о сотрудниках
     * 
     * @param array $filters Фильтры (employee_id, department_id, date_from, date_to)
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив записей журнала
     */
    public function getAttendanceLogs($filters = [], $orderBy = 'a.time', $order = 'DESC')
    {
        $sql = "SELECT a.*, e.name as employee_name, e.qr_code, d.name as department_name 
                FROM {$this->table} a
                JOIN employees e ON a.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE 1";
        
        $params = [];
        
        // Применяем фильтры
        if (!empty($filters['employee_id'])) {
            $sql .= " AND a.employee_id = :employee_id";
            $params['employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :department_id";
            $params['department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.time) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.time) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        // Добавляем сортировку
        $sql .= " ORDER BY $orderBy $order";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Получает последний проход сотрудника
     * 
     * @param int $employeeId ID сотрудника
     * @return array|false Запись о проходе или false, если запись не найдена
     */
    public function getLastAttendance($employeeId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE employee_id = :employee_id 
                ORDER BY time DESC 
                LIMIT 1";
        
        return $this->db->fetch($sql, ['employee_id' => $employeeId]);
    }
    
    /**
     * Получает статистику посещаемости по отделам
     * 
     * @param string $dateFrom Дата начала периода
     * @param string $dateTo Дата окончания периода
     * @return array Статистика посещаемости
     */
    public function getDepartmentStatistics($dateFrom, $dateTo)
    {
        $sql = "SELECT d.id, d.name, 
                COUNT(DISTINCT a.employee_id) as employee_count,
                COUNT(a.id) as attendance_count,
                SUM(CASE WHEN TIME(a.time) > '09:00:00' AND a.type = 'in' THEN 1 ELSE 0 END) as late_count
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id
                LEFT JOIN {$this->table} a ON e.id = a.employee_id AND DATE(a.time) BETWEEN :date_from AND :date_to
                GROUP BY d.id, d.name
                ORDER BY d.name ASC";
        
        return $this->db->fetchAll($sql, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }
}
