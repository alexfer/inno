CREATE OR REPLACE FUNCTION public.get_order_summary(session character varying DEFAULT NULL::character varying, customer_id integer DEFAULT NULL::integer, number character varying DEFAULT NULL::character varying)
    RETURNS json
    LANGUAGE plpgsql
AS $function$
DECLARE
    summary JSON;
BEGIN
    SELECT json_agg(
                   json_build_object(
                           'id', o.id,
                           'session', o.session,
                           'number', o.number,
                           'store', (SELECT json_build_object(
                                                    'id', s.id,
                                                    'name', s.name,
                                                    'tax', s.tax,
                                                    'currency', s.currency,
                                                    'slug', s.slug,
                                                    'cc', s.cc::json
                                            )
                                     FROM store s
                                     WHERE s.id = o.store_id
                                     LIMIT 1),
                           'status', o.status,
                           'total', o.total,
                           'tax', o.tax,
                           'qty',
                           (SELECT SUM(sop.quantity)
                            FROM store_orders_product sop
                            WHERE sop.orders_id = o.id
                            LIMIT 1),
                           'products', (SELECT json_agg(json_build_object(
                           'id', sop.id,
                           'size', sop.size::json -> 'size',
                           'size_title', sop.size::json -> 'size',
                           'color', sop.color::json -> 'extra',
                           'color_title', sop.color::json -> 'color',
                           'quantity', sop.quantity,
                           'coupon', (SELECT json_build_object(
                                                     'id', sc.id,
                                                     'discount', sc.discount::integer,
                                                     'price', sc.price,
                                                     'started', sc.started_at,
                                                     'expired', sc.expired_at,
                                                     'valid', (sc.started_at::timestamp < CURRENT_TIMESTAMP AND sc.expired_at::timestamp > CURRENT_TIMESTAMP),
                                                     'hasUsed', (SELECT COUNT(scu.id)
                                                                 FROM store_coupon_usage scu
                                                                 WHERE scu.customer_id = get_order_summary.customer_id
                                                                   AND scu.coupon_id = sc.id AND scu.relation = sop.product_id
                                                                 LIMIT 1)
                                             )
                                      FROM store_coupon_store_product scsp
                                               LEFT JOIN store_coupon sc ON sc.id = scsp.store_coupon_id AND sc.type = 'product'
                                      WHERE scsp.store_product_id = sop.product_id),
                           'product', (SELECT json_build_object(
                                                      'id', p.id,
                                                      'name', p.name,
                                                      'short_name', p.short_name,
                                                      'cost', p.cost,
                                                      'reduce', (SELECT json_build_object(
                                                                                'value', spd.value,
                                                                                'unit', spd.unit
                                                                        )
                                                                 FROM store_product_discount spd
                                                                 WHERE spd.product_id = p.id
                                                                 LIMIT 1),
                                                      'sku', p.sku,
                                                      'fee', p.fee,
                                                      'slug', p.slug,
                                                      'quantity', p.quantity,
                                                      'attachment', (SELECT json_build_object(
                                                                                    'name', a.name,
                                                                                    'path', a.path
                                                                            )
                                                                     FROM store_product_attach spa
                                                                              LEFT JOIN attach a ON a.id = spa.attach_id
                                                                     WHERE spa.product_id = p.id
                                                                     LIMIT 1)
                                              )
                                       FROM store_product p
                                       WHERE p.id = sop.product_id)
                                                        ))
                                        FROM store_orders_product sop
                                        WHERE sop.orders_id = o.id)
                   )
           )
    INTO summary
    FROM store_orders o
    WHERE o.session = get_order_summary.session;

    RETURN json_build_object(
            'summary', summary
           );
END;
$function$