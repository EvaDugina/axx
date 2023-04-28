ALTER TABLE ax_assignment ADD COLUMN status INTEGER;

UPDATE ax_assignment SET status = 4 WHERE status_code = 3;
UPDATE ax_assignment SET status = 1 WHERE status_code = 5;
UPDATE ax_assignment SET status = -1 WHERE status_code = 1;
UPDATE ax_assignment SET status = 0 WHERE status_code != 5 AND status_code != 3; 
UPDATE ax_assignment SET status_code = 2 WHERE status_code = 3;
UPDATE ax_assignment SET status_code = 2 WHERE status_code = 5;
UPDATE ax_assignment SET status_code = 2 WHERE status_code = 1;


-- status = статус выполнения:
-- -1 - недоступно для выполнения
-- 0 - ожидает выполнения
-- 1 - ожидает проверки
-- 2 - проверено, не оценено
-- 3 - ожидает повторной проверки
-- 4 - оценено