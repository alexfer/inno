CREATE OR REPLACE FUNCTION public.get_dashboard_customers(ids jsonb, start integer DEFAULT 0, rows_count integer DEFAULT 12)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results jsonb;
BEGIN
    WITH customers AS (SELECT DISTINCT jsonb_build_object(
                                               'id', c.id,
                                               'country', c.country,
                                               'created', c.created_at,
                                               'full_name', INITCAP(c.first_name || ' ' || c.last_name),
                                               'orders', (SELECT COUNT(co.id)
                                                          FROM store_customer_orders co
                                                          WHERE co.customer_id = c.id
                                                            AND co.id IN (SELECT jsonb_array_elements_text(ids)::INT))
                                       ) AS customer
                       FROM store_customer c
                                JOIN store_customer_orders sco ON sco.customer_id = c.id AND sco.id IN (SELECT jsonb_array_elements_text(ids)::INT)
                       GROUP BY c.id, c.email
                       OFFSET start LIMIT rows_count)

    SELECT json_agg(customer ORDER BY customer ->> 'id' DESC)
    INTO results
    FROM customers;

    RETURN json_build_object(
            'result', results
           );
END;
$function$