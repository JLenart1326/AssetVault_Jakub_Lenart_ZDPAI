-- Tabela użytkowników
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) DEFAULT 'user'
);

-- Tabela assetów
CREATE TABLE assets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela dla obrazków podglądu
CREATE TABLE asset_images (
    id SERIAL PRIMARY KEY,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    image_path VARCHAR(255) NOT NULL
);

-- Tabela dla ról
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50)
);


-- ROLES
INSERT INTO roles (name) VALUES
('admin'),
('user');


CREATE TABLE IF NOT EXISTS Asset_Types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

INSERT INTO Asset_Types (name) VALUES 
    ('Model3D'),
    ('Texture'),
    ('Audio')