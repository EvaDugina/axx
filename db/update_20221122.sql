INSERT INTO public.ax_assignment_student(id, assignment_id, student_user_id) VALUES
(-27, -9, -7), (-28, -8, -7), (-29, -6, -7), (-30, -4, -7), (-31, -2, -7);

UPDATE ax_assignment SET status_code = 2, status_text = 'активно' WHERE id in (-23, -24);
UPDATE ax_assignment SET status_code = 5, status_text = 'ожидает проверки' WHERE id in (-14, -16);

DROP TABLE ax_color_theme;
CREATE TABLE ax_color_theme (
    id SERIAL, 
    disc_id INTEGER,
    name text, 
    bg_color text, 
    src_url text, 
    CONSTRAINT ax_color_theme_pkey PRIMARY KEY (id)
); ALTER TABLE ax_color_theme OWNER TO accelerator;

INSERT INTO public.ax_color_theme(id, disc_id, name, bg_color, src_url) VALUES
(0, -1, 'Красный', '#dc3545', 'src/img/red.jpg'), (1, -2, 'Жёлтый', '#ffc107', 'src/img/yellow.jpg'), 
(2, -3, 'Зелёный', '#198754', 'src/img/green.jpg'), (3, -4, 'Синий', '#1266f1', 'src/img/blue.jpg'),
(4, -5, 'Фиолетовый', '#6f42c1', 'src/img/purple.jpg');

UPDATE ax_page SET color_theme_id = 0;

GRANT USAGE, SELECT ON SEQUENCE ax_assignment_id_seq TO accelerator;

-- (раскомментировать строчку, если колонки нет)
-- ALTER TABLE ax_message ADD COLUMN visibility integer;

UPDATE ax_message SET visibility = 0;
UPDATE ax_message SET sender_user_type = 2 WHERE sender_user_type = 1;
UPDATE ax_message SET sender_user_type = 3 WHERE sender_user_type = 0;
