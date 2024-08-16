CREATE
OR REPLACE FUNCTION public.get_random_products(row_count integer DEFAULT 18)
 RETURNS json
 LANGUAGE plpgsql
AS $function$DECLARE
    get_products JSON;
BEGIN
WITH products AS (SELECT DISTINCT jsonb_build_object(
                                          'id', p.id,
                                          'slug', p.slug,
                                          'cost', p.cost,
                                          'reduce', (SELECT json_build_object(
                                                                    'value', spd.value,
                                                                    'unit', spd.unit
                                                            )
                                                     FROM store_product_discount spd
                                                     WHERE spd.product_id = p.id
                                              LIMIT 1),
                                          'name', p.name,
                                          'fee', p.fee,
                                          'short_name', p.short_name,
                                          'quantity', p.quantity,
                                          'attach_name', a.name,
                                          'category_name', c.name,
                                          'category_slug', c.slug,
                                          'parent_category_name', cc.name,
                                          'parent_category_slug', cc.slug,
                                          'store', m.name,
                                          'store_phone', m.phone,
                                          'store_id', m.id,
                                          'currency', m.currency,
                                          'store_slug', m.slug
                                  ) AS product
                  FROM store_product p
                           JOIN store_category_product cp ON p.id = cp.product_id
                           JOIN store_category c ON c.id = cp.category_id
                           JOIN store_category cc ON c.parent_id = cc.id
                           LEFT JOIN (SELECT DISTINCT
                                      ON (pa.product_id) pa.product_id, a.name
                                      FROM store_product_attach pa
                                          LEFT JOIN attach a
                                      ON pa.attach_id = a.id
                                      ORDER BY pa.product_id) a ON a.product_id = p.id
                           LEFT JOIN store_wishlist w ON w.product_id = p.id
                           JOIN store m ON m.id = p.store_id
                  WHERE p.deleted_at IS NULL
    LIMIT row_count)
SELECT json_agg(product ORDER BY RANDOM())
INTO get_products
FROM products;

RETURN json_build_object(
        'data', get_products
       );
END;$function$