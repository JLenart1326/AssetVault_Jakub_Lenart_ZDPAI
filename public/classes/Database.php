<?php

class Database
{
    // Tutaj trzymamy jedyną instancję tej klasy (Wzorzec Singleton)
    private static $instance = null;
    private $pdo;

    // Konstruktor jest prywatny, żeby nikt nie mógł zrobić "new Database()" z zewnątrz.
    // Połączenie tworzy się tylko raz, wewnątrz tej klasy.
    private function __construct()
    {
        // Dane konfiguracyjne bazy (zgodne z docker-compose.yml)
        $host = 'db'; // To jest nazwa usługi/kontenera w Dockerze
        $dbname = 'assetvault'; 
        $username = 'assetuser';    
        $password = 'assetpass';  

        // Opcje połączenia PDO
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Wyrzucaj błędy jako wyjątki (łatwiej debugować)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Wyniki z bazy zwracaj jako tablice asocjacyjne
            PDO::ATTR_EMULATE_PREPARES => false, // Używaj natywnych prepared statements (lepsze bezpieczeństwo)
        ];

        // Nawiązujemy właściwe połączenie z bazą PostgreSQL
        $this->pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, $options);
    }

    // Metoda statyczna - to jest jedyny "włącznik" bazy danych
    public static function getInstance()
    {
        // Jeśli instancja jeszcze nie istnieje -> stwórz ją (uruchomi się konstruktor)
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        // Jeśli już istnieje -> zwróć tę, która jest w pamięci
        return self::$instance;
    }

    // Zwraca obiekt PDO, na którym wykonujemy zapytania (prepare, query, execute)
    public function getConnection()
    {
        return $this->pdo;
    }
}