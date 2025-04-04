<?php
namespace src\Service;

use PDO;
use PDOException;

class DatabaseService
{
    private PDO $pdo;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $db = $_ENV['DB_DATABASE'] ?? '';
        $user = $_ENV['DB_USERNAME'] ?? '';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function insertRAGChunk(string $text, array $embedding, array $metadata = []): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO rag_chunks (text, embedding, metadata) VALUES (?, ?, ?)");
        $stmt->execute([
            $text,
            json_encode($embedding),
            json_encode($metadata),
        ]);
    }

    public function fetchAllRAGChunks(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM rag_chunks");
        return $stmt->fetchAll();
    }
}
?>