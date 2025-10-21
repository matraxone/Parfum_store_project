USE `parfum_shop`;
-- Categories removed

-- Admin user (password: admin123)
INSERT INTO users (name,email,password_hash,role,birthdate,parent_consent,created_at)
SELECT 'Admin','admin@example.com','$2y$10$CwTycUXWue0Thq9StjUM0uJ8gG/2ywk6b7bQm8vF6cV3cC5Q9V7xS','admin','1990-01-01',0,NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='admin@example.com');

-- Sample products
INSERT INTO products (name,description,price,stock,volume_ml,image_path,is_active,created_at) VALUES
('Pistola ad acqua','Divertente pistola ad acqua per giochi estivi.',9.90,50,50.0,NULL,1,NOW()),
('Fucile soft dart','Fucile con dardi in gomma morbida, sicuro e divertente.',24.90,30,75.0,NULL,1,NOW()),
('Spada laser giocattolo','Spada luminosa per giochi di ruolo.',14.90,20,100.0,NULL,1,NOW());
