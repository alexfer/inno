CREATE OR REPLACE FUNCTION public.get_store(slug character varying, customer_id integer DEFAULT 0, start integer DEFAULT 0, row_count integer DEFAULT 25)
    RETURNS jsonb
    LANGUAGE plpgsql
AS $function$
DECLARE
    results JSON;
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
                                              'fee', p.fee,
                                              'quantity', p.quantity,
                                              'short_name', p.short_name,
                                              'name', p.name,
                                              'category_name', c.name,
                                              'category_slug', c.slug,
                                              'parent_category_name', cc.name,
                                              'parent_category_slug', cc.slug,
                                              'attachment', (SELECT json_build_object(
                                                                            'name', a.name,
                                                                            'path', a.path
                                                                    )
                                                             FROM store_product_attach spa
                                                                      LEFT JOIN attach a on a.id = spa.attach_id
                                                             WHERE spa.product_id = p.id
                                                             ORDER BY spa.id DESC
                                                             LIMIT 1)
                                      ) AS product
                      FROM store s
                               LEFT JOIN store_product p ON s.id = p.store_id
                               JOIN store_category_product cp ON p.id = cp.product_id
                               JOIN store_category c ON c.id = cp.category_id
                               JOIN store_category cc ON c.parent_id = cc.id
                      WHERE s.slug = get_store.slug
                      OFFSET get_store.start LIMIT get_store.row_count),
         coupon AS (SELECT sc2.started_at AS started, sc2.expired_at AS expired
                    FROM store s
                             LEFT JOIN store_coupon sc2 ON s.id = sc2.store_id
                    WHERE s.slug = get_store.slug
                    GROUP BY sc2.id
                    LIMIT 1)
    SELECT json_build_object(
                   'id', s.id,
                   'name', s.name,
                   'cc', s.cc::json,
                   'slug', s.slug,
                   'description', s.description,
                   'currency', s.currency,
                   'phone', s.phone,
                   'email', s.email,
                   'website', s.website,
                   'address', s.address,
                   'picture', (select a.name FROM attach a WHERE a.id = s.attach_id LIMIT 1),
                   'promo', json_build_object(' expired ', coupon.expired, ' started ', coupon.started),
                   'products_count', (SELECT COUNT(p.id)
                                      FROM store_product p
                                      WHERE p.store_id = s.id),
                   'socials', (SELECT json_agg(ss.*) as name
                               FROM store_social ss
                               WHERE ss.store_id = s.id
                                 AND ss.is_active = true),
                   'products', json_agg(product ORDER BY products DESC)
           )
    INTO results
    FROM store s
             CROSS JOIN products,
         coupon
    WHERE s.slug = get_store.slug
    GROUP BY s.id, coupon.started, coupon.expired;

    RETURN json_build_object(
            'result', results
           );
END;

$function$