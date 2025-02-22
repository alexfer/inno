CREATE OR REPLACE FUNCTION public.backdrop_products(store_id integer, query text DEFAULT NULL::text, start integer DEFAULT 0, row_count integer DEFAULT 25)
    RETURNS json
    LANGUAGE plpgsql
AS $function$
DECLARE
    results    JSON;
    rows_count INTEGER;
BEGIN
    WITH products AS (SELECT DISTINCT jsonb_build_object(
                                              'id', p.id,
                                              'store', (SELECT json_build_object(
                                                                       'id', s.id,
                                                                       'deleted', s.deleted_at
                                                               )
                                                        FROM store s
                                                        WHERE s.id = p.store_id
                                                        LIMIT 1),
                                              'name', p.name,
                                              'short_name', p.short_name,
                                              'cost', p.cost,
                                              'quantity', p.quantity,
                                              'reduce', (SELECT json_build_object(
                                                                        'value', spd.value,
                                                                        'unit', spd.unit
                                                                )
                                                         FROM store_product_discount spd
                                                         WHERE spd.product_id = p.id
                                                         LIMIT 1),
                                              'fee', p.fee,
                                              'created', p.created_at,
                                              'deleted', p.deleted_at,
                                              'coupons', (SELECT json_agg(json_build_object(
                    'coupon', sc.id,
                    'product', scsp.store_product_id
                                                                          ))
                                                          FROM store_coupon sc
                                                                   LEFT JOIN store_coupon_store_product scsp on sc.id = scsp.store_coupon_id
                                                          WHERE sc.store_id = p.store_id
                                                            AND sc.type = 'product')
                                      ) AS product
                      FROM store_product p
                      WHERE LOWER(p.short_name) LIKE LOWER('%' || backdrop_products.query::text || '%')
                        AND p.store_id = backdrop_products.store_id
                      ORDER BY product DESC
                      OFFSET start LIMIT row_count)
    SELECT json_agg(product)
    INTO results
    FROM products;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_product p
    WHERE LOWER(p.short_name) LIKE LOWER('%' || backdrop_products.query::text || '%')
      AND p.store_id = backdrop_products.store_id;

    RETURN json_build_object(
            'result', results,
            'query', query::text,
            'store', backdrop_products.store_id,
            'rows', rows_count
           );
END ;
$function$