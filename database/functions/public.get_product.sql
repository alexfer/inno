CREATE OR REPLACE FUNCTION public.get_product(slug character varying)
    RETURNS json
    LANGUAGE plpgsql
AS $function$
DECLARE
    get_product JSON;
BEGIN
    WITH attachments AS (
        SELECT DISTINCT jsonb_build_object(
                                'id', a.id,
                                'name', a.name,
                                'path', a.path
                        ) AS attachment
        FROM store_product p
                 LEFT JOIN store_product_attach spa ON spa.product_id = p.id
                 LEFT JOIN attach a ON a.id = spa.attach_id
        WHERE p.slug = get_product.slug
    )
    SELECT json_build_object(
                   'id', p.id,
                   'slug', p.slug,
                   'name', p.name,
                   'code', UPPER(p.slug),
                   'short_name', p.short_name,
                   'description', p.description,
                   'cost', p.cost,
                   'fee', p.fee,
                   'reduce', (SELECT json_build_object(
                                             'value', spd.value,
                                             'unit', spd.unit
                                     )
                              FROM store_product_discount spd
                              WHERE spd.product_id = p.id
                              LIMIT 1),
                   'sku', p.sku,
                   'quantity', p.quantity,
                   'pckg', p.pckg_quantity,
                   'attributes', (
                       SELECT json_agg(json_build_object(
                               'id', pa.id,
                               'name', pa.name,
                               'in_front', pa.in_front
                                       ))
                       FROM store_product_attribute pa
                       WHERE pa.product_id = p.id
                   ),
                   'attribute_values', (
                       SELECT json_agg(json_build_object(
                               'id', pav.id,
                               'attribute_id', pav.attribute_id,
                               'value', pav.value,
                               'in_use', pav.in_use,
                               'extra', pav.extra
                                       ))
                       FROM store_product_attribute_value pav
                       WHERE pav.attribute_id IN (
                           SELECT pa.id
                           FROM store_product_attribute pa
                           WHERE pa.product_id = p.id
                       )
                   ),
                   'coupon', (CASE
                                  WHEN sc.id IS NULL THEN NULL
                                  ELSE json_build_object(
                                          'id', sc.id,
                                          'price', sc.price,
                                          'expired', sc.expired_at
                                       ) END),
                   'store', json_build_object(
                           'id', s.id,
                           'slug', s.slug,
                           'name', s.name,
                           'address', s.address,
                           'phone', s.phone,
                           'email', s.email,
                           'currency', s.currency,
                           'country', s.country,
                           'website', s.website,
                           'description', s.description
                            ),
                   'attachments_count', (SELECT COUNT(spa.id) FROM store_product_attach spa WHERE spa.product_id = p.id),
                   'attachments', (SELECT json_agg(attachment) FROM attachments),
                   'wishlist', json_build_object(
                           'id', w.id,
                           'product', w.product_id,
                           'store', w.store_id,
                           'customer', w.customer_id
                               ),
                   'brand', (
                       SELECT sb.name
                       FROM store_product_brand spb
                                LEFT JOIN store_brand sb ON sb.id = spb.brand_id
                       WHERE spb.product_id = p.id
                       LIMIT 1
                   ),
                   'supplier', (
                       SELECT ss.name
                       FROM store_product_supplier sps
                                LEFT JOIN store_supplier ss ON ss.id = sps.supplier_id
                       WHERE sps.product_id = p.id
                       LIMIT 1
                   ),
                   'manufacturer', (
                       SELECT sm.name
                       FROM store_product_manufacturer spm
                                LEFT JOIN store_manufacturer sm ON sm.id = spm.manufacturer_id
                       WHERE spm.product_id = p.id
                       LIMIT 1
                   )
           )
    INTO get_product
    FROM store_product p
             LEFT JOIN store_coupon_store_product scsp ON scsp.store_product_id = p.id
             LEFT JOIN store_coupon sc ON sc.id = scsp.store_coupon_id
             JOIN store s ON s.id = p.store_id
             LEFT JOIN store_wishlist w ON w.product_id = p.id AND w.store_id = s.id
    WHERE p.slug = get_product.slug
    GROUP BY p.id, s.id, sc.id, w.id;

    RETURN json_build_object(
            'product', get_product
           );
END;
$function$