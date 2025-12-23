# AssetVault – Dokumentacja techniczna

## Spis treści

1. [Opis projektu](#opis-projektu)
2. [Technologie](#technologie)
3. [Struktura katalogów](#struktura-katalogów)
4. [Funkcjonalności](#funkcjonalności)
5. [Instalacja i uruchomienie](#instalacja-i-uruchomienie)
6. [Konfiguracja bazy danych](#konfiguracja-bazy-danych)
7. [Modele danych](#modele-danych)
8. [Uprawnienia i role](#uprawnienia-i-role)
9. [Widoki i UI](#widoki-i-ui)
10. [Przykładowe dane](#przykładowe-dane)
11. [Zarządzanie assetami](#zarządzanie-assetami)
12. [Bezpieczeństwo](#bezpieczeństwo)
13. [Autorzy](#autorzy)

---

## Opis projektu

**AssetVault** to aplikacja webowa napisana w PHP, służąca do zarządzania, przeglądania i udostępniania zasobów cyfrowych (assetów) takich jak pliki graficzne, modele 3D oraz ich miniatury. Umożliwia rejestrację użytkowników, logowanie, podział ról (admin, user), upload i pobieranie plików oraz zaawansowane zarządzanie assetami i uprawnieniami.

---

## Technologie

* **Backend:** PHP 8.x+
* **Frontend:** HTML5, CSS3 (dedykowane arkusze dla każdego widoku)
* **Baza danych:** PostgreSQL (lub kompatybilny system SQL)
* **Przechowywanie plików:** system plików (folder `uploads`)
* **Obsługa miniatur:** folder `images`
* **Autoryzacja:** własny system ról i uprawnień

---

## Struktura katalogów

```
public/
│
├── auth.php             # logika uwierzytelniania
├── config.php           # konfiguracja bazy danych i aplikacji
├── index.php            # punkt wejścia aplikacji
│
├── classes/             # klasy PHP
│   ├── Asset.php
│   ├── Database.php
│   └── User.php
│
├── images/              # obrazy systemowe i miniatury assetów
│   ├── logo-black.png
│   ├── default-thumb.png
│   └── thumb_xxx.jpg/png
│
├── styles/              # arkusze CSS
│   ├── asset.css
│   ├── assets.css
│   ├── asset_list.css
│   ├── auth.css
│   ├── dashboard.css
│   └── upload_edit.css
│
├── uploads/             # przesyłane pliki użytkowników (assetów)
│   ├── *.png, *.jpg, *.fbx, *.blend
│
└── views/               # widoki aplikacji (MVC)
    ├── asset.php
    ├── assets.php
    ├── dashboard.php
    ├── delete_asset.php
    ├── edit_asset.php
    ├── login.php
    ├── logout.php
    ├── register.php
    ├── upload.php
    └── partials/
        └── asset_list.php
```

---

## Funkcjonalności

* Rejestracja i logowanie użytkowników
* Przypisywanie ról: admin/user
* Przeglądanie listy assetów z miniaturkami i filtrowaniem po typach
* Przesyłanie plików (upload assetów, upload miniaturek)
* Podgląd szczegółów assetu
* Edycja i usuwanie assetów (zależnie od uprawnień)
* Podgląd i zarządzanie własnymi assetami
* Pobieranie assetów
* Dedykowane widoki dla admina (pełne uprawnienia)
* Responsywny, rozbudowany interfejs z dedykowanymi stylami CSS
* Baza danych z czytelną strukturą relacyjną

---

## Instalacja i uruchomienie

Aplikacja działa w środowisku **Docker**. Do uruchomienia projektu wymagane są:

* [Docker](https://www.docker.com/products/docker-desktop)
* [Docker Compose](https://docs.docker.com/compose/)

### Krok 1: Sklonuj repozytorium

```bash
git clone <adres_repozytorium>
cd <folder_projektu>
```

### Krok 2: Skonfiguruj pliki środowiskowe

Przed pierwszym uruchomieniem możesz edytować plik `.env` (jeśli występuje) lub sprawdzić/ustawić zmienne środowiskowe w plikach `docker-compose.yml` i `config.php`, takie jak:

* nazwa bazy danych
* użytkownik i hasło do bazy
* porty serwera WWW i bazy danych

### Krok 3: Uruchom kontenery Docker

W głównym katalogu projektu uruchom polecenie:

```bash
docker-compose up --build
```

Spowoduje to:

* Zbudowanie obrazu PHP z Twoją aplikacją (serwer www, np. Apache)
* Utworzenie kontenera z bazą danych PostgreSQL
* (Opcjonalnie) Utworzenie kontenera narzędziowego do migracji bazy lub adminer/phpmyadmin

### Krok 4: Inicjalizacja bazy danych

Zainicjaluzj baze danych kodem z pliku ```init.sql```

```bash
docker exec -i <nazwa_kontenera_postgres> psql -U <db_user> -d <db_name> < /docker-entrypoint-initdb.d/init.sql
```

### Krok 5: Wejdź na aplikację

Po poprawnym starcie kontenerów aplikacja będzie dostępna pod adresem:

```
http://localhost:<port_www>
```

Domyślny port serwera www to najczęściej `8080` lub `8000` (sprawdź w `docker-compose.yml`).

### Krok 6: Przesyłanie i zapisywanie plików

Upewnij się, że katalogi `public/uploads` oraz `public/images` mają nadane odpowiednie prawa zapisu (w środku kontenera – Dockerfile powinien to ustawiać automatycznie).

---

## Konfiguracja bazy danych

* Nazwy tabel: `users`, `assets`, `asset_images`, `roles`
* Relacje:

  * `assets.user_id` → `users.id`
  * `asset_images.asset_id` → `assets.id`
* Rola użytkownika określana przez pole `role` w tabeli `users` (`admin` / `user`)
* Miniatury powiązane z assetami przez tabelę `asset_images`

---

## Modele danych

### Tabela `users`

| Kolumna  | Typ                | Opis                              |
| -------- | ------------------ | --------------------------------- |
| id       | SERIAL PRIMARY KEY | Unikalny identyfikator            |
| username | VARCHAR(50)        | Nazwa użytkownika                 |
| email    | VARCHAR(100)       | Email użytkownika                 |
| password | VARCHAR(255)       | Hasło (zahashowane)               |
| role     | VARCHAR(10)        | Rola użytkownika (`admin`/`user`) |

### Tabela `assets`

| Kolumna     | Typ                | Opis                                |
| ----------- | ------------------ | ----------------------------------- |
| id          | SERIAL PRIMARY KEY | Unikalny identyfikator assetu       |
| user\_id    | INTEGER            | ID właściciela assetu               |
| name        | VARCHAR(100)       | Nazwa assetu                        |
| description | TEXT               | Opis assetu                         |
| type        | VARCHAR(50)        | Typ assetu (np. Tekstura, Model 3D) |
| file\_path  | VARCHAR(255)       | Ścieżka do pliku                    |
| created\_at | TIMESTAMP          | Data dodania                        |

### Tabela `asset_images`

| Kolumna     | Typ                | Opis                           |
| ----------- | ------------------ | ------------------------------ |
| id          | SERIAL PRIMARY KEY | Unikalny identyfikator obrazka |
| asset\_id   | INTEGER            | Powiązanie z assetem           |
| image\_path | VARCHAR(255)       | Ścieżka do pliku miniatury     |

### Tabela `roles`

| Kolumna | Typ                | Opis                         |
| ------- | ------------------ | ---------------------------- |
| id      | SERIAL PRIMARY KEY | Unikalny identyfikator roli  |
| name    | VARCHAR(50)        | Nazwa roli (`admin`, `user`) |

### Tabela `asset_types`

| Kolumna | Typ                | Opis                         |
| ------- | ------------------ | ---------------------------- |
| id      | SERIAL PRIMARY KEY | Unikalny identyfikator typu |
| name    | VARCHAR(50)        | Nazwa typu (`Model3D`, `Texture`, `Audio`) |

---

## Uprawnienia i role

* **admin**: pełne uprawnienia do zarządzania wszystkimi assetami i użytkownikami
* **user**: uprawnienia ograniczone do własnych assetów

W aplikacji role są przechowywane zarówno w tabeli `roles`, jak i jako pole tekstowe w `users.role`.

---

## Widoki i UI

Każdy widok jest osobnym plikiem PHP w folderze `views/`:

* `assets.php` – lista wszystkich assetów, filtrowanie, pobieranie
* `asset.php` – podgląd szczegółów assetu, karuzela miniatur, info o autorze i dacie
* `dashboard.php` – panel użytkownika, lista własnych assetów, edycja profilu
* `edit_asset.php` – edycja assetu (tylko właściciel/admin)
* `upload.php` – przesyłanie nowego assetu
* `delete_asset.php` – potwierdzenie usunięcia assetu
* `login.php` / `register.php` / `logout.php` – autoryzacja i rejestracja
* `partials/asset_list.php` – komponent do renderowania listy assetów

---

## Przykładowe dane

Przykładowe wypełnienie bazy (użytkownicy, assety, miniatury) znajduje się na screenach i w przesłanym SQL-u.

**Użytkownicy:**

* `admin` (rola: admin)
* `Oliwia Zając` (rola: admin)
* ...
* `Paweł`, `Michał`, `Krzysiek` (rola: user)

**Assety:**

* `Test` (Tekstura)
* `Doll2` (Model 3D)
* `Dragon 3D Model 4` (Tekstura)
* `Anime Doll` (Model 3D)
* `Water` (Tekstura)

**Miniatury assetów**:
Powiązane z assetami przez `asset_images`.

---

## Zarządzanie assetami

* **Dodawanie:** Użytkownik może przesłać asset oraz do 3 miniaturek podglądu.
* **Edycja:** Właściciel assetu oraz admin mogą edytować asset oraz miniaturki.
* **Usuwanie:** Usunięcie assetu skutkuje usunięciem wszystkich powiązanych miniaturek.
* **Podgląd:** Szczegóły, lista miniaturek w formie karuzeli, informacje o autorze i dacie dodania.
* **Pobieranie:** Assety można pobrać bezpośrednio z widoku szczegółów lub listy.

---

## Bezpieczeństwo

* Hasła są przechowywane w postaci haszowanej.
* Unikalne nazwy użytkownika i email.
* Walidacja uprawnień przed edycją/usuwaniem assetów.
* Ograniczenie uprawnień do panelu admina.
* Spójność i integralność relacji dzięki kluczom obcym i ON DELETE CASCADE.
* Ochrona przed SQL Injection (Prepared Statements)
* Ochrona przed Cross-Site Scripting (XSS)

---

## Autorzy

Projekt przygotowany przez Jakub Lenart WIIT 3

---

