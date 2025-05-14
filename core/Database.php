<?php
/**
 * Класс для работы с базой данных MySQL
 * 
 * Предоставляет методы для выполнения запросов к базе данных
 */
class Database
{
    /**
     * Экземпляр подключения к базе данных
     * @var PDO
     */
    private $pdo;
    
    /**
     * Конструктор класса
     * 
     * @param string $host Хост базы данных
     * @param string $dbname Имя базы данных
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @param string $charset Кодировка
     */
    public function __construct($host, $dbname, $username, $password, $charset = 'utf8mb4')
    {
        try {
            // Формируем DSN для подключения
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            
            // Опции подключения
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Создаем экземпляр PDO
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // В случае ошибки подключения выбрасываем исключение
            throw new Exception('Ошибка подключения к базе данных: ' . $e->getMessage());
        }
    }
    
    /**
     * Выполняет SQL-запрос
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return PDOStatement Результат выполнения запроса
     */
    public function query($sql, $params = [])
    {
        try {
            // Подготавливаем запрос
            $stmt = $this->pdo->prepare($sql);
            
            // Выполняем запрос с параметрами
            $stmt->execute($params);
            
            // Возвращаем результат
            return $stmt;
        } catch (PDOException $e) {
            // В случае ошибки выполнения запроса выбрасываем исключение
            throw new Exception('Ошибка выполнения запроса: ' . $e->getMessage());
        }
    }
    
    /**
     * Получает все строки результата запроса
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return array Массив строк результата
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Получает одну строку результата запроса
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return array|false Строка результата или false, если результат пуст
     */
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Получает значение одного столбца из первой строки результата
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры запроса
     * @return mixed Значение столбца или false, если результат пуст
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }
    
    /**
     * Вставляет данные в таблицу
     * 
     * @param string $table Имя таблицы
     * @param array $data Данные для вставки (ключ => значение)
     * @return int ID вставленной записи
     */
    public function insert($table, $data)
    {
        // Формируем список полей и плейсхолдеров
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);
        
        // Формируем SQL-запрос
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        // Выполняем запрос
        $this->query($sql, $data);
        
        // Возвращаем ID вставленной записи
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Обновляет данные в таблице
     * 
     * @param string $table Имя таблицы
     * @param array $data Данные для обновления (ключ => значение)
     * @param string $where Условие WHERE
     * @param array $whereParams Параметры условия WHERE
     * @return int Количество обновленных строк
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        // Формируем список SET
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "$field = :$field";
        }
        
        // Формируем SQL-запрос
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        
        // Объединяем параметры данных и условия
        $params = array_merge($data, $whereParams);
        
        // Выполняем запрос
        $stmt = $this->query($sql, $params);
        
        // Возвращаем количество обновленных строк
        return $stmt->rowCount();
    }
    
    /**
     * Удаляет данные из таблицы
     * 
     * @param string $table Имя таблицы
     * @param string $where Условие WHERE
     * @param array $params Параметры условия WHERE
     * @return int Количество удаленных строк
     */
    public function delete($table, $where, $params = [])
    {
        // Формируем SQL-запрос
        $sql = "DELETE FROM $table WHERE $where";
        
        // Выполняем запрос
        $stmt = $this->query($sql, $params);
        
        // Возвращаем количество удаленных строк
        return $stmt->rowCount();
    }
    
    /**
     * Начинает транзакцию
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Фиксирует транзакцию
     */
    public function commit()
    {
        $this->pdo->commit();
    }
    
    /**
     * Откатывает транзакцию
     */
    public function rollBack()
    {
        $this->pdo->rollBack();
    }
    
    /**
     * Возвращает экземпляр PDO
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }
}
