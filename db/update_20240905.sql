ALTER TABLE ax.ax_task ADD COLUMN mark_type text;
UPDATE ax.ax_task SET mark_type = 'оценка';

UPDATE ax.ax_assignment SET mark = '5' WHERE mark = 'зачтено';
