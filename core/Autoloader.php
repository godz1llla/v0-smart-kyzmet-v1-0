<?php
/**
 * Класс автозагрузки для проекта SmartKyzmet
 * 
 * Автоматически загружает классы по их имени, 
 * следуя структуре директорий проекта
 */
class Autoloader
{
    /**
     * Регистрирует автозагрузчик в стеке автозагрузчиков PHP
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Загружает класс по его имени
     * 
     * @param string $className Имя класса для загрузки
     * @return bool Успешность загрузки класса
     */
    public function loadClass($className)
    {
        // Преобразуем имя класса в путь к файлу
        $filePath = $this->getFilePath($className);
        
        // Проверяем существование файла
        if (file_exists($filePath)) {
            require_once $filePath;
            return true;
        }
        
        return false;
    }
    
    /**
     * Преобразует имя класса в путь к файлу
     * 
     * @param string $className Имя класса
     * @return string Путь к файлу класса
     */
    private function getFilePath($className)
    {
        // Заменяем обратные слеши на прямые для соответствия структуре директорий
        $className = str_replace('\\', '/', $className);
        
        // Определяем базовые директории для поиска классов
        $directories = [
            ROOT_DIR . '/core/',
            ROOT_DIR . '/models/',
            ROOT_DIR . '/controllers/',
            ROOT_DIR . '/helpers/',
            ROOT_DIR . '/services/'
        ];
        
        // Ищем класс в каждой директории
        foreach ($directories as $directory) {
            $filePath = $directory . $className . '.php';
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        
        // Если класс не найден в базовых директориях, 
        // пробуем найти его в корне проекта
        return ROOT_DIR . '/' . $className . '.php';
    }
}
