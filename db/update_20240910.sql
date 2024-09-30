DROP TABLE ax.ax_message_delivery;

CREATE TABLE ax.ax_message_delivery (    -- признаки уведомлений о получении сообщений
  id serial,
  message_id integer,  -- --> ax_message
  recipient_user_id integer,  -- --> students
  status INTEGER,  -- 0 - не прочитано, 1 - прочитано
  CONSTRAINT ax_message_delivery_pkey PRIMARY KEY (id)
); ALTER TABLE ax.ax_message_delivery OWNER TO postgres;

INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
SELECT ax.ax_message.id, ax.ax_assignment_student.student_user_id, 1 FROM ax.ax_message
INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_message.assignment_id;

INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
SELECT ax.ax_message.id, ax.ax_page_prep.prep_user_id, 1 FROM ax.ax_message
INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id;

GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA ax TO accelerator;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA ax TO accelerator;