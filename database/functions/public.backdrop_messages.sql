CREATE OR REPLACE FUNCTION public.backdrop_messages(ids jsonb, start integer DEFAULT 0, row_count integer DEFAULT 25)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results jsonb;
BEGIN
    WITH counter AS (SELECT COUNT(m1.id)
                     FROM store_message m1
                     WHERE m1.store_id IN (SELECT jsonb_array_elements_text(ids)::INT))

    SELECT json_agg(json_build_object(
            'messages_count', (SELECT * FROM counter),
            'id', m.id,
            'created', m.created_at,
            'priority', m.priority,
            'store', m.store_id,
            'message', m.message,
            'customer', initcap(c.first_name || ' ' || c.last_name)
                    ))
    FROM store_message m
             LEFT JOIN store_customer c ON c.id = m.customer_id
    WHERE m.parent_id IS NULL
      AND m.store_id IN (SELECT jsonb_array_elements_text(ids)::INT)
    GROUP BY m.id
    OFFSET start LIMIT row_count
    INTO results;

    RETURN json_build_object(
            'result', results
           );
END;
$function$