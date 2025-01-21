CREATE OR REPLACE FUNCTION public.get_customer_orders(customer_id integer, start integer DEFAULT 0,
                                                      row_count integer DEFAULT 25)
    RETURNS json
    LANGUAGE plpgsql
AS
$function$
DECLARE
    orders     JSON;
    rows_count INTEGER;
BEGIN
    SELECT COUNT(*)
    FROM store_customer_orders sco
    WHERE sco.customer_id = get_customer_orders.customer_id
    INTO rows_count;

    SELECT json_agg(json_build_object(
                            'id', o.id,
                            'store', (SELECT json_build_object(
                                                     'id', s.id,
                                                     'name', s.name,
                                                     'currency', s.currency,
                                                     'slug', s.slug
                                             )
                                      FROM store s
                                      WHERE s.id = o.store_id
                                      LIMIT 1),
                            'number', o.number,
                            'created', o.created_at,
                            'completed', o.completed_at,
                            'status', o.status,
                            'tax', o.tax,
                            'total_quantity',
                            (SELECT SUM(op.quantity) FROM store_orders_product op WHERE op.orders_id = o.id LIMIT 1),
                            'coupon', (SELECT json_build_object(
                                                      'id', scu.id,
                                                      'price', sc.price,
                                                      'discount', sc.discount::integer,
                                                      'total_discount', (o.total -
                                                                         ((o.total * sc.discount::integer) - sc.discount::integer) /
                                                                         100),
                                                      'total_price', (o.total - sc.price)
                                              )
                                       FROM store_coupon_usage scu
                                                LEFT JOIN public.store_coupon sc on sc.id = scu.coupon_id
                                       WHERE scu.relation = co.orders_id
                                       LIMIT 1),
                            'invoice', (json_build_object(
                    'id', si.id,
                    'number', si.number,
                    'tax', si.tax,
                    'amount', si.amount,
                    'created', si.created_at,
                    'paid', si.paid_at,
                    'carrier', (SELECT json_build_object(
                                               'id', sc.id,
                                               'name', sc.name
                                       )
                                FROM store_carrier sc
                                WHERE sc.id = si.carrier_id
                                LIMIT 1),
                    'payment_gateway', (SELECT json_build_object(
                                                       'id', spg.id,
                                                       'name', spg.name
                                               )
                                        FROM store_payment_gateway spg
                                        WHERE spg.id = si.payment_gateway_id
                                        LIMIT 1)
                                        )),
                            'status', o.status,
                            'total', o.total,
                            'products', (SELECT json_agg(json_build_object(
                    'id', sop.id,
                    'quantity', sop.quantity,
                    'size', sop.size::json -> 'size',
                    'size_title', sop.size::json -> 'size',
                    'color', sop.color::json -> 'extra',
                    'color_title', sop.color::json -> 'color',
                    'product', (SELECT json_build_object(
                                               'id', p.id,
                                               'fee', p.fee,
                                               'cost', p.cost,
                                               'slug', p.slug,
                                               'amount', SUM(p.cost + p.fee) * sop.quantity,
                                               'reduce', (SELECT json_build_object(
                                                                         'value', spd.value,
                                                                         'unit', spd.unit
                                                                 )
                                                          FROM store_product_discount spd
                                                          WHERE spd.product_id = p.id
                                                          LIMIT 1),
                                               'short_name', p.short_name,
                                               'name', p.name,
                                               'coupon', (SELECT json_build_object(
                                                                         'id', c.id,
                                                                         'price', c.price,
                                                                         'discount', c.discount::integer,
                                                                         'total_discount', (
                                                                             p.cost -
                                                                             (((p.cost + p.fee) * c.discount::integer) - c.discount::integer) /
                                                                             100
                                                                             ),
                                                                         'total_price', (p.cost - c.price)
                                                                 )
                                                          FROM store_coupon_store_product scsp
                                                                   LEFT JOIN public.store_coupon c on c.id = scsp.store_coupon_id
                                                          WHERE scsp.store_product_id = p.id
                                                          LIMIT 1)
                                       )
                                FROM store_product p
                                WHERE p.id = sop.product_id
                                GROUP BY p.id
                                LIMIT 1)
                                                         ))
                                         FROM store_orders_product sop
                                         WHERE sop.orders_id = co.orders_id
                                         LIMIT 1)
                    ) ORDER BY co.id DESC)
    INTO orders
    FROM store_customer_orders co
             JOIN store_orders o ON o.id = co.orders_id
             LEFT JOIN store_invoice si on co.orders_id = si.orders_id
    WHERE co.customer_id = get_customer_orders.customer_id
    OFFSET get_customer_orders.start LIMIT get_customer_orders.row_count;

    RETURN json_build_object(
            'orders', orders,
            'rows_count', rows_count
           );
END;
$function$