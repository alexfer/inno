CREATE OR REPLACE FUNCTION public.customer_counters(customer_id integer)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    msg_counter      int = 0;
    wishlist_counter int = 0;
BEGIN

    WITH customer AS (SELECT id FROM store_customer WHERE member_id = customer_id)

    SELECT COUNT(*)
    FROM store_wishlist w
    WHERE w.customer_id = (SELECT c2.id FROM customer c2)
    INTO wishlist_counter;

    SELECT COUNT(m.id)
    FROM store_customer c
             LEFT JOIN store_message m ON c.id = m.customer_id
    WHERE c.member_id = customer_counters.customer_id
      AND m.owner_id IS NOT NULL
      AND m.read = false
    INTO msg_counter;

    return json_build_object(
            'messages', msg_counter,
            'wishlist', wishlist_counter
           );
END;
$function$