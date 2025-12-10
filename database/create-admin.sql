
-- Create Admin User
-- Email: ojobabajide2018@gmail.com
-- Password: Password@123

INSERT INTO users (email, password_hash, first_name, last_name, role, email_verified, created_at, updated_at)
VALUES (
  'ojobabajide2018@gmail.com',
  '$2a$10$RgJfxTf.1Nd62MUsW8Tueuu52i.Mar4CbAEPt7Byo/AtxfRi/RzVy',
  'Admin',
  'User',
  'admin',
  true,
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  password_hash = '$2a$10$RgJfxTf.1Nd62MUsW8Tueuu52i.Mar4CbAEPt7Byo/AtxfRi/RzVy',
  role = 'admin',
  updated_at = NOW();

-- Verify the admin was created
SELECT id, email, first_name, last_name, role, created_at 
FROM users 
WHERE email = 'ojobabajide2018@gmail.com';
