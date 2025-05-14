<?php
/**
 * Модель для работы с пользователями
 */
class UserModel extends Model
{
    /**
     * Имя таблицы в базе данных
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Получает пользователя по имени пользователя
     * 
     * @param string $username Имя пользователя
     * @return array|false Пользователь или false, если пользователь не найден
     */
    public function getByUsername($username)
    {
        return $this->getOneWhere('username = :username', ['username' => $username]);
    }
    
    /**
     * Проверяет учетные данные пользователя
     * 
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @return array|false Пользователь или false, если учетные данные неверны
     */
    public function authenticate($username, $password)
    {
        // Получаем пользователя по имени пользователя
        $user = $this->getByUsername($username);
        
        // Если пользователь не найден, возвращаем false
        if (!$user) {
            return false;
        }
        
        // Проверяем пароль
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Возвращаем пользователя
        return $user;
    }
    
    /**
     * Создает нового пользователя
     * 
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @param string $role Роль пользователя
     * @return int ID созданного пользователя
     */
    public function createUser($username, $password, $role)
    {
        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Создаем пользователя
        return $this->create([
            'username' => $username,
            'password' => $hashedPassword,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Обновляет пароль пользователя
     * 
     * @param int $userId ID пользователя
     * @param string $password Новый пароль
     * @return int Количество обновленных записей
     */
    public function updatePassword($userId, $password)
    {
        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Обновляем пароль
        return $this->update($userId, [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
