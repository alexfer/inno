CREATE OR REPLACE FUNCTION public.get_dashboard_stores(start integer DEFAULT 0, rows_count integer DEFAULT 12, owner integer DEFAULT NULL::integer, store_slug character varying DEFAULT NULL::character varying)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results JSON;
BEGIN
    WITH stores AS (SELECT DISTINCT jsonb_build_object(
                                            'id', s.id,
                                            'name', s.name,
                                            'slug', s.slug,
                                            'country', s.country,
                                            'currency', s.currency,
                                            'orders', (SELECT json_agg(json_build_object(
                                            'id', o.id,
                                            'number', o.number,
                                            'created', o.created_at,
                                            'status', o.status,
                                            'total', o.total
                                                                       )) FROM store_orders o
                                                       WHERE o.store_id = s.id AND o.status = 'confirmed'
                                                       OFFSET start LIMIT rows_count),
                                            'products_count',
                                            (SELECT COUNT(p.id) FROM store_product p WHERE p.store_id = s.id),
                                            'products', (SELECT json_agg(json_build_object(
                                                            'id', sp.id,
                                                            'deleted', sp.deleted_at,
                                                            'created', sp.created_at,
                                                            'name', sp.short_name,
                                                            'slug', sp.slug,
                                                            'cost', sp.cost,
                                                            'fee', sp.fee,
                                                            'quantity', sp.quantity
                                                                         ))
                                                         FROM store_product sp
                                                         WHERE sp.store_id = s.id
                                                         OFFSET start LIMIT rows_count
                                            ),
                                            'owner', (SELECT json_build_object(
                                                                     'email', u.email,
                                                                     'roles', u.roles,
                                                                     'full_name', ud.first_name || ' ' || ud.last_name
                                                             )
                                                      FROM "user" u
                                                               JOIN user_details ud ON u.id = ud.user_id
                                                      WHERE u.id = s.owner_id
                                                      LIMIT 1),
                                            'created', s.created_at,
                                            'deleted', s.deleted_at,
                                            'locked', s.locked_to
                                    ) AS store
                    FROM store s
                    WHERE CASE WHEN get_dashboard_stores.owner IS NOT NULL
                                   THEN s.owner_id = get_dashboard_stores.owner
                               ELSE s.owner_id != 0 AND
                                    CASE WHEN get_dashboard_stores.store_slug IS NOT NULL
                                             THEN s.slug = get_dashboard_stores.store_slug ELSE s.slug LIKE '%' || '%' END
                              END
                    OFFSET start LIMIT rows_count)
    SELECT json_agg(store ORDER BY store ->> 'id' DESC)
    INTO results
    FROM stores;

    RETURN json_build_object(
            'result', results,
            'options', (SELECT json_agg(json_build_object(
                    'id', s2.id,
                    'slug', s2.slug,
                    'name', s2.name
                                        )) FROM store s2 OFFSET start LIMIT rows_count),
            'rows', (SELECT COUNT(*)
                     FROM store s WHERE CASE WHEN get_dashboard_stores.owner IS NOT NULL
                                                 THEN s.owner_id = get_dashboard_stores.owner
                                             ELSE s.owner_id != 0 AND
                                                  CASE WHEN get_dashboard_stores.store_slug IS NOT NULL
                                                           THEN s.slug = get_dashboard_stores.store_slug ELSE s.slug LIKE '%' || '%' END
                                            END)
           );
END;
$function$