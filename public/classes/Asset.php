<?php
// Używamy __DIR__, aby poprawnie zaimportować plik z tego samego katalogu (połączenie z bazą)
require_once __DIR__ . '/Database.php';

class Asset
{
    private $pdo;
    // Lista dozwolonych rozszerzeń plików, które można wrzucić na serwer
    private $allowedExtensions = ["fbx", "obj", "png", "jpg", "jpeg", "tga", "zip", "blend", "mp3", "wav"];

    public function __construct()
    {
        // Przy tworzeniu obiektu Asset łączymy się z bazą danych
        $this->pdo = Database::getInstance()->getConnection();
    }

    // Pobiera jeden konkretny asset po jego ID (wraz z nazwą autora)
    public function getById($assetId)
    {   
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.username FROM assets a JOIN users u ON a.user_id = u.id WHERE a.id = :id"
        );
        $stmt->execute([':id' => $assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jeśli asset istnieje, dociągamy do niego listę miniaturek (Showcase)
        if  ($asset) {
            $stmt2 = $this->pdo->prepare("SELECT * FROM asset_images WHERE asset_id = :asset_id");
            $stmt2->execute([':asset_id' => $assetId]);
            $asset['images'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        return $asset;
    }

    // Pobiera wszystkie assety należące do konkretnego użytkownika (np. do Dashboardu)
    public function findByUserId($userId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM assets WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$assets) {
            return [];
        }
    
        // Do każdego assetu dociągamy jego miniaturki
        foreach ($assets as $key => $asset) {
            $stmt2 = $this->pdo->prepare("SELECT * FROM asset_images WHERE asset_id = :asset_id");
            $stmt2->execute([':asset_id' => $asset['id']]);
            $assets[$key]['images'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $assets;
    }

    // Proste pobranie wszystkich assetów z bazy (bez dodatkowych danych)
    public function findAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM assets");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pobiera wszystkie assety wraz z ich obrazkami w jednym zapytaniu (zoptymalizowane)
    public function findAllWithImages()
    {
        $sql = "
            SELECT 
                a.*, 
                string_agg(ai.image_path, ';') as image_paths
            FROM 
                assets a
            LEFT JOIN 
                asset_images ai ON a.id = ai.asset_id
            GROUP BY 
                a.id
            ORDER BY 
                a.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Przetwarzamy wynik zapytania, żeby zamienić string z obrazkami na tablicę
        foreach ($assets as $key => $asset) {
            if (!empty($asset['image_paths'])) {
                $paths = explode(';', $asset['image_paths']);
                $assets[$key]['images'] = array_map(function($path) {
                    return ['image_path' => $path];
                }, $paths);
            } else {
                $assets[$key]['images'] = [];
            }
            unset($assets[$key]['image_paths']);
        }

        return $assets;
    }

    // Alternatywna metoda pobierania wszystkich assetów (opcjonalnie filtrowana po user_id)
    public function getAll($userId = null)
    {
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT * FROM assets WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $userId]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM assets ORDER BY created_at DESC");
        }
        return $stmt->fetchAll();
    }

    // Aktualizacja danych assetu (nazwa, opis, typ)
    public function update($assetId, $name, $description, $type, $userId, $isAdmin = false)
    {
        // Admin może edytować wszystko, zwykły user tylko swoje pliki
        if ($isAdmin) {
            $stmt = $this->pdo->prepare(
                "UPDATE assets SET name = :name, description = :description, type = :type WHERE id = :id"
        );
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':type' => $type,
            ':id' => $assetId
        ]);
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE assets SET name = :name, description = :description, type = :type WHERE id = :id AND user_id = :user_id"
            );
            return $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':type' => $type,
                ':id' => $assetId,
                ':user_id' => $userId
            ]);
        }
    }

    // Usuwanie assetu z bazy
    public function delete($assetId, $userId, $isAdmin = false)
    {
        if ($isAdmin) {
            $stmt = $this->pdo->prepare("DELETE FROM assets WHERE id = :id");
            return $stmt->execute([':id' => $assetId]);
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM assets WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([':id' => $assetId, ':user_id' => $userId]);
        }
    }

    // Prosty upload (stara wersja, bez miniaturek)
    public function upload($userId, $name, $description, $type, $file)
    {
        $errors = [];

        // Walidacja podstawowa
        if (empty($name) || empty($type) || empty($file['name'])) {
            $errors[] = 'Please fill in all required fields and select a file.';
            return [false, $errors];
        }

        $maxFileSize = 1024 * 1024 * 1024; // Limit 1 GB
        
        // POPRAWKA: Używamy __DIR__ żeby wskazać folder uploads względem pliku Asset.php
        $uploadDir = __DIR__ . '/../uploads/';

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $this->allowedExtensions)) {
            $errors[] = "File type not allowed.";
        }
        if ($file['size'] > $maxFileSize) {
            $errors[] = "File is too large.";
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error. Code: " . $file['error'];
        }

        if ($errors) {
            return [false, $errors];
        }

        // Generujemy unikalną nazwę pliku, żeby nie nadpisać istniejących
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = "Failed to upload file.";
            return [false, $errors];
        }

        $dbFilePath = 'uploads/' . $fileName; 

        $stmt = $this->pdo->prepare("INSERT INTO assets (user_id, name, description, type, file_path, created_at) VALUES (:user_id, :name, :description, :type, :file_path, NOW())");
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':description' => $description,
            ':type' => $type,
            ':file_path' => $dbFilePath 
        ]);

        // Jeśli zapis do bazy się nie udał, usuwamy wgrany plik (sprzątanie)
        if (!$result) {
            $errors[] = "Database error. Asset not saved.";
            @unlink($targetPath);
            return [false, $errors];
        }

        return [true, []];
    }

    // Pełny upload assetu wraz z opcjonalnymi miniaturkami (używane w upload.php)
    public function uploadWithThumbnails($userId, $name, $description, $type, $file, $thumbnails = null)
    {
        $errors = [];
        if (empty($name)) $errors[] = "Field 'Asset Name' is required.";
        if (empty($description)) $errors[] = "Field 'Description' is required.";
        if (empty($type)) $errors[] = "Field 'Type' is required.";
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = "Main file is required.";

        if (empty($errors)) {
            $originalName = basename($file['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (in_array($extension, $this->allowedExtensions)) {
                $newFileName = uniqid() . '.' . $extension;
                // UPLOAD_DIR pochodzi z config.php (np. 'uploads/')
                $uploadPath = UPLOAD_DIR . $newFileName;

                // POPRAWKA: __DIR__ . '/../' przed ścieżką zapewnia poprawne zapisanie pliku
                if (move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $uploadPath)) {
                    $stmt = $this->pdo->prepare("INSERT INTO assets (user_id, name, description, type, file_path) 
                                           VALUES (:user_id, :name, :description, :type, :file_path)");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':name' => $name,
                        ':description' => $description,
                        ':type' => $type,
                        ':file_path' => $uploadPath
                    ]);

                    $assetId = $this->pdo->lastInsertId();

                    // Obsługa wgrywania miniaturek (jeśli wybrano)
                    if ($thumbnails && !empty($thumbnails['name'][0])) {
                        for ($i = 0; $i < min(3, count($thumbnails['name'])); $i++) {
                            if ($thumbnails['error'][$i] === 0) {
                                $thumbExt = strtolower(pathinfo($thumbnails['name'][$i], PATHINFO_EXTENSION));
                                if (in_array($thumbExt, ['jpg', 'jpeg', 'png'])) {
                                    $thumbName = uniqid('thumb_') . '.' . $thumbExt;
                                    $thumbPath = THUMBNAIL_DIR . $thumbName;
                                    
                                    // POPRAWKA: __DIR__
                                    move_uploaded_file($thumbnails['tmp_name'][$i], __DIR__ . '/../' . $thumbPath);

                                    $stmtImg = $this->pdo->prepare("INSERT INTO asset_images (asset_id, image_path) 
                                                              VALUES (:asset_id, :image_path)");
                                    $stmtImg->execute([
                                        ':asset_id' => $assetId,
                                        ':image_path' => $thumbPath
                                    ]);
                                }
                            }
                        }
                    }

                    return [true, []];
                } else {
                    $errors[] = "Error saving file.";
                }
            } else {
                $errors[] = "Unsupported file extension.";
            }
        }
        return [false, $errors];
    }

    // Zaawansowana edycja assetu (pozwala podmienić plik główny i miniaturki)
    public function updateWithFiles($assetId, $name, $description, $type, $userId, $isAdmin, $updateMain, $mainFile, $updateShowcase, $thumbnails)
    {
        $asset = $this->getById($assetId);
        if (!$asset) return [false, ["Asset not found."]];

        $errors = [];

        // Walidacja danych formularza
        if (empty($name)) $errors[] = "Asset name is required.";
        if (empty($description)) $errors[] = "Description is required.";
        if (empty($type)) $errors[] = "Type is required.";

        if ($updateMain && (!$mainFile || $mainFile['error'] !== UPLOAD_ERR_OK)) {
            $errors[] = "Main file must be uploaded if you selected Update Main Asset.";
        }
        if ($updateShowcase && (!$thumbnails || empty($thumbnails['name'][0]))) {
            $errors[] = "At least one showcase image must be uploaded if you selected Update Showcase.";
        }

        if (!empty($errors)) return [false, $errors];

        // Rozpoczynamy transakcję bazodanową (wszystko albo nic)
        $this->pdo->beginTransaction();

        try {
            // Aktualizujemy dane tekstowe assetu
            $sql = $isAdmin ?
                "UPDATE assets SET name=:name, description=:description, type=:type WHERE id=:id"
                : "UPDATE assets SET name=:name, description=:description, type=:type WHERE id=:id AND user_id=:user_id";
            $params = [
                ':name' => $name,
                ':description' => $description,
                ':type' => $type,
                ':id' => $assetId
            ];
            if (!$isAdmin) $params[':user_id'] = $userId;
            $this->pdo->prepare($sql)->execute($params);

            // Jeśli wybrano aktualizację głównego pliku
            if ($updateMain && $mainFile && $mainFile['error'] === UPLOAD_ERR_OK) {
                // Usuwamy stary plik z dysku
                if (!empty($asset['file_path']) && file_exists(__DIR__ . '/../' . $asset['file_path'])) {
                    @unlink(__DIR__ . '/../' . $asset['file_path']);
                }
                
                // Wgrywamy nowy plik
                $ext = strtolower(pathinfo($mainFile['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $this->allowedExtensions)) {
                    $this->pdo->rollBack();
                    return [false, ["Unsupported file extension for main file."]];
                }
                $newFileName = uniqid() . '.' . $ext;
                $uploadPath = UPLOAD_DIR . $newFileName;
                
                if (!move_uploaded_file($mainFile['tmp_name'], __DIR__ . '/../' . $uploadPath)) {
                    $this->pdo->rollBack();
                    return [false, ["Error uploading new main file."]];
                }
                // Aktualizujemy ścieżkę w bazie
                $this->pdo->prepare("UPDATE assets SET file_path = :file_path WHERE id = :id")
                    ->execute([':file_path' => $uploadPath, ':id' => $assetId]);
            }

            // Jeśli wybrano aktualizację miniaturek
            if ($updateShowcase) {
                // Najpierw usuwamy stare miniaturki z dysku i bazy
                if (!empty($asset['images'])) {
                    foreach ($asset['images'] as $img) {
                        if (!empty($img['image_path']) && file_exists(__DIR__ . '/../' . $img['image_path'])) {
                            @unlink(__DIR__ . '/../' . $img['image_path']);
                        }
                    }
                    $this->pdo->prepare("DELETE FROM asset_images WHERE asset_id = :asset_id")
                        ->execute([':asset_id' => $assetId]);
                }
                // Wgrywamy nowe miniaturki (max 3)
                for ($i = 0; $i < min(3, count($thumbnails['name'])); $i++) {
                    if ($thumbnails['error'][$i] === 0) {
                        $thumbExt = strtolower(pathinfo($thumbnails['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($thumbExt, ['jpg', 'jpeg', 'png'])) {
                            $thumbName = uniqid('thumb_') . '.' . $thumbExt;
                            $thumbPath = THUMBNAIL_DIR . $thumbName;
                            
                            move_uploaded_file($thumbnails['tmp_name'][$i], __DIR__ . '/../' . $thumbPath);
                            
                            $this->pdo->prepare(
                                "INSERT INTO asset_images (asset_id, image_path) VALUES (:asset_id, :image_path)"
                            )->execute([
                                ':asset_id' => $assetId,
                                ':image_path' => $thumbPath
                            ]);
                        }
                    }
                }
            }

            // Zatwierdzamy zmiany w bazie
            $this->pdo->commit();
            return [true, []];
        } catch (Exception $e) {
            // W razie błędu cofamy wszystkie zmiany w bazie
            $this->pdo->rollBack();
            return [false, ["Error updating asset: " . $e->getMessage()]];
        }
    }
}