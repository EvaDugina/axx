CREATE OR REPLACE FUNCTION get_semester(year integer, semester integer)
  RETURNS text AS
$BODY$
BEGIN
    IF (semester%2 = 1) THEN
	RETURN (year-1)::text || '/' || year || ' весна';
    ELSIF (semester%2 = 0) THEN
        RETURN year || '/' || (year+1)::text || ' осень';
    END IF;

    RETURN '';
  
END$BODY$
  LANGUAGE plpgsql VOLATILE;
ALTER FUNCTION get_semester(integer, integer)
  OWNER TO accelerator;