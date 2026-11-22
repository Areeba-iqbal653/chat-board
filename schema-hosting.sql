CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(20)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL
);

CREATE TABLE rooms (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE messages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  room_id    INT          NOT NULL,
  user_id    INT          NOT NULL,
  body       VARCHAR(500) NOT NULL,
  created_at DATETIME     NOT NULL,
  FOREIGN KEY (room_id) REFERENCES rooms(id)  ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
  INDEX idx_room_id (room_id, id)
);

INSERT INTO rooms (name) VALUES ('General'), ('Study'), ('Random');
