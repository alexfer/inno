CREATE OR REPLACE FUNCTION public.get_dashboard_entries(user_id integer DEFAULT NULL::integer, status character varying DEFAULT NULL::character varying, type character varying DEFAULT NULL::character varying, start integer DEFAULT 0, row_count integer DEFAULT 25)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results jsonb;
    rows    int = 0;
BEGIN
    SELECT json_agg(json_build_object(
            'id', e.id,
            'title', ed.title,
            'created', e.created_at,
            'status', e.status,
            'deleted', e.deleted_at,
            'locked', e.locked_to,
            'slug', e.slug,
            'uid', u.id,
            'author', ud.first_name || ' ' || ud.last_name
                    ))
    FROM entry e
             JOIN entry_details ed ON e.id = ed.entry_id
             JOIN "user" u on u.id = e.user_id
             JOIN user_details ud ON u.id = ud.user_id
    WHERE CASE
              WHEN get_dashboard_entries.user_id IS NOT NULL THEN e.user_id = get_dashboard_entries.user_id
              ELSE e.user_id > 0 END
      AND e.type = get_dashboard_entries.type
      AND e.status = get_dashboard_entries.status
    GROUP BY e.id
    ORDER BY e.id DESC
    OFFSET start LIMIT row_count
    INTO results;

    SELECT count(e2.id)
    FROM entry e2
    WHERE e2.type = get_dashboard_entries.type
      AND e2.status = get_dashboard_entries.status
    INTO rows;

    RETURN json_build_object(
            'result', results,
            'rows', rows
           );
END;
$function$