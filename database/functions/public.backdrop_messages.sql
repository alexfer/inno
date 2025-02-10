CREATE OR REPLACE FUNCTION public.backdrop_messages(ids jsonb, start integer DEFAULT 0, row_count integer DEFAULT 25)
    RETURNS jsonb
    LANGUAGE plpgsql
AS
$function$
DECLARE
    results jsonb;
    rows    int = 0;
BEGIN
    SELECT json_agg(json_build_object(
            'id', m.id,
            'created', m.created_at,
            'priority', m.priority,
            'product', sp.short_name,
            'product_slug', sp.slug,
            'order', so.number,
            'store', m.store_id,
            'message', m.message,
            'customer', initcap(c.first_name || ' ' || c.last_name)
                    ))
    FROM store_message m
             LEFT JOIN store_customer c ON c.id = m.customer_id
             LEFT JOIN store_orders so ON m.orders_id = so.id
             LEFT JOIN store_product sp ON m.product_id = sp.id
    WHERE m.store_id IN (SELECT jsonb_array_elements_text(ids)::INT)
      AND m.parent_id IS NULL
    ORDER BY MAX(m.id) DESC
    OFFSET start LIMIT row_count
    INTO results;

    SELECT COUNT(m2.id)
    FROM store_message m2
             LEFT JOIN store_customer c1 ON c1.id = m2.customer_id
    WHERE m2.parent_id IS NULL
      AND m2.store_id IN (SELECT jsonb_array_elements_text(ids)::INT)
    INTO rows;

    RETURN json_build_object(
            'rows', rows,
            'result', results
           );
END;
$function$