CREATE OR REPLACE FUNCTION public.get_customer_messages(customer_id integer, "offset" integer DEFAULT 0,
                                                        "limit" integer DEFAULT 25)
    RETURNS json
    LANGUAGE plpgsql
AS
$function$
DECLARE
    get_customer_messages JSON;
    rows_count            INT;
BEGIN
    SELECT json_agg(json_build_object(
            'id', sm.id,
            'created', sm.created_at,
            'deleted', sm.deleted_at,
            'priority', INITCAP(sm.priority),
            'read', (SELECT sm2.read FROM store_message sm2 WHERE sm2.owner_id IS NULL ORDER BY sm2.id DESC LIMIT 1),
            'answers', (SELECT COUNT(*) FROM store_message m WHERE m.parent_id = sm.id),
            'store', json_build_object(
                    'id', s.id,
                    'name', s.name,
                    'slug', s.slug
                      ),
            'product', (CASE
                            WHEN sp.id IS NULL THEN NULL
                            ELSE json_build_object(
                                    'id', sp.id,
                                    'slug', sp.slug,
                                    'short_name', sp.short_name
                                 ) END),
            'order', (CASE
                          WHEN so.id IS NULL THEN NULL
                          ELSE json_build_object(
                                  'id', so.id,
                                  'number', so.number
                               ) END)
                    ))
    INTO get_customer_messages
    FROM store_message sm
             LEFT JOIN store_product sp ON sp.id = sm.product_id
             LEFT JOIN store_orders so ON so.id = sm.orders_id
             LEFT JOIN store s ON s.id = sm.store_id
    WHERE sm.customer_id = get_customer_messages.customer_id
      AND sm.parent_id IS NULL
    ORDER BY MAX(sm.id) DESC
    OFFSET get_customer_messages.offset LIMIT get_customer_messages.limit;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_message sm
    WHERE sm.customer_id = get_customer_messages.customer_id;

    RETURN json_build_object(
            'data', get_customer_messages,
            'rows_count', rows_count
           );

END;
$function$