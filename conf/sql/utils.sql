-- Get all products combinations
-- $product = new Product(7, true, $this->module->language_id);
-- $combinations = $product->getAttributeCombinations($this->module->language_id, true);
SELECT pa.id_product, pa.id_product_attribute, agl.name, al.name
FROM `ps_product_attribute` pa
LEFT JOIN `ps_product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
LEFT JOIN `ps_attribute` a ON a.`id_attribute` = pac.`id_attribute`
LEFT JOIN `ps_attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
LEFT JOIN `ps_attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = 1)
LEFT JOIN `ps_attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = 1)
WHERE pa.`id_product` = 1
GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
ORDER BY pa.`id_product_attribute`

--Concat attribute
SELECT pa.id_product, pa.id_product_attribute,group_concat(concat(agl.name , ' ', al.name) separator ', ')
FROM `ps_product_attribute` pa
LEFT JOIN `ps_product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
LEFT JOIN `ps_attribute` a ON a.`id_attribute` = pac.`id_attribute`
LEFT JOIN `ps_attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
LEFT JOIN `ps_attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = 1)
LEFT JOIN `ps_attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = 1)
WHERE pa.`id_product` = 269
GROUP BY pa.id_product, pa.id_product_attribute
ORDER BY pa.id_product


-- Requête pour affichage des produits synchronisés chez Reverb
SELECT IF (pa.id_product_attribute IS NULL, CONCAT(p.id_product, '-', '0'), CONCAT(pa.id_product, '-', pa.id_product_attribute)) as identifier,
p.id_product, pa.id_product_attribute, p.reference as reference,
IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, ' ', GROUP_CONCAT(CONCAT (agl.name, ' ', al.name) SEPARATOR ', '))) as name,
rs.status
FROM `ps_product` p
INNER JOIN `ps_product_lang` `pl` ON pl.`id_product` = p.`id_product`
INNER JOIN `ps_reverb_attributes` `ra` ON ra.`id_product` = p.`id_product`
LEFT JOIN `ps_product_attribute` `pa` ON pa.`id_product` = p.`id_product`
LEFT JOIN `ps_reverb_sync` `rs` ON (rs.`id_product` = p.`id_product` AND (pa.`id_product_attribute` IS NULL OR rs.`id_product_attribute` = pa.`id_product_attribute`))
LEFT JOIN `ps_product_attribute_combination` `pac` ON pac.`id_product_attribute` = pa.`id_product_attribute`
LEFT JOIN `ps_attribute` `a` ON a.`id_attribute` = pac.`id_attribute`
LEFT JOIN `ps_attribute_group` `ag` ON ag.`id_attribute_group` = a.`id_attribute_group`
LEFT JOIN `ps_attribute_lang` `al` ON al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = 1
LEFT JOIN `ps_attribute_group_lang` `agl` ON agl.`id_attribute_group` = ag.`id_attribute_group` AND agl.`id_lang` = 1
WHERE (ra.`reverb_enabled` = 1)
AND pl.id_lang = 1
AND p.id_product = 7
GROUP BY p.id_product, p.reference, rs.status, rs.reverb_id, rs.details, rs.reverb_slug, rs.date, pa.id_product_attribute;
--rs.reverb_id,rs.details,rs.reverb_slug, rs.date as last_sync

-- exemple le produit 7 a 6 ps_product_attribute
mysql> select * from ps_product_attribute where id_product = 7;
+----------------------+------------+-----------+--------------------+----------+-------+------+------+-----------------+----------+----------+----------+----------+-------------------+------------+------------------+----------------+
| id_product_attribute | id_product | reference | supplier_reference | location | ean13 | isbn | upc  | wholesale_price | price    | ecotax   | quantity | weight   | unit_price_impact | default_on | minimal_quantity | available_date |
+----------------------+------------+-----------+--------------------+----------+-------+------+------+-----------------+----------+----------+----------+----------+-------------------+------------+------------------+----------------+
|                   35 |          7 |           |                    |          |       |      |      |        0.000000 | 0.000000 | 0.000000 |      100 | 0.000000 |          0.000000 |       NULL |                1 | 0000-00-00     |
|                   36 |          7 |           |                    |          |       |      |      |        0.000000 | 0.000000 | 0.000000 |      100 | 0.000000 |          0.000000 |       NULL |                1 | 0000-00-00     |
|                   37 |          7 |           |                    |          |       |      |      |        0.000000 | 0.000000 | 0.000000 |        0 | 0.000000 |          0.000000 |       NULL |                1 | 0000-00-00     |
|                   38 |          7 |           |                    |          |       |      |      |        0.000000 | 0.000000 | 0.000000 |        0 | 0.000000 |          0.000000 |       NULL |                1 | 0000-00-00     |
|                   39 |          7 |           |                    |          |       |      |      |        6.150000 | 0.000000 | 0.000000 |        0 | 0.000000 |          0.000000 |       NULL |                1 | 0000-00-00     |
|                   34 |          7 |           |                    |          |       |      |      |        0.000000 | 0.000000 | 0.000000 |      100 | 0.000000 |          0.000000 |          1 |                1 | 0000-00-00     |
+----------------------+------------+-----------+--------------------+----------+-------+------+------+-----------------+----------+----------+----------+----------+-------------------+------------+------------------+----------------+
6 rows in set (0.00 sec)

-- Chaque déclaison est liée à 2 attributes
mysql> select * FROM ps_product_attribute_combination WHERE id_product_attribute IN (34,35,36,37,38,39);
+--------------+----------------------+
| id_attribute | id_product_attribute |
+--------------+----------------------+
|            1 |                   34 |
|           16 |                   34 |
|            2 |                   35 |
|           16 |                   35 |
|            3 |                   36 |
|           16 |                   36 |
|            1 |                   37 |
|           15 |                   37 |
|            2 |                   38 |
|           15 |                   38 |
|            3 |                   39 |
|           15 |                   39 |
+--------------+----------------------+
12 rows in set (0.01 sec)


-- On récupère les attributs via les tables ps_attribute -> ps_attribute_lang
-- Et le groupe attribut via les tables ps_attribute_group -> ps_attribute_group_lang

CREATE TABLE IF NOT EXISTS `ps_reverb_sync` (
    `id_sync` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_product` int(10) unsigned NOT NULL,
    `id_product_attribute` int(10) unsigned,
    `reverb_id` varchar(32) ,
    `status` varchar(32) NOT NULL,
    `details` text,
    `reverb_slug` varchar(150) ,
    `date` datetime,
    `origin` text,
    PRIMARY KEY  (`id_sync`),
    FOREIGN KEY fk_reverb_sync_product(id_product) REFERENCES `ps_product` (id_product),
    FOREIGN KEY fk_reverb_sync_product(id_product_attribute) REFERENCES `ps_product_attribute` (id_product_attribute),
    UNIQUE (id_product, id_product_attribute)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


--Shipping

--Carriers list
SELECT c.*
FROM `ps_product_carrier` pc
INNER JOIN `ps_carrier` c
  ON (c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0)
WHERE pc.`id_product` = 269
  AND pc.`id_shop` = 1;
