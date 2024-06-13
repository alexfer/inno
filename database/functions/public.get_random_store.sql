CREATE OR REPLACE FUNCTION public.get_random_store()
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results  JSON;
    products JSON;
BEGIN
    SELECT json_build_object(
                   'id', s.id,
                   'currency', s.currency,
                   'name', s.name,
                   'slug', s.slug,
                   'description', s.description,
                   'picture', a.name,
                   'coupon', (SELECT json_build_object(
                                             'id', sc.id,
                                             'type', sc.type,
                                             'price', sc.price,
                                             'discount', sc.discount
                                     )
                              FROM store_coupon sc
                              WHERE sc.store_id = s.id
                                AND sc.event = 1
                                AND extract(epoch from current_timestamp)::integer > extract(epoch from sc.created_at)::integer
                                AND extract(epoch from current_timestamp)::integer < extract(epoch from sc.expired_at)::integer
                              LIMIT 1),
                   'payments', json_agg(json_build_object(
                    'id', spg.id,
                    'icon', spg.icon,
                    'text', spg.handler_text,
                    'name', spg.name,
                    'summary', spg.summary
                                        ))
           )
    INTO results
    FROM store s
             LEFT JOIN attach a on a.id = s.attach_id
             LEFT JOIN public.store_payment_gateway_store spgs on s.id = spgs.store_id
             LEFT JOIN public.store_payment_gateway spg on spg.id = spgs.gateway_id
    WHERE s.deleted_at IS NULL
    GROUP BY s.id, a.id
    ORDER BY RANDOM()
    LIMIT 1;

    SELECT json_agg(json_build_object(
            'id', p.id,
            'slug', p.slug,
            'name', p.name,
            'store_id', p.store_id,
            'quantity', p.quantity,
            'short_name', p.short_name,
            'cost', p.cost,
            'fee', p.fee,
            'discount', p.discount,
            'payments', (SELECT json_agg(json_build_object(
                    'name', g.name,
                    'icon', g.icon
                                         ))
                         FROM store_payment_gateway_store spg
                                  LEFT JOIN store_payment_gateway g ON g.id = spg.gateway_id
                         WHERE spg.store_id = p.store_id),
            'currency', (SELECT s.currency FROM store s WHERE s.id = p.store_id LIMIT 1),
            'attachment', (SELECT a.name
                           FROM store_product_attach spa
                                    LEFT JOIN attach a on a.id = spa.attach_id
                           WHERE spa.product_id = p.id
                           LIMIT 1)
                    ))
    FROM (SELECT sp.id,
                 sp.store_id,
                 sp.slug,
                 sp.quantity,
                 sp.name,
                 sp.short_name,
                 sp.cost,
                 sp.fee,
                 sp.discount
          FROM store_product sp
          WHERE sp.deleted_at IS NULL
          ORDER BY RANDOM()
          LIMIT 3) AS p
    INTO products;

    RETURN
        json_build_object(
                'store', results,
                'products', products
        );
END;
$function$