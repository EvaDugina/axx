INSERT INTO public.ax_assignment_student(id, assignment_id, student_user_id) VALUES
(-27, -9, -7), (-28, -8, -7), (-29, -6, -7), (-30, -4, -7), (-31, -2, -7);

UPDATE ax_assignment SET status_code = 2, status_text = 'активно' WHERE id in (-23, -24);
UPDATE ax_assignment SET status_code = 5, status_text = 'ожидает проверки' WHERE id in (-14, -16);