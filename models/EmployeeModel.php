<?php
/**
 * Модель для работы с сотрудниками
 */
class EmployeeModel extends Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table = 'employees';
    
    /**
     * Получает сотрудников с информацией об отделе
     * 
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив сотрудников
     */
    public function getAllWithDepartment($orderBy = 'e.name', $order = 'ASC')
    {
        $sql = "SELECT e.*, d.name as department_name 
                FROM {$this->table} e
                LEFT JOIN departments d ON e.department_id = d.id
                ORDER BY $orderBy $order";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Получает сотрудника по QR-коду
     * 
     * @param string $qrCode QR-код сотрудника
     * @return array|false Сотрудник или false, если сотрудник не найден
     */
    public function getByQrCode($qrCode)
    {
        return $this->getOneWhere('qr_code = :qr_code', ['qr_code' => $qrCode]);
    }
    
    /**
     * Генерирует уникальный QR-код для сотрудника
     * 
     * @return string Уникальный QR-код
     */
    public function generateUniqueQrCode()
    {
        do {
            // Генерируем случайный код
            $qrCode = uniqid('EMP', true);
            
            // Проверяем, что код уникален
            $exists = $this->exists('qr_code = :qr_code', ['qr_code' => $qrCode]);
        } while ($exists);
        
        return $qrCode;
    }
    
    /**
     * Создает нового сотрудника
     * 
     * @param array $data Данные сотрудника
     * @return int ID созданного сотрудника
     */
    public function createEmployee($data)
    {
        // Генерируем QR-код, если он не указан
        if (!isset($data['qr_code']) || empty($data['qr_code'])) {
            $data['qr_code'] = $this->generateUniqueQrCode();
        }
        
        // Добавляем дату создания
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Создаем сотрудника
        return $this->create($data);
    }
    
    /**
     * Получает сотрудников по отделу
     * 
     * @param int $departmentId ID отдела
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив сотрудников
     */
    public function getByDepartment($departmentId, $orderBy = 'name', $order = 'ASC')
    {
        return $this->getWhere(
            'department_id = :department_id',
            ['department_id' => $departmentId],
            $orderBy,
            $order
        );
    }
}
