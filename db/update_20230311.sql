-- DROP TABLE ax_message_delivery;

-- CREATE TABLE ax_message_delivery (    -- признаки уведомлений о получении сообщений
--   id serial,
--   message_id integer,  -- --> ax_message
--   recipient_user_id integer,  -- --> students
--   status INTEGER,  -- 0 - не прочитано, 1 - прочитано
--   CONSTRAINT ax_message_delivery_pkey PRIMARY KEY (id)
-- ); ALTER TABLE ax_message_delivery OWNER TO postgres;

-- INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
-- SELECT ax_message.id, ax_assignment_student.student_user_id, 1 FROM ax_message
-- INNER JOIN ax_assignment ON ax_assignment.id = ax_message.assignment_id
-- INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_message.assignment_id;

-- INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
-- SELECT ax_message.id, ax_page_prep.prep_user_id, 1 FROM ax_message
-- INNER JOIN ax_assignment ON ax_assignment.id = ax_message.assignment_id
-- INNER JOIN ax_task ON ax_task.id = ax_assignment.task_id
-- INNER JOIN ax_page ON ax_page.id = ax_task.page_id
-- INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id;

