CREATE TABLE IF NOT EXISTS gearbox_type
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    transmission ENUM ('AUTO', 'MANU', 'CVT') NOT NULL UNIQUE ,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO gearbox_type (transmission)
VALUES ('AUTO'),
       ('MANU'),
       ('CVT');