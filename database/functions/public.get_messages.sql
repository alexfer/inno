CREATE OR REPLACE FUNCTION public.get_messages(store_id integer, priority text, start integer DEFAULT 0,
                                               row_count integer DEFAULT 25)
    RETURNS json
    LANGUAGE plpgsql
AS
$function$
DECLARE
    get_messages JSON;
    rows_count   INT;
BEGIN
    SELECT json_agg(json_build_object(
            'id', sm.id,
            'store', get_messages.store_id,
            'created', sm.created_at,
            'priority', INITCAP(sm.priority),
            'read', (SELECT sm2.read FROM store_message sm2 WHERE sm2.owner_id IS NOT NULL ORDER BY sm2.id DESC LIMIT 1),
            'answers', (SELECT COUNT(*) FROM store_message mc WHERE mc.parent_id = sm.id),
            'customer', json_build_object(
                    'id', sc.id,
                    'phone', sc.phone,
                    'full_name', CONCAT_WS(' ', sc.first_name, sc.last_name)
                        ),
            'product', (CASE
                            WHEN sp.id IS NULL THEN NULL
                            ELSE json_build_object(
                                    'id', sp.id,
                                    'slug', sp.slug,
                                    'short_name', sp.short_name,
                                    'picture', a.name,
                                    'path', a.path
                                 ) END),
            'order', (CASE
                          WHEN mo.id IS NULL THEN NULL
                          ELSE json_build_object(
                                  'id', mo.id,
                                  'number', mo.number
                               ) END)
                    ))
    INTO get_messages
    FROM store_message sm
             LEFT JOIN store_product sp ON sp.id = sm.product_id
             LEFT JOIN store_product_attach spa ON sp.id = spa.product_id
             LEFT JOIN attach a ON a.id = spa.attach_id
             LEFT JOIN store_orders mo ON mo.id = sm.orders_id
             LEFT JOIN store_customer sc ON sc.id = sm.customer_id
    WHERE sm.store_id = get_messages.store_id
      AND sm.priority = get_messages.priority
      AND sm.parent_id IS NULL
    ORDER BY MAX(sm.id) DESC
    OFFSET start LIMIT row_count;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_message sm
    WHERE sm.store_id = get_messages.store_id
      AND sm.priority = get_messages.priority;

    RETURN json_build_object(
            'data', get_messages,
            'rows_count', rows_count
           );

END;
$function$