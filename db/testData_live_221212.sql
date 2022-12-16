--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: get_semester(integer, integer); Type: FUNCTION; Schema: public; Owner: accelerator
--

CREATE FUNCTION get_semester(year integer, semester integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF (semester = 1) THEN
	RETURN (year-1)::text || '/' || year || ' весна';
    ELSIF (semester = 2) THEN
        RETURN year || '/' || (year+1)::text || ' осень';
    END IF;

    RETURN '';
  
END$$;


ALTER FUNCTION public.get_semester(year integer, semester integer) OWNER TO accelerator;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: ax_assignment; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_assignment (
    id integer NOT NULL,
    task_id integer,
    variant_comment text,
    start_limit timestamp with time zone,
    finish_limit timestamp with time zone,
    status_code integer,
    delay integer,
    status_text text,
    mark text,
    checks text
);


ALTER TABLE public.ax_assignment OWNER TO accelerator;

--
-- Name: ax_assignment_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_assignment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_assignment_id_seq OWNER TO accelerator;

--
-- Name: ax_assignment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_assignment_id_seq OWNED BY ax_assignment.id;


--
-- Name: ax_assignment_session; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_assignment_session (
    id integer NOT NULL,
    assignment_id integer,
    student_user_id integer,
    start_time timestamp with time zone,
    end_time timestamp with time zone
);


ALTER TABLE public.ax_assignment_session OWNER TO accelerator;

--
-- Name: ax_assignment_session_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_assignment_session_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_assignment_session_id_seq OWNER TO accelerator;

--
-- Name: ax_assignment_session_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_assignment_session_id_seq OWNED BY ax_assignment_session.id;


--
-- Name: ax_assignment_student; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_assignment_student (
    id integer NOT NULL,
    assignment_id integer,
    student_user_id integer
);


ALTER TABLE public.ax_assignment_student OWNER TO accelerator;

--
-- Name: ax_assignment_student_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_assignment_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_assignment_student_id_seq OWNER TO accelerator;

--
-- Name: ax_assignment_student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_assignment_student_id_seq OWNED BY ax_assignment_student.id;


--
-- Name: ax_autotest_results; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_autotest_results (
    id integer NOT NULL,
    commit_id integer,
    order_num integer,
    test_name text,
    test_timing text,
    succeeded boolean
);


ALTER TABLE public.ax_autotest_results OWNER TO accelerator;

--
-- Name: ax_autotest_results_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_autotest_results_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_autotest_results_id_seq OWNER TO accelerator;

--
-- Name: ax_autotest_results_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_autotest_results_id_seq OWNED BY ax_autotest_results.id;


--
-- Name: ax_color_theme; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_color_theme (
    id integer NOT NULL,
    disc_id integer,
    name text,
    bg_color text,
    src_url text,
    font_color text,
    dark boolean
);


ALTER TABLE public.ax_color_theme OWNER TO accelerator;

--
-- Name: ax_color_theme_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_color_theme_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_color_theme_id_seq OWNER TO accelerator;

--
-- Name: ax_color_theme_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_color_theme_id_seq OWNED BY ax_color_theme.id;


--
-- Name: ax_message; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_message (
    id integer NOT NULL,
    assignment_id integer,
    type integer,
    sender_user_type integer,
    sender_user_id integer,
    date_time timestamp with time zone,
    reply_to_id integer,
    full_text text,
    commit_id integer,
    status integer,
    visibility integer
);


ALTER TABLE public.ax_message OWNER TO accelerator;

--
-- Name: ax_message_attachment; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_message_attachment (
    id integer NOT NULL,
    message_id integer,
    file_name text,
    download_url text,
    full_text text
);


ALTER TABLE public.ax_message_attachment OWNER TO accelerator;

--
-- Name: ax_message_attachment_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_message_attachment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_message_attachment_id_seq OWNER TO accelerator;

--
-- Name: ax_message_attachment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_message_attachment_id_seq OWNED BY ax_message_attachment.id;


--
-- Name: ax_message_delivery; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_message_delivery (
    id integer NOT NULL,
    message_id integer,
    recipient_user_id integer,
    read boolean
);


ALTER TABLE public.ax_message_delivery OWNER TO accelerator;

--
-- Name: ax_message_delivery_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_message_delivery_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_message_delivery_id_seq OWNER TO accelerator;

--
-- Name: ax_message_delivery_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_message_delivery_id_seq OWNED BY ax_message_delivery.id;


--
-- Name: ax_message_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_message_id_seq OWNER TO accelerator;

--
-- Name: ax_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_message_id_seq OWNED BY ax_message.id;


--
-- Name: ax_page; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_page (
    id integer NOT NULL,
    disc_id integer,
    short_name text,
    year integer,
    semester integer,
    color_theme_id integer,
    creator_id integer,
    creation_date timestamp with time zone,
    status integer
);


ALTER TABLE public.ax_page OWNER TO accelerator;

--
-- Name: ax_page_group; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_page_group (
    id integer NOT NULL,
    page_id integer,
    group_id integer
);


ALTER TABLE public.ax_page_group OWNER TO accelerator;

--
-- Name: ax_page_group_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_page_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_page_group_id_seq OWNER TO accelerator;

--
-- Name: ax_page_group_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_page_group_id_seq OWNED BY ax_page_group.id;


--
-- Name: ax_page_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_page_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_page_id_seq OWNER TO accelerator;

--
-- Name: ax_page_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_page_id_seq OWNED BY ax_page.id;


--
-- Name: ax_page_prep; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_page_prep (
    id integer NOT NULL,
    prep_user_id integer,
    page_id integer
);


ALTER TABLE public.ax_page_prep OWNER TO accelerator;

--
-- Name: ax_page_prep_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_page_prep_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_page_prep_id_seq OWNER TO accelerator;

--
-- Name: ax_page_prep_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_page_prep_id_seq OWNED BY ax_page_prep.id;


--
-- Name: ax_settings; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_settings (
    user_id integer NOT NULL,
    email text,
    notification_type integer,
    monaco_dark boolean
);


ALTER TABLE public.ax_settings OWNER TO accelerator;

--
-- Name: ax_solution_commit; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_solution_commit (
    id integer NOT NULL,
    assignment_id integer,
    session_id integer,
    student_user_id integer,
    type integer,
    autotest_results text
);


ALTER TABLE public.ax_solution_commit OWNER TO accelerator;

--
-- Name: ax_solution_commit_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_solution_commit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_solution_commit_id_seq OWNER TO accelerator;

--
-- Name: ax_solution_commit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_solution_commit_id_seq OWNED BY ax_solution_commit.id;


--
-- Name: ax_solution_file; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_solution_file (
    id integer NOT NULL,
    assignment_id integer,
    commit_id integer,
    type integer,
    file_name text,
    download_url text,
    full_text text
);


ALTER TABLE public.ax_solution_file OWNER TO accelerator;

--
-- Name: ax_solution_file_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_solution_file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_solution_file_id_seq OWNER TO accelerator;

--
-- Name: ax_solution_file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_solution_file_id_seq OWNED BY ax_solution_file.id;


--
-- Name: ax_student_page_info; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_student_page_info (
    id integer NOT NULL,
    student_user_id integer,
    page_id integer,
    total_count integer,
    passed_count integer,
    variant_comment text,
    variant_num text
);


ALTER TABLE public.ax_student_page_info OWNER TO accelerator;

--
-- Name: ax_student_page_info_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_student_page_info_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_student_page_info_id_seq OWNER TO accelerator;

--
-- Name: ax_student_page_info_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_student_page_info_id_seq OWNED BY ax_student_page_info.id;


--
-- Name: ax_task; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_task (
    id integer NOT NULL,
    page_id integer,
    type integer,
    title text,
    description text,
    max_mark text,
    status integer,
    checks text
);


ALTER TABLE public.ax_task OWNER TO accelerator;

--
-- Name: ax_task_file; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE ax_task_file (
    id integer NOT NULL,
    type integer,
    task_id integer,
    file_name text,
    download_url text,
    full_text text
);


ALTER TABLE public.ax_task_file OWNER TO accelerator;

--
-- Name: ax_task_file_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_task_file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_task_file_id_seq OWNER TO accelerator;

--
-- Name: ax_task_file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_task_file_id_seq OWNED BY ax_task_file.id;


--
-- Name: ax_task_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE ax_task_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.ax_task_id_seq OWNER TO accelerator;

--
-- Name: ax_task_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE ax_task_id_seq OWNED BY ax_task.id;


--
-- Name: discipline; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE discipline (
    id integer NOT NULL,
    name text
);


ALTER TABLE public.discipline OWNER TO accelerator;

--
-- Name: discipline_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE discipline_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.discipline_id_seq OWNER TO accelerator;

--
-- Name: discipline_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE discipline_id_seq OWNED BY discipline.id;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE groups (
    id integer NOT NULL,
    name text,
    year integer
);


ALTER TABLE public.groups OWNER TO accelerator;

--
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.groups_id_seq OWNER TO accelerator;

--
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE groups_id_seq OWNED BY groups.id;


--
-- Name: students; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE students (
    id integer NOT NULL,
    first_name text,
    middle_name text,
    last_name text,
    login text,
    role integer
);


ALTER TABLE public.students OWNER TO accelerator;

--
-- Name: students_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.students_id_seq OWNER TO accelerator;

--
-- Name: students_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE students_id_seq OWNED BY students.id;


--
-- Name: students_to_groups; Type: TABLE; Schema: public; Owner: accelerator; Tablespace: 
--

CREATE TABLE students_to_groups (
    id integer NOT NULL,
    student_id integer,
    group_id integer
);


ALTER TABLE public.students_to_groups OWNER TO accelerator;

--
-- Name: students_to_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: accelerator
--

CREATE SEQUENCE students_to_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.students_to_groups_id_seq OWNER TO accelerator;

--
-- Name: students_to_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: accelerator
--

ALTER SEQUENCE students_to_groups_id_seq OWNED BY students_to_groups.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_assignment ALTER COLUMN id SET DEFAULT nextval('ax_assignment_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_assignment_session ALTER COLUMN id SET DEFAULT nextval('ax_assignment_session_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_assignment_student ALTER COLUMN id SET DEFAULT nextval('ax_assignment_student_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_autotest_results ALTER COLUMN id SET DEFAULT nextval('ax_autotest_results_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_color_theme ALTER COLUMN id SET DEFAULT nextval('ax_color_theme_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_message ALTER COLUMN id SET DEFAULT nextval('ax_message_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_message_attachment ALTER COLUMN id SET DEFAULT nextval('ax_message_attachment_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_message_delivery ALTER COLUMN id SET DEFAULT nextval('ax_message_delivery_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_page ALTER COLUMN id SET DEFAULT nextval('ax_page_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_page_group ALTER COLUMN id SET DEFAULT nextval('ax_page_group_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_page_prep ALTER COLUMN id SET DEFAULT nextval('ax_page_prep_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_solution_commit ALTER COLUMN id SET DEFAULT nextval('ax_solution_commit_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_solution_file ALTER COLUMN id SET DEFAULT nextval('ax_solution_file_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_student_page_info ALTER COLUMN id SET DEFAULT nextval('ax_student_page_info_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_task ALTER COLUMN id SET DEFAULT nextval('ax_task_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY ax_task_file ALTER COLUMN id SET DEFAULT nextval('ax_task_file_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY discipline ALTER COLUMN id SET DEFAULT nextval('discipline_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY groups ALTER COLUMN id SET DEFAULT nextval('groups_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY students ALTER COLUMN id SET DEFAULT nextval('students_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: accelerator
--

ALTER TABLE ONLY students_to_groups ALTER COLUMN id SET DEFAULT nextval('students_to_groups_id_seq'::regclass);


--
-- Data for Name: ax_assignment; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_assignment (id, task_id, variant_comment, start_limit, finish_limit, status_code, delay, status_text, mark, checks) FROM stdin;
84	27	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
85	27	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
86	27	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
87	28	\N	2022-11-12 01:35:37.39038+03	2023-11-12 01:35:37.39038+03	2	\N	\N	\N	\N
88	28	\N	2022-11-12 01:35:37.407087+03	2023-11-12 01:35:37.407087+03	2	\N	\N	\N	\N
89	28	\N	2022-11-12 01:35:37.423564+03	2023-11-12 01:35:37.423564+03	2	\N	\N	\N	\N
90	28	\N	2022-11-12 01:35:37.440238+03	2023-11-12 01:35:37.440238+03	2	\N	\N	\N	\N
91	29	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
92	29	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
93	29	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
94	29	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
96	31	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
97	32	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
99	31	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
100	32	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
102	31	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
103	32	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
105	31	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
106	32	\N	\N	2022-11-26 23:59:59+03	2	0	ожидает выполнения	\N	\N
83	27	\N	\N	2022-11-26 23:59:59+03	3	0	выполнено	3	\N
112	30	\N	\N	2022-12-14 23:59:59+03	2	0	ожидает выполнения	\N	\N
113	30	\N	\N	2022-12-12 23:59:59+03	2	0	ожидает выполнения	\N	\N
114	30	\N	\N	2022-12-12 23:59:59+03	2	0	ожидает выполнения	\N	\N
115	30	\N	\N	2022-12-12 23:59:59+03	2	0	ожидает выполнения	\N	\N
118	30		\N	\N	2	0	ожидает выполнения	\N	{"tools":{"valgrind":{"enabled":"false","show_to_student":"false","bin":"valgrind","arguments":"","compiler":"gcc","checks":[{"check":"errors","enabled":"true","limit":"0","autoreject":"false"},{"check":"leaks","enabled":"true","limit":"0","autoreject":"false"}]},"cppcheck":{"enabled":"false","show_to_student":"false","bin":"cppcheck","arguments":"","checks":[{"check":"error","enabled":"true","limit":"0","autoreject":"false"},{"check":"warning","enabled":"true","limit":"3","autoreject":"false"},{"check":"style","enabled":"true","limit":"3","autoreject":"false"},{"check":"performance","enabled":"true","limit":"2","autoreject":"false"},{"check":"portability","enabled":"true","limit":"0","autoreject":"false"},{"check":"information","enabled":"true","limit":"0","autoreject":"false"},{"check":"unusedFunction","enabled":"true","limit":"0","autoreject":"false"},{"check":"missingInclude","enabled":"true","limit":"0","autoreject":"false"}]},"clang-format":{"enabled":"false","show_to_student":"false","bin":"clang-format","arguments":"","check":{"level":"strict","file":"","limit":"5","autoreject":"true"}},"copydetect":{"enabled":"false","show_to_student":"false","bin":"copydetect","arguments":"","check":{"type":"with_all","limit":"80","autoreject":"false"}}}}
\.


--
-- Name: ax_assignment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_assignment_id_seq', 118, true);


--
-- Data for Name: ax_assignment_session; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_assignment_session (id, assignment_id, student_user_id, start_time, end_time) FROM stdin;
\.


--
-- Name: ax_assignment_session_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_assignment_session_id_seq', 1, false);


--
-- Data for Name: ax_assignment_student; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_assignment_student (id, assignment_id, student_user_id) FROM stdin;
90	83	-1
91	84	-2
92	85	-3
93	86	-4
94	87	-3
95	88	-4
96	89	-1
97	90	-2
98	91	-1
99	92	-2
100	93	-3
101	94	-4
103	96	-1
104	97	-1
106	99	-2
107	100	-2
109	102	-3
110	103	-3
112	105	-4
113	106	-4
119	112	-4
120	113	-1
121	114	-2
122	115	-3
157	118	-1
158	118	-2
\.


--
-- Name: ax_assignment_student_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_assignment_student_id_seq', 158, true);


--
-- Data for Name: ax_autotest_results; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_autotest_results (id, commit_id, order_num, test_name, test_timing, succeeded) FROM stdin;
\.


--
-- Name: ax_autotest_results_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_autotest_results_id_seq', 1, false);


--
-- Data for Name: ax_color_theme; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_color_theme (id, disc_id, name, bg_color, src_url, font_color, dark) FROM stdin;
0	-1	Красный	#dc3545	src/img/red.jpg	\N	\N
1	-2	Жёлтый	#ffc107	src/img/yellow.jpg	\N	\N
2	-3	Зелёный	#198754	src/img/green.jpg	\N	\N
3	-4	Синий	#1266f1	src/img/blue.jpg	\N	\N
4	-5	Фиолетовый	#6f42c1	src/img/purple.jpg	\N	\N
\.


--
-- Name: ax_color_theme_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_color_theme_id_seq', 1, false);


--
-- Data for Name: ax_message; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_message (id, assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status, visibility) FROM stdin;
67	83	2	2	-7	2022-11-14 16:00:46.412852+03	66	Задание проверено. Оценка: 3	\N	0	0
68	83	0	2	-7	2022-11-14 16:01:00.139329+03	\N	norm	\N	0	0
55	101	0	3	-3	2022-11-12 01:55:01.07672+03	\N	Куку	\N	1	0
56	101	0	3	-3	2022-11-12 01:55:15.487457+03	\N	не кукуй	\N	1	0
65	91	0	3	-1	2022-11-12 14:51:52.742298+03	\N	Не могу выполнить задание по Qt, он ищет Makefile a ne CMakeLists.txt	\N	0	0
61	83	1	3	-1	2022-11-12 14:31:24.24655+03	\N	Отправлено на проверку	66	1	0
62	83	1	3	-1	2022-11-12 14:38:42.577205+03	\N	Отправлено на проверку	67	1	0
63	83	0	3	-1	2022-11-12 14:39:09.048474+03	\N	Я сдал работу можно домой?	\N	1	0
66	83	1	3	-7	2022-11-14 16:00:31.465007+03	\N	Проверено	71	1	0
69	83	0	3	-1	2022-11-14 16:03:02.259547+03	\N	(((	\N	1	0
57	101	0	2	-9	2022-11-12 01:56:03.100115+03	\N	да	\N	1	0
59	101	0	2	-9	2022-11-12 02:00:05.755794+03	\N	Ти там нормальный?	\N	1	0
60	101	2	2	-9	2022-11-12 02:00:10.658151+03	58	Задание проверено. Оценка: 1	\N	1	0
\.


--
-- Data for Name: ax_message_attachment; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_message_attachment (id, message_id, file_name, download_url, full_text) FROM stdin;
40	58	проверить	editor.php?assignment=101&commit=64	\N
41	61	проверить	editor.php?assignment=83&commit=66	\N
42	62	проверить	editor.php?assignment=83&commit=67	\N
43	64	проверить	editor.php?assignment=95&commit=69	\N
44	66	проверенная версия	editor.php?assignment=83&commit=71	\N
\.


--
-- Name: ax_message_attachment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_message_attachment_id_seq', 44, true);


--
-- Data for Name: ax_message_delivery; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_message_delivery (id, message_id, recipient_user_id, read) FROM stdin;
37	55	-9	t
38	56	-9	t
39	58	-9	t
40	61	-9	t
41	62	-9	t
42	63	-9	t
43	64	-9	t
44	66	-7	t
45	69	-7	t
46	57	-3	t
47	59	-3	t
48	60	-3	t
\.


--
-- Name: ax_message_delivery_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_message_delivery_id_seq', 48, true);


--
-- Name: ax_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_message_id_seq', 69, true);


--
-- Data for Name: ax_page; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_page (id, disc_id, short_name, year, semester, color_theme_id, creator_id, creation_date, status) FROM stdin;
17	-2	Введение в РПО	2022	2	2	-7	2022-11-12 01:42:11+03	1
19	-7	Прог. в ЗРЛ	2022	2	1	-7	2022-11-12 01:42:22+03	1
18	-4	МиСП 1 часть (весна)	2022	1	3	-7	2022-11-12 01:42:32+03	1
\.


--
-- Data for Name: ax_page_group; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_page_group (id, page_id, group_id) FROM stdin;
61	17	-3
62	17	-4
63	19	-3
64	19	-4
65	18	-3
66	18	-4
\.


--
-- Name: ax_page_group_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_page_group_id_seq', 66, true);


--
-- Name: ax_page_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_page_id_seq', 20, true);


--
-- Data for Name: ax_page_prep; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_page_prep (id, prep_user_id, page_id) FROM stdin;
54	-7	17
55	-9	17
56	-10	17
57	-5	17
58	-9	19
59	-10	19
60	-5	19
61	-8	18
62	-9	18
63	-10	18
64	-5	18
\.


--
-- Name: ax_page_prep_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_page_prep_id_seq', 65, true);


--
-- Data for Name: ax_settings; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_settings (user_id, email, notification_type, monaco_dark) FROM stdin;
-1	vega@mirea.ru	1	t
-2	vega@mirea.ru	0	t
-3	vega@mirea.ru	0	t
-4	vega@mirea.ru	1	t
-5	a.zavjalov@gmail.com	1	t
-6	zavjalov@mirea.ru	1	t
-7	despair774@gmail.com	1	t
-8	chernousov.id@gmail.com	1	t
-9	sergekachalov@gmail.com	1	t
-10	meretukova999@mail.ru	1	t
\.


--
-- Data for Name: ax_solution_commit; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_solution_commit (id, assignment_id, session_id, student_user_id, type, autotest_results) FROM stdin;
65	83	\N	-1	0	\N
66	83	\N	-1	1	\N
67	83	\N	-1	1	\N
70	91	\N	-1	0	\N
71	83	\N	-7	0	\N
\.


--
-- Name: ax_solution_commit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_solution_commit_id_seq', 71, true);


--
-- Data for Name: ax_solution_file; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_solution_file (id, assignment_id, commit_id, type, file_name, download_url, full_text) FROM stdin;
177	91	70	11	CMakeLists.txt	\N	cmake_minimum_required(VERSION 3.16)\r\n\r\nproject(helloworld VERSION 1.0.0 LANGUAGES CXX)\r\n\r\nset(CMAKE_CXX_STANDARD 17)\r\nset(CMAKE_CXX_STANDARD_REQUIRED ON)\r\n\r\nfind_package(Qt6 REQUIRED COMPONENTS Widgets)\r\nqt_standard_project_setup()\r\n\r\nadd_executable(helloworld\r\n    main.cpp\r\n)\r\n\r\ntarget_link_libraries(helloworld PRIVATE Qt6::Widgets)\r\n\r\nset_target_properties(helloworld PROPERTIES\r\n    WIN32_EXECUTABLE ON\r\n    MACOSX_BUNDLE ON\r\n)
175	91	70	11	main.cpp	\N	#include <QApplication>\r\n#include <QWidget>\r\n\r\n\r\nint main(int argc, char** argv) {\r\n\r\n\tQApplication app(argc, argv);\r\n\r\n\treturn app.run();\r\n}\r\n
179	83	67	11	Новый файл	\N	\N
163	83	65	11	main.cpp	\N	#include <iostream>\r\n\r\n\r\nint main(int argc, char** argv) {\r\n\r\n\tstd::cout << "Hello!" << std::endl;\r\n\r\n\t// fix run in console please\r\n\t// fix 400 error\r\n\r\n\treturn 0;\r\n}\r\n
164	83	65	11	another.cpp	\N	
169	83	67	11	Makefile	\N	.PHONY: run\r\n\r\nrun: main\r\n\t./main\r\n\r\nmain: main.c\r\n\tg++ main.c -o main\r\n
181	83	67	11	main.c	\N	#include <stdio.h>\r\n\r\n\r\nint main(int argc, char** argv) {\r\n\r\n\tputs("Hello!");\r\n\r\n\t// fix run in console please\r\n\t// fix 400 er\r\n\treturn 0;\r\n}\r\n
182	83	71	11	Новый файл	\N	\N
183	83	71	11	Makefile	\N	.PHONY: run\r\n\r\nrun: main\r\n\t./main\r\n\r\nmain: main.c\r\n\tg++ main.c -o main\r\n
184	83	71	11	main.c	\N	#include <stdio.h>\r\n\r\n\r\nint main(int argc, char** argv) {\r\n\r\n\tputs("Hello!");\r\n\r\n\t// fix run in console please\r\n\t// fix 400 er\r\n\treturn 0;\r\n}\r\n
168	83	66	11	Makefile	\N	.PHONY: run\r\n\r\nrun: main\r\n\t./main\r\n\r\nmain: main.cpp\r\n\tg++ main.cpp -o main\r\n
165	83	66	11	main.cpp	\N	#include <iostream>\r\n\r\n\r\nint main(int argc, char** argv) {\r\n\r\n\tstd::cout << "Hello!" << std::endl;\r\n\r\n\t// fix run in console please\r\n\t// fix 400 er\r\n\treturn 0;\r\n}\r\n
\.


--
-- Name: ax_solution_file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_solution_file_id_seq', 184, true);


--
-- Data for Name: ax_student_page_info; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_student_page_info (id, student_user_id, page_id, total_count, passed_count, variant_comment, variant_num) FROM stdin;
\.


--
-- Name: ax_student_page_info_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_student_page_info_id_seq', 1, false);


--
-- Data for Name: ax_task; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_task (id, page_id, type, title, description, max_mark, status, checks) FROM stdin;
27	17	1	Задание 1.	Считать с клавиатуры два целых числа и символ операции (`*`, `/`, `%`, `+`, или `-`). Вычислить значение полученного выражения и вывести на экран.	5	1	\N
29	18	1	QtTask	Реализовать графический интерфейс для работы с csv-файлами с использованием раннее написанного класса CSVFile. Графический интерфейс должен включать:\r\n1.\tВиджет таблицы (QTableWidget)\r\n2.\tМеню (QMenuBar) со следующими пунктами:  \r\n2.1. "Файл" c действиями (QAction):  \r\n2.1.1. "Открыть" - вызывает файловый диалог (QFileDialog) для выбора csv файла  \r\n2.1.2. "Сохранить" - сохраняет текущее состояние таблицы в ранее открый файл  \r\n2.1.3. "Сохранить как" - сохраняет текущее состоянии таблицы в файл, заданный через файловый диалог  \r\n2.1.4. "Выход" - закрывает программу  \r\n2.2. "Таблица" с действиями:  \r\n2.2.1. "Добавить столбец" - добавляет в таблицу (QTableWiget) и CSVFile новый столбец  \r\n2.2.2. "Добавить строку" - добавляет в таблицу (QTableWiget) и CSVFile новую строку  \r\n3. Статус бар (QStatusBar) отображающий информационные сообщения, например:  \r\n"Открыт файл "students.csv""  \r\n"Файл успешно сохранен"  \r\n"Новый столбец добавлен"  \r\n4. QToolBar с вынесенными на него действиями "Открыть" и "Сохранить как" и назначенными для них иконками\r\n\r\n\r\nИзменение данных в таблице должно отслеживаться по сигналу QTableWidget::itemChanged() и иметь соответствующий слот-обработчик в классе MainWindow  \r\n\r\nДействия, не приводящие к какому-либо результату, либо вызывающие ошибку должны отображать пользователю окно с информацией об ошибке (QMessageBox::information/warning/error)\r\n\r\nЕсли при открытии файла, таблица уже существует - необходимо уточнить у пользователя, нужно ли сохранить текущую таблицу через диалоговое окно (QMessageBox::question)	5	1	\N
30	19	1	Bin2Dec	Необходимо реализовать программу, переводящую числа из двоичной записи в десятичную.\r\n##### Пример:\r\nВвод: 1100\r\nВывод: 12\r\n\r\nВвод: 0011101\r\nВывод: 29\r\n\r\nВвод: 123\r\nВывод: error	5	1	\N
31	19	1	Dec2Bin	Необходимо реализовать программу, переводящую положительные целые числа из десятичной записи в двоичную.\r\n\r\n##### Пример:\r\nВвод: 2\r\nВывод: 10\r\n\r\nВвод: 10\r\nВывод: 1010\r\n\r\nВвод: 31\r\nВывод: 11111\r\n	5	1	\N
32	19	1	PowerOfTwo	Реализовать программу, возводящую число 2 в неотрицательную степень n при помощи побитового сдвига.\r\n##### Пример:\r\nВвод: 10\r\nВывод: 1024\r\n\r\nВвод: 0\r\nВывод: 1\r\n\r\nВвод: 4\r\nВывод: 16	5	1	\N
\.


--
-- Data for Name: ax_task_file; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY ax_task_file (id, type, task_id, file_name, download_url, full_text) FROM stdin;
55	2	27	test.cpp	\N	
56	3	27	checktest.cpp	\N	
59	2	29	test.cpp	\N	
60	3	29	checktest.cpp	\N	
63	2	31	test.cpp	\N	
64	3	31	checktest.cpp	\N	
65	2	32	test.cpp	\N	
66	3	32	checktest.cpp	\N	
61	0	30	test.cpp	\N	
62	1	30	checktest.cpp	\N	
\.


--
-- Name: ax_task_file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_task_file_id_seq', 66, true);


--
-- Name: ax_task_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('ax_task_id_seq', 33, true);


--
-- Data for Name: discipline; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY discipline (id, name) FROM stdin;
-1	Базы данных
-2	Введение в разработку программного обеспечения
-3	Компьютерная графика
-4	Методы и стандарты программирования
-5	Операционные системы
-6	Компьютерные сети
-7	Программирование в задачах радиолокации
-8	Архитектура компьютеров
-9	Проектирование трансляторов
-10	Системы автоматизированного проектирования
-11	Технология создания программного продукта
\.


--
-- Name: discipline_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('discipline_id_seq', 1, false);


--
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY groups (id, name, year) FROM stdin;
-1	КМБО-02-22	2022
-2	КМБО-05-22	2022
-3	КМБО-02-21	2021
-4	КМБО-05-21	2021
-5	КМБО-02-20	2020
-6	КМБО-05-20	2020
-7	КМБО-02-19	2019
-8	КММО-02-22	2022
-9	КММО-02-21	2021
29	Преподаватели	\N
\.


--
-- Name: groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('groups_id_seq', 1, false);


--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY students (id, first_name, middle_name, last_name, login, role) FROM stdin;
-1	Иван	Иванов	Петрович	ivan	3
-2	Петр	Петров	Петрович	peter	3
-3	Семен	Семенов	Петрович	semen	3
-4	Сидор	Сидоров	Бердымухамедович	sidor	3
-5	Антон	Завьялов	Владимирович	avz	2
-6	Владимир	Путин	Молодец	admin	1
-7	Алина	Шульгина	Сергеевна	shalinash	2
-8	Игорь	Черноусов	Дмитриевич	chernousov	2
-9	Сергей	Качалов	Константинович	redbird66	2
-10	Бэлла	Меретукова	Шумафовна	bella_m	2
\.


--
-- Name: students_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('students_id_seq', 2, true);


--
-- Data for Name: students_to_groups; Type: TABLE DATA; Schema: public; Owner: accelerator
--

COPY students_to_groups (id, student_id, group_id) FROM stdin;
-1	-1	-3
-2	-2	-3
-3	-3	-4
-4	-4	-4
-5	-5	29
-6	-6	29
-7	-7	29
-8	-8	29
-9	-9	29
-10	-10	29
\.


--
-- Name: students_to_groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: accelerator
--

SELECT pg_catalog.setval('students_to_groups_id_seq', 1, false);


--
-- Name: ax_assignment_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_assignment
    ADD CONSTRAINT ax_assignment_pkey PRIMARY KEY (id);


--
-- Name: ax_assignment_session_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_assignment_session
    ADD CONSTRAINT ax_assignment_session_pkey PRIMARY KEY (id);


--
-- Name: ax_assignment_student_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_assignment_student
    ADD CONSTRAINT ax_assignment_student_pkey PRIMARY KEY (id);


--
-- Name: ax_autotest_results_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_autotest_results
    ADD CONSTRAINT ax_autotest_results_pkey PRIMARY KEY (id);


--
-- Name: ax_color_theme_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_color_theme
    ADD CONSTRAINT ax_color_theme_pkey PRIMARY KEY (id);


--
-- Name: ax_message_attachment_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_message_attachment
    ADD CONSTRAINT ax_message_attachment_pkey PRIMARY KEY (id);


--
-- Name: ax_message_delivery_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_message_delivery
    ADD CONSTRAINT ax_message_delivery_pkey PRIMARY KEY (id);


--
-- Name: ax_message_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_message
    ADD CONSTRAINT ax_message_pkey PRIMARY KEY (id);


--
-- Name: ax_page_group_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_page_group
    ADD CONSTRAINT ax_page_group_pkey PRIMARY KEY (id);


--
-- Name: ax_page_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_page
    ADD CONSTRAINT ax_page_pkey PRIMARY KEY (id);


--
-- Name: ax_page_prep_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_page_prep
    ADD CONSTRAINT ax_page_prep_pkey PRIMARY KEY (id);


--
-- Name: ax_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_settings
    ADD CONSTRAINT ax_settings_pkey PRIMARY KEY (user_id);


--
-- Name: ax_solution_commit_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_solution_commit
    ADD CONSTRAINT ax_solution_commit_pkey PRIMARY KEY (id);


--
-- Name: ax_solution_file_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_solution_file
    ADD CONSTRAINT ax_solution_file_pkey PRIMARY KEY (id);


--
-- Name: ax_student_page_info_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_student_page_info
    ADD CONSTRAINT ax_student_page_info_pkey PRIMARY KEY (id);


--
-- Name: ax_task_file_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_task_file
    ADD CONSTRAINT ax_task_file_pkey PRIMARY KEY (id);


--
-- Name: ax_task_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY ax_task
    ADD CONSTRAINT ax_task_pkey PRIMARY KEY (id);


--
-- Name: discipline_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY discipline
    ADD CONSTRAINT discipline_pkey PRIMARY KEY (id);


--
-- Name: groups_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: students_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT students_pkey PRIMARY KEY (id);


--
-- Name: students_to_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: accelerator; Tablespace: 
--

ALTER TABLE ONLY students_to_groups
    ADD CONSTRAINT students_to_groups_pkey PRIMARY KEY (id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

