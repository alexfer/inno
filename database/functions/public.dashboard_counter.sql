CREATE OR REPLACE FUNCTION public.dashboard_message_counter(user_id integer)
 RETURNS integer
 LANGUAGE plpgsql
AS $function$
DECLARE
    counter int = 0;
BEGIN
    WITH ids AS (SELECT id
                 FROM store
                 WHERE owner_id = user_id)

    SELECT COUNT(m.id)
    FROM store_message m
    WHERE m.store_id IN (SELECT ids.id FROM ids) AND m.read = false AND m.owner_id IS NULL
    INTO counter;

    RETURN counter;
END;
$function$