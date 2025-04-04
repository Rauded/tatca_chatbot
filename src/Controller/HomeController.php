<?php
namespace src\Controller;

class HomeController
{
    public function index(array $vars = []): void
    {
        require __DIR__ . '/../../templates/chat.html.php';
    }
}
?>