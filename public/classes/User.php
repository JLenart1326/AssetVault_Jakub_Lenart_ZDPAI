<?php
// Używamy __DIR__, aby naprawić błąd "No such file" przy importowaniu bazy danych
require_once __DIR__ . '/Database.php';

class User
{
    private $pdo;

    public function __construct()
    {
        // Pobieramy połączenie z bazą danych (Singleton)
        $this->pdo = Database::getInstance()->getConnection();
    }

    // Pobiera dane użytkownika na podstawie ID
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Pobiera dane użytkownika na podstawie adresu email (przydatne przy logowaniu)
    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    // Pobiera dane użytkownika na podstawie nazwy (przydatne przy sprawdzaniu czy login jest wolny)
    public function findByUsername($username)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    // Rejestracja nowego użytkownika
    public function register($username, $email, $password, $role = 'user')
    {
        // Haszujemy hasło przed zapisaniem do bazy (bezpieczeństwo!)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => $role
        ]);
    }

    // Aktualizacja danych użytkownika (Dashboard)
    public function updateUser($userId, array $data)
    {
        $fields = [];
        $params = [':id' => $userId];

        // Budujemy dynamicznie zapytanie SQL w zależności od tego, co użytkownik chce zmienić
        if (isset($data['username'])) {
            $fields[] = "username = :username";
            $params[':username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }
        
        // Jeśli użytkownik zmienia hasło, musimy je ponownie zahaszować
        if (!empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Jeśli nie ma nic do zmiany, kończymy
        if (empty($fields)) {
            return true;
        }

        // Składamy kawałki zapytania w całość
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Logowanie użytkownika
    public function login($email, $password)
    {
        // Najpierw szukamy użytkownika po mailu
        $user = $this->findByEmail($email);
        
        // Jeśli użytkownik istnieje I hasło pasuje do hasha w bazie -> logujemy
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }

    // Wylogowywanie (niszczenie sesji)
    public function logout()
    {
        // Czyścimy zmienne sesyjne
        session_unset();
        // Niszczymy sesję po stronie serwera
        session_destroy();

        // Usuwamy ciasteczko sesyjne z przeglądarki użytkownika (żeby był wylogowany "na czysto")
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
}