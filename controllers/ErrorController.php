<?php
/**
 * Контроллер для обработки ошибок
 */
class ErrorController extends Controller
{
    /**
     * Действие для отображения страницы "Страница не найдена"
     * 
     * @param array $params Параметры запроса
     */
    public function notFoundAction($params)
    {
        // Устанавливаем HTTP-статус 404
        header('HTTP/1.1 404 Not Found');
        
        // Отображаем страницу ошибки
        $this->render('error/not-found', [
            'message' => $params['message'] ?? 'Страница не найдена'
        ]);
    }
    
    /**
     * Действие для отображения страницы "Доступ запрещен"
     * 
     * @param array $params Параметры запроса
     */
    public function forbiddenAction($params)
    {
        // Устанавливаем HTTP-статус 403
        header('HTTP/1.1 403 Forbidden');
        
        // Отображаем страницу ошибки
        $this->render('error/forbidden', [
            'message' => $params['message'] ?? 'Доступ запрещен'
        ]);
    }
    
    /**
     * Действие для отображения страницы "Внутренняя ошибка сервера"
     * 
     * @param array $params Параметры запроса
     */
    public function serverErrorAction($params)
    {
        // Устанавливаем HTTP-статус 500
        header('HTTP/1.1 500 Internal Server Error');
        
        // Отображаем страницу ошибки
        $this->render('error/server-error', [
            'message' => $params['message'] ?? 'Внутренняя ошибка сервера'
        ]);
    }
}
