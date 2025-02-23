create function get_coupons(store_id integer, type character varying DEFAULT NULL::character varying, start integer DEFAULT 0, row_count integer DEFAULT 10) returns json
    language plpgsql
as
$$
DECLARE
    get_coupons JSON;
    rows_count  INT;
BEGIN
    SELECT json_agg(json_build_object(
            'id', sc.id,
            'name', sc.name,
            'codes', (SELECT json_agg(json_build_object('id', c.id, 'code', c.code))
                      FROM store_coupon_code c
                      WHERE c.coupon_id = sc.id),
            'product', json_build_object('id', mp.id),
            'market_id', sc.store_id,
            'discount', COALESCE(sc.discount, 0),
            'price', COALESCE(sc.price, 0),
            'duration', sc.expired_at::timestamp - sc.started_at::timestamp,
            'startedAt', sc.started_at,
            'expiredAt', sc.expired_at
                    ))
    INTO get_coupons
    FROM store_coupon sc
             LEFT JOIN store_coupon_store_product scsp ON scsp.store_coupon_id = sc.id
             LEFT JOIN store_product mp ON scsp.store_product_id = mp.id
    WHERE sc.store_id = get_coupons.store_id
      AND sc.type = get_coupons.type
    ORDER BY MIN(sc.expired_at) ASC
    OFFSET start LIMIT row_count;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_coupon sc
    WHERE sc.store_id = get_coupons.store_id
      AND sc.type = get_coupons.type;

    RETURN json_build_object(
            'data', get_coupons,
            'rows_count', rows_count
           );
END;
$$;

alter function get_coupons(integer, varchar, integer, integer) owner to inno;

create function get_dashboard_customers(ids jsonb, start integer DEFAULT 0, rows_count integer DEFAULT 12) returns jsonb
    language plpgsql
as
$$
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
$$;

alter function get_dashboard_customers(jsonb, integer, integer) owner to inno;

create function backdrop_admin_stores(start integer DEFAULT 0, row_count integer DEFAULT 25) returns json
    language plpgsql
as
$$
DECLARE
    results JSON;
BEGIN
    SELECT json_agg(json_build_object(
                            'id', s.id,
                            'name', s.name,
                            'created', s.created_at,
                            'deleted', s.deleted_at,
                            'orders', (SELECT COUNT(*)
                                       FROM store_orders o
                                       WHERE o.store_id = s.id
                                       LIMIT 1),
                            'exports', (SELECT COUNT(*)
                                        FROM store_operation o
                                        WHERE o.store_id = s.id
                                        LIMIT 1),
                            'messages', (SELECT COUNT(*)
                                         FROM store_message m
                                         WHERE m.store_id = s.id
                                         LIMIT 1),
                            'products', (SELECT COUNT(*)
                                         FROM store_product p
                                         WHERE p.store_id = s.id
                                         LIMIT 1)
                    ) ORDER BY s.id DESC)
    INTO results
    FROM store s
    OFFSET start LIMIT row_count;
    RETURN json_build_object(
            'result', results
           );
END;
$$;

alter function backdrop_admin_stores(integer, integer) owner to inno;

create function get_dashboard_entries(user_id integer DEFAULT NULL::integer, status character varying DEFAULT NULL::character varying, type character varying DEFAULT NULL::character varying, start integer DEFAULT 0, row_count integer DEFAULT 25) returns jsonb
    language plpgsql
as
$$
DECLARE
    results jsonb;
    rows    int = 0;
BEGIN
    SELECT json_agg(json_build_object(
            'id', e.id,
            'title', ed.title,
            'created', e.created_at,
            'status', e.status,
            'deleted', e.deleted_at,
            'locked', e.locked_to,
            'slug', e.slug,
            'uid', u.id,
            'author', ud.first_name || ' ' || ud.last_name
                    ))
    FROM entry e
             JOIN entry_details ed ON e.id = ed.entry_id
             JOIN "user" u on u.id = e.user_id
             JOIN user_details ud ON u.id = ud.user_id
    WHERE CASE
              WHEN get_dashboard_entries.user_id IS NOT NULL THEN e.user_id = get_dashboard_entries.user_id
              ELSE e.user_id > 0 END
      AND e.type = get_dashboard_entries.type
      AND e.status = get_dashboard_entries.status
    GROUP BY e.id
    ORDER BY e.id DESC
    OFFSET start LIMIT row_count
    INTO results;

    SELECT count(e2.id)
    FROM entry e2
    WHERE e2.type = get_dashboard_entries.type
      AND e2.status = get_dashboard_entries.status
    INTO rows;

    RETURN json_build_object(
            'result', results,
            'rows', rows
           );
END;
$$;

alter function get_dashboard_entries(integer, varchar, varchar, integer, integer) owner to inno;

create function owner_store_search(query text, oid integer DEFAULT 0) returns json
    language plpgsql
as
$$DECLARE
    store_search JSON;
BEGIN
SELECT json_agg(json_build_object(
        'id', m.id,
        'name', m.name
                ))
INTO store_search
FROM store m WHERE m.owner_id = oid AND LOWER(m.name) LIKE LOWER('%' || query::text || '%');
RETURN json_build_object(
        'data', store_search
       );
END;$$;

alter function owner_store_search(text, integer) owner to inno;

create function customer_counters(customer_id integer) returns jsonb
    language plpgsql
as
$$
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
$$;

alter function customer_counters(integer) owner to inno;

create function create_customer(user_id integer, "values" json) returns integer
    language plpgsql
as
$$
DECLARE
    last_inserted_id INTEGER;
BEGIN
    INSERT INTO "store_customer" (member_id,
                                   first_name,
                                   last_name,
                                   phone,
                                   country,
                                   email,
                                   social_id,
                                   created_at)
    VALUES (user_id,
            values ->> 'first_name',
            values ->> 'last_name',
            values ->> 'phone',
            values ->> 'country',
            values ->> 'email',
            values ->> 'social_id',
            CURRENT_TIMESTAMP)
    RETURNING id INTO last_inserted_id;

    RETURN last_inserted_id;
END;
$$;

alter function create_customer(integer, json) owner to inno;

create function get_product(slug character varying) returns json
    language plpgsql
as
$$
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
$$;

alter function get_product(varchar) owner to inno;

create function get_dashboard_stores(start integer DEFAULT 0, rows_count integer DEFAULT 12, owner integer DEFAULT NULL::integer, store_slug character varying DEFAULT NULL::character varying) returns jsonb
    language plpgsql
as
$$
DECLARE
    results JSON;
BEGIN
    WITH stores AS (SELECT DISTINCT jsonb_build_object(
                                            'id', s.id,
                                            'name', s.name,
                                            'slug', s.slug,
                                            'country', s.country,
                                            'currency', s.currency,
                                            'orders', (SELECT json_agg(json_build_object(
                                            'id', o.id,
                                            'number', o.number,
                                            'created', o.created_at,
                                            'status', o.status,
                                            'total', o.total
                                                                       )) FROM store_orders o
                                                       WHERE o.store_id = s.id AND o.status = 'confirmed'
                                                       OFFSET start LIMIT rows_count),
                                            'products_count',
                                            (SELECT COUNT(p.id) FROM store_product p WHERE p.store_id = s.id),
                                            'products', (SELECT json_agg(json_build_object(
                                                            'id', sp.id,
                                                            'deleted', sp.deleted_at,
                                                            'created', sp.created_at,
                                                            'name', sp.short_name,
                                                            'slug', sp.slug,
                                                            'cost', sp.cost,
                                                            'fee', sp.fee,
                                                            'quantity', sp.quantity
                                                                         ))
                                                         FROM store_product sp
                                                         WHERE sp.store_id = s.id
                                                         OFFSET start LIMIT rows_count
                                            ),
                                            'owner', (SELECT json_build_object(
                                                                     'email', u.email,
                                                                     'roles', u.roles,
                                                                     'full_name', ud.first_name || ' ' || ud.last_name
                                                             )
                                                      FROM "user" u
                                                               JOIN user_details ud ON u.id = ud.user_id
                                                      WHERE u.id = s.owner_id
                                                      LIMIT 1),
                                            'created', s.created_at,
                                            'deleted', s.deleted_at,
                                            'locked', s.locked_to
                                    ) AS store
                    FROM store s
                    WHERE CASE WHEN get_dashboard_stores.owner IS NOT NULL
                                   THEN s.owner_id = get_dashboard_stores.owner
                               ELSE s.owner_id != 0 AND
                                    CASE WHEN get_dashboard_stores.store_slug IS NOT NULL
                                             THEN s.slug = get_dashboard_stores.store_slug ELSE s.slug LIKE '%' || '%' END
                              END
                    OFFSET start LIMIT rows_count)
    SELECT json_agg(store ORDER BY store ->> 'id' DESC)
    INTO results
    FROM stores;

    RETURN json_build_object(
            'result', results,
            'options', (SELECT json_agg(json_build_object(
                    'id', s2.id,
                    'slug', s2.slug,
                    'name', s2.name
                                        )) FROM store s2 OFFSET start LIMIT rows_count),
            'rows', (SELECT COUNT(*)
                     FROM store s WHERE CASE WHEN get_dashboard_stores.owner IS NOT NULL
                                                 THEN s.owner_id = get_dashboard_stores.owner
                                             ELSE s.owner_id != 0 AND
                                                  CASE WHEN get_dashboard_stores.store_slug IS NOT NULL
                                                           THEN s.slug = get_dashboard_stores.store_slug ELSE s.slug LIKE '%' || '%' END
                                            END)
           );
END;
$$;

alter function get_dashboard_stores(integer, integer, integer, varchar) owner to inno;

create function get_random_store(_limit integer DEFAULT 4) returns jsonb
    language plpgsql
as
$$
DECLARE
    results  JSON;
    products JSON;
BEGIN
    SELECT json_build_object(
                   'id', s.id,
                   'currency', s.currency,
                   'name', s.name,
                   'cc', s.cc::json,
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
                    'text', spg.handler_text,
                    'slug', spg.slug,
                    'name', spg.name,
                    'summary', spg.summary
                                        ))
           )
    INTO results
    FROM store s
             LEFT JOIN attach a on a.id = s.attach_id
             LEFT JOIN store_payment_gateway_store spgs on s.id = spgs.store_id
             LEFT JOIN store_payment_gateway spg on spg.id = spgs.gateway_id
             JOIN store_product sp2 on s.id = sp2.store_id
    WHERE s.deleted_at IS NULL
    GROUP BY s.id, a.id
    HAVING COUNT(sp2.id) > 0
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
            'reduce', (SELECT json_build_object(
                                      'value', spd.value,
                                      'unit', spd.unit
                              )
                       FROM store_product_discount spd
                       WHERE spd.product_id = p.id
                       LIMIT 1),
            'payments', (SELECT json_agg(json_build_object(
                    'id', g.id,
                    'slug', g.slug,
                    'name', g.name,
                    'summary', g.summary,
                    'name', g.name
                                         ))
                         FROM store_payment_gateway_store spg
                                  LEFT JOIN store_payment_gateway g ON g.id = spg.gateway_id
                         WHERE spg.store_id = p.store_id),
            'currency', (SELECT s.currency FROM store s WHERE s.id = p.store_id LIMIT 1),
            'attachment', (SELECT json_build_object(
                                          'name', a.name,
                                          'path', a.path
                                  )
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
                 sp.fee
          FROM store_product sp
          WHERE sp.deleted_at IS NULL
          ORDER BY RANDOM()
          LIMIT get_random_store._limit) AS p
    INTO products;

    RETURN
        json_build_object(
                'store', results,
                'products', products
        );
END;
$$;

alter function get_random_store(integer) owner to inno;

create function create_address(customer_id integer, "values" json) returns integer
    language plpgsql
as
$$
DECLARE
    last_inserted_id INTEGER;
    cid              INT;
BEGIN
    cid := customer_id;

    INSERT INTO "store_address" (customer_id,
                                  line1,
                                  line2,
                                  phone,
                                  country,
                                  city,
                                  region,
                                  postal,
                                  created_at)
    VALUES (cid,
            values ->> 'line1',
            values ->> 'line2',
            values ->> 'phone',
            values ->> 'country',
            values ->> 'city',
            values ->> 'region',
            values ->> 'postal',
            CURRENT_TIMESTAMP)
    RETURNING id INTO last_inserted_id;

    RETURN last_inserted_id;
END;
$$;

alter function create_address(integer, json) owner to inno;

create function get_random_products(row_count integer DEFAULT 18) returns json
    language plpgsql
as
$$DECLARE
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
                                          'attach_path', a.path,
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
                                      ON (pa.product_id) pa.product_id, a.name, a.path
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
END;$$;

alter function get_random_products(integer) owner to inno;

create function backdrop_store_extra(store_id integer) returns json
    language plpgsql
as
$$
DECLARE
    suppliers     JSON;
    brands        JSON;
    manufacturers JSON;
BEGIN
    SELECT json_agg(json_build_object(
                            'id', b.id,
                            'name', b.name
                    ) ORDER BY b.name ASC)
    INTO brands
    FROM store_brand b
    WHERE b.store_id = backdrop_store_extra.store_id;

    SELECT json_agg(json_build_object(
                            'id', s.id,
                            'name', s.name
                    ) ORDER BY s.name ASC)
    INTO suppliers
    FROM store_supplier s
    WHERE s.store_id = backdrop_store_extra.store_id;

    SELECT json_agg(json_build_object(
                            'id', m.id,
                            'name', m.name
                    ) ORDER BY m.name ASC)
    INTO manufacturers
    FROM store_manufacturer m
    WHERE m.store_id = backdrop_store_extra.store_id;

    RETURN json_build_object(
            'suppliers', suppliers,
            'brands', brands,
            'manufacturers', manufacturers
           );
END;
$$;

alter function backdrop_store_extra(integer) owner to inno;

create function backdrop_owner_stores(owner_id integer, start integer DEFAULT 0, row_count integer DEFAULT 10) returns json
    language plpgsql
as
$$
DECLARE
    results JSON;
BEGIN
    WITH stores AS (SELECT DISTINCT jsonb_build_object(
                                            'id', s.id,
                                            'name', s.name,
                                            'products', (SELECT COUNT(p.id)
                                                         FROM store_product p
                                                         WHERE p.store_id = s.id
                                                         LIMIT 1),
                                            'owner', (SELECT u.email
                                                      FROM "user" u
                                                      WHERE u.id = s.owner_id
                                                      LIMIT 1),
                                            'created', s.created_at,
                                            'deleted', s.deleted_at,
                                            'locked', s.locked_to
                                    ) AS store
                    FROM store s
                    WHERE s.owner_id = backdrop_owner_stores.owner_id
                    OFFSET start LIMIT row_count)
    SELECT json_agg(store ORDER BY store ->> 'id' DESC)
    INTO results
    FROM stores;

    RETURN json_build_object(
            'result', results,
            'rows', (SELECT COUNT(*) FROM store s WHERE s.owner_id = backdrop_owner_stores.owner_id)
           );
END;
$$;

alter function backdrop_owner_stores(integer, integer, integer) owner to inno;

create function get_active_coupon(store_id integer, type text, event smallint DEFAULT 1) returns json
    language plpgsql
as
$$
DECLARE
    coupon JSON;
BEGIN

    SELECT json_build_object('coupon', json_build_object(
            'id', sc.id,
            'price', sc.price,
            'available', sc.available,
            'name', sc.name,
            'code', (SELECT scc.code
                     FROM store_coupon_code scc
                     WHERE scc.coupon_id = sc.id
                       AND scc.id NOT IN (SELECT DISTINCT coupon_code_id
                                          FROM store_coupon_usage)
                     ORDER BY RANDOM()
                     LIMIT 1),
            'promotion', sc.promotion_text,
            'discount', sc.discount,
            'start', to_char(sc.started_at::timestamp, 'YYYY-MM-DD HH24:MI:SS'),
            'end', to_char(sc.expired_at::timestamp, 'YYYY-MM-DD HH24:MI:SS')
                                       )) AS single
    INTO coupon
    FROM store_coupon sc
    WHERE sc.store_id = get_active_coupon.store_id
      AND sc.type = get_active_coupon.type
      AND sc.event = get_active_coupon.event
      AND sc.started_at::timestamp < CURRENT_TIMESTAMP
      AND sc.expired_at::timestamp > CURRENT_TIMESTAMP
    LIMIT 1;
    IF coupon IS NULL THEN RETURN 0; ELSE RETURN coupon; END IF;
END;
$$;

alter function get_active_coupon(integer, text, smallint) owner to inno;

create function get_products_by_child_category(child_id integer, start integer DEFAULT 0, row_count integer DEFAULT 10, search text DEFAULT NULL::text) returns json
    language plpgsql
as
$$
DECLARE
    get_products JSON;
    rows_count   INT;
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
                                              'attach_path', a.path,
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
                               LEFT JOIN (SELECT DISTINCT ON (pa.product_id) pa.product_id, a.name, a.path
                                          FROM store_product_attach pa
                                                   LEFT JOIN attach a ON pa.attach_id = a.id
                                          ORDER BY pa.product_id) a ON a.product_id = p.id
                               LEFT JOIN store_wishlist w ON w.product_id = p.id
                               JOIN store m ON m.id = p.store_id
                      WHERE p.deleted_at IS NULL
                        AND cp.category_id = child_id
                      OFFSET start LIMIT row_count)
    SELECT json_agg(product ORDER BY product->>'id' DESC )
    INTO get_products
    FROM products;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_product p
             JOIN store_category_product cp ON p.id = cp.product_id
             JOIN store_category c ON c.id = cp.category_id
    WHERE p.deleted_at IS NULL
      AND cp.category_id = child_id;
    RETURN json_build_object(
            'data', get_products,
            'rows_count', rows_count
           );
END;
$$;

alter function get_products_by_child_category(integer, integer, integer, text) owner to inno;

create function dashboard_message_counter(user_id integer) returns integer
    language plpgsql
as
$$
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
$$;

alter function dashboard_message_counter(integer) owner to inno;

create function get_coupon_codes(store_id integer, coupon_id integer, type character varying) returns json
    language plpgsql
as
$$
DECLARE
    codes JSON;
BEGIN
    SELECT json_agg(json_build_object(
            'id', cc.id,
            'code', cc.code
                    ))
    INTO codes
    FROM store_coupon_code cc
             LEFT OUTER JOIN store_coupon sc on sc.store_id = get_coupon_codes.store_id
             LEFT JOIN store_coupon_usage scu on cc.id != scu.coupon_code_id
    WHERE sc.type = get_coupon_codes.type
      AND cc.coupon_id = get_coupon_codes.coupon_id;

    RETURN json_build_object(
            'result', codes
           );
END;
$$;

alter function get_coupon_codes(integer, integer, varchar) owner to inno;

create function get_order_summary(session character varying DEFAULT NULL::character varying, customer_id integer DEFAULT NULL::integer, number character varying DEFAULT NULL::character varying) returns json
    language plpgsql
as
$$
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
$$;

alter function get_order_summary(varchar, integer, varchar) owner to inno;

create function store_search(query text) returns json
    language plpgsql
as
$$DECLARE
    store_search JSON;
BEGIN
SELECT json_agg(json_build_object(
        'id', m.id,
        'name', m.name
                ))
INTO store_search
FROM store m WHERE LOWER(m.name) LIKE LOWER('%' || query::text || '%');

RETURN json_build_object(
        'data', store_search
       );
END;$$;

alter function store_search(text) owner to inno;

create function get_customer_messages(customer_id integer, "offset" integer DEFAULT 0, "limit" integer DEFAULT 25) returns json
    language plpgsql
as
$$
DECLARE
    get_customer_messages JSON;
    rows_count            INT;
BEGIN
    SELECT json_agg(json_build_object(
            'id', sm.id,
            'created', sm.created_at,
            'deleted', sm.deleted_at,
            'priority', INITCAP(sm.priority),
            'read', (SELECT sm2.read FROM store_message sm2 WHERE sm2.owner_id IS NULL ORDER BY sm2.id DESC LIMIT 1),
            'answers', (SELECT COUNT(*) FROM store_message m WHERE m.parent_id = sm.id),
            'store', json_build_object(
                    'id', s.id,
                    'name', s.name,
                    'slug', s.slug
                      ),
            'product', (CASE
                            WHEN sp.id IS NULL THEN NULL
                            ELSE json_build_object(
                                    'id', sp.id,
                                    'slug', sp.slug,
                                    'short_name', sp.short_name
                                 ) END),
            'order', (CASE
                          WHEN so.id IS NULL THEN NULL
                          ELSE json_build_object(
                                  'id', so.id,
                                  'number', so.number
                               ) END)
                    ))
    INTO get_customer_messages
    FROM store_message sm
             LEFT JOIN store_product sp ON sp.id = sm.product_id
             LEFT JOIN store_orders so ON so.id = sm.orders_id
             LEFT JOIN store s ON s.id = sm.store_id
    WHERE sm.customer_id = get_customer_messages.customer_id
      AND sm.parent_id IS NULL
    ORDER BY MAX(sm.id) DESC
    OFFSET get_customer_messages.offset LIMIT get_customer_messages.limit;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_message sm
    WHERE sm.customer_id = get_customer_messages.customer_id;

    RETURN json_build_object(
            'data', get_customer_messages,
            'rows_count', rows_count
           );

END;
$$;

alter function get_customer_messages(integer, integer, integer) owner to inno;

create function backdrop_messages(ids jsonb, start integer DEFAULT 0, row_count integer DEFAULT 25) returns jsonb
    language plpgsql
as
$$
DECLARE
    results jsonb;
    rows    int = 0;
BEGIN
    SELECT json_agg(json_build_object(
            'id', m.id,
            'created', m.created_at,
            'priority', m.priority,
            'product', sp.short_name,
            'product_slug', sp.slug,
            'order', so.number,
            'store', m.store_id,
            'message', m.message,
            'customer', initcap(c.first_name || ' ' || c.last_name)
                    ))
    FROM store_message m
             LEFT JOIN store_customer c ON c.id = m.customer_id
             LEFT JOIN store_orders so ON m.orders_id = so.id
             LEFT JOIN store_product sp ON m.product_id = sp.id
    WHERE m.store_id IN (SELECT jsonb_array_elements_text(ids)::INT)
      AND m.parent_id IS NULL
    ORDER BY MAX(m.id) DESC
    OFFSET start LIMIT row_count
    INTO results;

    SELECT COUNT(m2.id)
    FROM store_message m2
             LEFT JOIN store_customer c1 ON c1.id = m2.customer_id
    WHERE m2.parent_id IS NULL
      AND m2.store_id IN (SELECT jsonb_array_elements_text(ids)::INT)
    INTO rows;

    RETURN json_build_object(
            'rows', rows,
            'result', results
           );
END;
$$;

alter function backdrop_messages(jsonb, integer, integer) owner to inno;

create function get_customer_orders(customer_id integer, start integer DEFAULT 0, row_count integer DEFAULT 25) returns json
    language plpgsql
as
$$
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
                            'cancelled', o.cancelled_at,
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
                                               'attachment', (SELECT json_build_object(
                                                                             'name', a.name,
                                                                             'path', a.path
                                                                     ) FROM store_product_attach pa
                                                                                LEFT JOIN attach a ON pa.attach_id = a.id
                                                              WHERE pa.product_id = p.id LIMIT 1),
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
$$;

alter function get_customer_orders(integer, integer, integer) owner to inno;

create function backdrop_fetch_stores(start integer DEFAULT 0, row_count integer DEFAULT 10) returns json
    language plpgsql
as
$$
DECLARE
    results JSON;
BEGIN
    WITH stores AS (SELECT DISTINCT jsonb_build_object(
                                            'id', s.id,
                                            'name', s.name,
                                            'products',
                                            (SELECT COUNT(p.id) FROM store_product p WHERE p.store_id = s.id),
                                            'owner', (SELECT json_build_object(
                                                                     'email', u.email,
                                                                     'roles', u.roles
                                                             )
                                                      FROM "user" u
                                                      WHERE u.id = s.owner_id),
                                            'created', s.created_at,
                                            'deleted', s.deleted_at,
                                            'locked', s.locked_to
                                    ) AS store
                    FROM store s
                    OFFSET start LIMIT row_count)
    SELECT json_agg(store ORDER BY store ->> 'id' DESC)
    INTO results
    FROM stores;

    RETURN json_build_object(
            'result', results,
            'rows', (SELECT COUNT(*)
                     FROM store)
           );
END;
$$;

alter function backdrop_fetch_stores(integer, integer) owner to inno;

create function get_store(slug character varying, customer_id integer DEFAULT 0, start integer DEFAULT 0, row_count integer DEFAULT 25) returns jsonb
    language plpgsql
as
$$
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

$$;

alter function get_store(varchar, integer, integer, integer) owner to inno;

create function create_user_details(user_id integer, "values" json) returns integer
    language plpgsql
as
$$
DECLARE
    last_details_id INTEGER;
    social_id       INT;
    uid             INT;
    date_birth      DATE;
BEGIN
    uid := user_id;
    date_birth := TO_DATE(values ->> 'date_birth', 'YYYY-MM-DD');

    INSERT INTO "user_details" (user_id,
                                first_name,
                                last_name,
                                phone,
                                country,
                                city,
                                about,
                                date_birth,
                                updated_at)
    VALUES (uid,
            values ->> 'first_name',
            values ->> 'last_name',
            values ->> 'phone',
            values ->> 'country',
            values ->> 'city',
            values ->> 'about',
            date_birth,
            CURRENT_TIMESTAMP)
    RETURNING id INTO last_details_id;

    INSERT INTO "user_social" (details_id) VALUES (last_details_id) RETURNING id INTO social_id;

    RETURN social_id;
END;
$$;

alter function create_user_details(integer, json) owner to inno;

create function backdrop_stores(owner_id integer) returns json
    language plpgsql
as
$$
DECLARE
    results JSON;
BEGIN
    SELECT json_agg(json_build_object(
            'id', s.id,
            'name', s.name,
            'created', s.created_at,
            'deleted', s.deleted_at,
            'locked', s.locked_to,
            'orders', (SELECT COUNT(*)
                       FROM store_orders o
                       WHERE o.store_id = s.id
                       LIMIT 1),
            'exports', (SELECT COUNT(*)
                        FROM store_operation o
                        WHERE o.store_id = s.id
                        LIMIT 1),
            'messages', (SELECT COUNT(*)
                         FROM store_message m
                         WHERE m.store_id = s.id
                         LIMIT 1),
            'products', (SELECT COUNT(*)
                         FROM store_product p
                         WHERE p.store_id = s.id
                         LIMIT 1)
                    ))
    INTO results
    FROM store s
    WHERE s.owner_id = backdrop_stores.owner_id;
    RETURN json_build_object(
            'result', results
           );
END;
$$;

alter function backdrop_stores(integer) owner to inno;

create function get_products(start integer DEFAULT 0, row_count integer DEFAULT 10) returns json
    language plpgsql
as
$$
DECLARE
    get_products JSON;
    rows_count   INT;
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
                                              'attach_path', a.path,
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
                               LEFT JOIN (SELECT DISTINCT ON (pa.product_id) pa.product_id, a.name, a.path
                                          FROM store_product_attach pa
                                                   LEFT JOIN attach a ON pa.attach_id = a.id
                                          ORDER BY pa.product_id) a ON a.product_id = p.id
                               LEFT JOIN store_wishlist w ON w.product_id = p.id
                               JOIN store m ON m.id = p.store_id
                      WHERE p.deleted_at IS NULL OFFSET start LIMIT row_count)
    SELECT json_agg(product ORDER BY product->>'id' DESC )
    INTO get_products FROM products;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_product p
             JOIN store_category_product cp ON p.id = cp.product_id
             JOIN store_category c ON c.id = cp.category_id
    WHERE p.deleted_at IS NULL;

    RETURN json_build_object(
            'data', get_products,
            'rows_count', rows_count
           );
END;
$$;

alter function get_products(integer, integer) owner to inno;

create function search_products(term text, category text DEFAULT NULL::text, start integer DEFAULT 0, row_count integer DEFAULT 25) returns json
    language plpgsql
as
$$
DECLARE
    get_products JSON;
    rows_count   INT;
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
                                              'attach_path', a.path,
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
                               LEFT JOIN (SELECT DISTINCT ON (pa.product_id) pa.product_id, a.name, a.path
                                          FROM store_product_attach pa
                                                   LEFT JOIN attach a ON pa.attach_id = a.id
                                          ORDER BY pa.product_id) a ON a.product_id = p.id
                               LEFT JOIN store_wishlist w ON w.product_id = p.id
                               JOIN store m ON m.id = p.store_id
                      WHERE p.deleted_at IS NULL
                        AND LOWER(p.name) LIKE LOWER('%' || term::text || '%')
                        AND (category IS NULL OR
                             c.parent_id IN (SELECT c2.id FROM store_category c2 WHERE c2.slug = category))
                      OFFSET start LIMIT row_count)

    SELECT json_agg(product ORDER BY product->>'id' DESC)
    INTO get_products
    FROM products;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_product p
             JOIN store_category_product cp ON p.id = cp.product_id
             JOIN store_category c ON c.id = cp.category_id
    WHERE p.deleted_at IS NULL
      AND LOWER(p.name) LIKE LOWER('%' || term::text || '%')
      AND (category IS NULL OR c.parent_id IN (SELECT c2.id FROM store_category c2 WHERE c2.slug = category));

    RETURN json_build_object(
            'data', get_products,
            'rows_count', rows_count
           );
END;
$$;

alter function search_products(text, text, integer, integer) owner to inno;

create function get_products_by_parent_category(category_slug character varying, start integer DEFAULT 0, row_count integer DEFAULT 10, search text DEFAULT NULL::text) returns json
    language plpgsql
as
$$
DECLARE
    get_products JSON;
    rows_count   INT;
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
                                              'attach_path', a.path,
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
                               LEFT JOIN (SELECT DISTINCT ON (pa.product_id) pa.product_id, a.name, a.path
                                          FROM store_product_attach pa
                                                   LEFT JOIN attach a ON pa.attach_id = a.id
                                          ORDER BY pa.product_id) a ON a.product_id = p.id
                               LEFT JOIN store_wishlist w ON w.product_id = p.id
                               JOIN store m ON m.id = p.store_id
                      WHERE p.deleted_at IS NULL
                        AND c.parent_id in (SELECT id FROM store_category WHERE slug = category_slug)
                      OFFSET start LIMIT row_count)

    SELECT json_agg(product ORDER BY product->>'id' DESC )
    INTO get_products
    FROM products;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_product p
             JOIN store_category_product cp ON p.id = cp.product_id
             JOIN store_category c ON c.id = cp.category_id
    WHERE p.deleted_at IS NULL
      AND c.parent_id IN (SELECT id FROM store_category WHERE slug = category_slug);

    RETURN json_build_object(
            'data', get_products,
            'rows_count', rows_count
           );
END;
$$;

alter function get_products_by_parent_category(varchar, integer, integer, text) owner to inno;

create function create_user("values" json) returns integer
    language plpgsql
as
$$
DECLARE
    last_inserted_id INTEGER;
    roles            json;
BEGIN
    roles := values ->> 'roles';

    INSERT INTO "user" (email, password, roles, ip, created_at)
    VALUES (values ->> 'email', values ->> 'password', roles, values ->> 'ip', CURRENT_TIMESTAMP)
    RETURNING id INTO last_inserted_id;

    RETURN last_inserted_id;
EXCEPTION
    WHEN unique_violation THEN
        RAISE NOTICE 'Unique constraint violation occurred';
        -- Perform additional actions as needed
        RETURN -1;
END;
$$;

alter function create_user(json) owner to inno;

create function get_messages(store_id integer, priority text, start integer DEFAULT 0, row_count integer DEFAULT 25) returns json
    language plpgsql
as
$$
DECLARE
    get_messages JSON;
    rows_count   INT;
BEGIN
    SELECT json_agg(json_build_object(
            'id', sm.id,
            'store', get_messages.store_id,
            'created', sm.created_at,
            'priority', INITCAP(sm.priority),
            'read', (SELECT sm2.read FROM store_message sm2 WHERE sm2.owner_id IS NOT NULL ORDER BY sm2.id DESC LIMIT 1),
            'answers', (SELECT COUNT(*) FROM store_message mc WHERE mc.parent_id = sm.id),
            'customer', json_build_object(
                    'id', sc.id,
                    'phone', sc.phone,
                    'full_name', CONCAT_WS(' ', sc.first_name, sc.last_name)
                        ),
            'product', (CASE
                            WHEN sp.id IS NULL THEN NULL
                            ELSE json_build_object(
                                    'id', sp.id,
                                    'slug', sp.slug,
                                    'short_name', sp.short_name,
                                    'picture', a.name,
                                    'path', a.path
                                 ) END),
            'order', (CASE
                          WHEN mo.id IS NULL THEN NULL
                          ELSE json_build_object(
                                  'id', mo.id,
                                  'number', mo.number
                               ) END)
                    ))
    INTO get_messages
    FROM store_message sm
             LEFT JOIN store_product sp ON sp.id = sm.product_id
             LEFT JOIN store_product_attach spa ON sp.id = spa.product_id
             LEFT JOIN attach a ON a.id = spa.attach_id
             LEFT JOIN store_orders mo ON mo.id = sm.orders_id
             LEFT JOIN store_customer sc ON sc.id = sm.customer_id
    WHERE sm.store_id = get_messages.store_id
      AND sm.priority = get_messages.priority
      AND sm.parent_id IS NULL
    ORDER BY MAX(sm.id) DESC
    OFFSET start LIMIT row_count;

    SELECT COUNT(*)
    INTO rows_count
    FROM store_message sm
    WHERE sm.store_id = get_messages.store_id
      AND sm.priority = get_messages.priority;

    RETURN json_build_object(
            'data', get_messages,
            'rows_count', rows_count
           );

END;
$$;

alter function get_messages(integer, text, integer, integer) owner to inno;

create function backdrop_products(store_id integer, query text DEFAULT NULL::text, start integer DEFAULT 0, row_count integer DEFAULT 25) returns json
    language plpgsql
as
$$
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
$$;

alter function backdrop_products(integer, text, integer, integer) owner to inno;


