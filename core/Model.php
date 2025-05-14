<?php
/**
 * Базовый класс модели для проекта SmartKyzmet
 * 
 * Предоставляет общую функциональность для всех моделей
 */
class Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table;
    
    /**
     * Первичный ключ таблицы
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Экземпляр подключения к базе данных
     * @var Database
     */
    protected $db;
    
    /**
     * Конструктор класса
     * 
     * @param Database $db Экземпляр подключения к базе данных
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Получает все записи из таблицы
     * 
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив записей
     */
    public function getAll($orderBy = null, $order = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy $order";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Получает запись по первичному ключу
     * 
     * @param int $id Значение первичного ключа
     * @return array|false Запись или false, если запись не найдена
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * Получает записи по условию
     * 
     * @param string $where Условие WHERE
     * @param array $params Параметры условия
     * @param string $orderBy Поле для сортировки
     * @param string $order Порядок сортировки (ASC или DESC)
     * @return array Массив записей
     */
    public function getWhere($where, $params = [], $orderBy = null, $order = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} WHERE $where";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy $order";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Получает одну запись по условию
     * 
     * @param string $where Условие WHERE
     * @param array $params Параметры условия
     * @return array|false Запись или false, если запись не найдена
     */
    public function getOneWhere($where, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE $where";
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Создает новую запись
     * 
     * @param array $data Данные для создания записи
     * @return int ID созданной записи
     */
    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Обновляет запись по первичному ключу
     * 
     * @param int $id Значение первичного ключа
     * @param array $data Данные для обновления
     * @return int Количество обновленных записей
     */
    public function update($id, $data)
    {
        return $this->db->update(
            $this->table,
            $data,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Удаляет запись по первичному ключу
     * 
     * @param int $id Значение первичного ключа
     * @return int Количество удаленных записей
     */
    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Проверяет существование записи по условию
     * 
     * @param string $where Условие WHERE
     * @param array $params Параметры условия
     * @return bool Существует ли запись
     */
    public function exists($where, $params = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
        return $this->db->fetchColumn($sql, $params) > 0;
    }
    
    /**
     * Получает количество записей по условию
     * 
     * @param string $where Условие WHERE
     * @param array $params Параметры условия
     * @return int Количество записей
     */
    public function count($where = '1', $params = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
        return $this->db->fetchColumn($sql, $params);
    }
}
