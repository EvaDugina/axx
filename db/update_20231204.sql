ALTER TABLE ax.ax_color_theme ADD COLUMN status integer;
-- 0 - картинка не входящая в базовый набор, являющаяся закгруженной преподом
-- 1 - картинка входящая в базовый набор

UPDATE ax.ax_color_theme SET status = 0 WHERE disc_id is null;
UPDATE ax.ax_color_theme SET status = 1 WHERE disc_id is not null;

ALTER TABLE ax.ax_color_theme ADD COLUMN page_id integer;
-- page_id в рамках которой загружена фотография

INSERT INTO ax.ax_color_theme(id, name, bg_color, src_url, status) VALUES
(-1, 'Серый', '#7a7a7a', 'src/img/no-foto.jpg', 1);