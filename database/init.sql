CREATE DATABASE IF NOT EXISTS curso_exemplo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE curso_exemplo;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4;

INSERT INTO products (name, category)
SELECT 'Caderno de projetos', 'Material'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Caderno de projetos');

INSERT INTO products (name, category)
SELECT 'Curso de PHP', 'Formacao'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Curso de PHP');

INSERT INTO products (name, category)
SELECT 'Laboratorio DevOps', 'Infraestrutura'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Laboratorio DevOps');