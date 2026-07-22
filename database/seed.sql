-- Seed data: the original 4 books, plus a demo member
USE central_library;

INSERT INTO books (title, author, total_copies, available_copies) VALUES
  ('Algorithms',   'Robert Sedgewick', 2, 2),
  ('Django',       'Adrian Holovaty',  1, 1),
  ('CLRS',         'Cormen et al.',    3, 3),
  ('Python Notes', 'Jithendra Nara',   1, 1)
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO members (name, email) VALUES
  ('Jithendra Nara', 'jithendra@example.com')
ON DUPLICATE KEY UPDATE name = name;
