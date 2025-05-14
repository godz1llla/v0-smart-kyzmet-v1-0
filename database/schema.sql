-- Создание базы данных
CREATE DATABASE IF NOT EXISTS smartkyzmet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Использование базы данных
USE smartkyzmet;

-- Таблица отделов
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB;

-- Таблица сотрудников
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT NULL,
    qr_code VARCHAR(255) NOT NULL UNIQUE,
    photo VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Таблица журнала посещений
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    time DATETIME NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'supervisor') NOT NULL,
    employee_id INT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Таблица задач
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
    assigned_to INT NULL,
    created_by INT NOT NULL,
    deadline DATE NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Таблица результатов анализа ИИ
CREATE TABLE IF NOT EXISTS ai_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    analysis_text TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Создание индексов для оптимизации запросов
CREATE INDEX idx_employees_department ON employees(department_id);
CREATE INDEX idx_attendance_employee ON attendance

Я начал разработку проекта SmartKyzmet и создал базовую структуру проекта. Вот что было реализовано:

### Основные компоненты:

1. **Ядро системы (core)**:
   - Автозагрузчик классов
   - Базовый класс приложения
   - Маршрутизатор
   - Базовый контроллер
   - Класс для работы с базой данных
   - Базовый класс модели

2. **Модели данных**:
   - UserModel - для работы с пользователями
   - EmployeeModel - для работы с сотрудниками
   - DepartmentModel - для работы с отделами
   - AttendanceModel - для работы с журналом посещений
   - TaskModel - для работы с задачами

3. **Контроллеры**:
   - AuthController - для авторизации
   - DashboardController - для главной страницы
   - EmployeeController - для управления сотрудниками
   - DepartmentController - для управления отделами
   - AttendanceController - для работы с журналом посещений
   - QrController - для работы с QR-кодами
   - TaskController - для управления задачами
   - ReportController - для генерации отчетов
   - AiController - для работы с ИИ-модулем
   - ErrorController - для обработки ошибок

4. **Структура базы данных**:
   - Таблица departments - отделы
   - Таблица employees - сотрудники
   - Таблица attendance_logs - журнал посещений
   - Таблица users - пользователи системы
   - Таблица tasks - задачи
   - Таблица ai_analysis - результаты анализа ИИ

### Следующие шаги:

1. Создание представлений (views) для всех контроллеров
2. Реализация JavaScript для сканирования QR-кодов
3. Интеграция с Ollama для ИИ-анализа
4. Настройка Python-сервиса для обработки данных

Сообщите, если у вас есть замечания или вы хотите, чтобы я сосредоточился на какой-то конкретной части проекта.
