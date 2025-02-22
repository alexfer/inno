CREATE
    OR REPLACE FUNCTION public.backdrop_fetch_stores(start integer DEFAULT 0, row_count integer DEFAULT 10)
    RETURNS json
    LANGUAGE plpgsql
AS
$function$
DECLARE
    results JSON;
BEGIN
    WITH stores AS (SELECT DISTINCT jsonb_build_object(
                                            'id', s.id,
                                            'name', s.name,
                                            'products',
                                            (SELECT COUNT(p.id) FROM store_product p WHERE p.store_id = s.id),
                                            'owner', (SELECT json_build_object(
                                                                     'email', u.email,
                                                                     'roles', u.roles
                                                             )
                                                      FROM "user" u
                                                      WHERE u.id = s.owner_id),
                                            'created', s.created_at,
                                            'deleted', s.deleted_at,
                                            'locked', s.locked_to
                                    ) AS store
                    FROM store s
                    OFFSET start LIMIT row_count)
    SELECT json_agg(store ORDER BY store ->> 'id' DESC)
    INTO results
    FROM stores;

    RETURN json_build_object(
            'result', results,
            'rows', (SELECT COUNT(*)
                     FROM store)
           );
END;
$function$