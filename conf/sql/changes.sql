# Remove ps_product.reverb_enabled
ALTER TABLE ps_product DROP COLUMN reverb_enabled;

# Change ps_reverb_sync colmuns
UPDATE ps_reverb_sync SET reverb_ref = id_sync;
ALTER TABLE ps_reverb_sync CHANGE COLUMN reverb_ref reverb_id INT;
ALTER TABLE ps_reverb_sync CHANGE COLUMN url_reverb reverb_slug TEXT;

# Insert ps_reverb_attributes values
DROP TABLE IF EXISTS ps_reverb_attributes;

CREATE TABLE IF NOT EXISTS `ps_reverb_attributes` (
  `id_attribute` int(11) NOT NULL AUTO_INCREMENT,
  `reverb_enabled` tinyint(1),
  `id_product` int(11) NOT NULL ,
  `id_lang` int(11) NOT NULL,
  `sold_as_is` tinyint(1),
  `finish` varchar(50) ,
  `origin_country_code` varchar(50),
  `year` varchar(50),
  `id_condition` varchar(50),
  PRIMARY KEY  (`id_attribute`)
) ENGINE='InnoDB' DEFAULT CHARSET=utf8;

INSERT INTO ps_reverb_attributes (reverb_enabled,
                                  id_product,
                                  id_lang,
                                  sold_as_is,
                                  finish,
                                  origin_country_code,
                                  year,
                                  id_condition
) VALUES (1, 114,1,0,'NEW','FR','2016','7c3f45de-2ae0-4c81-8400-fdb6b1d74890');

ALTER TABLE ps_reverb_sync ADD COLUMN `origin` text;

CREATE TABLE IF NOT EXISTS `ps_reverb_sync_history` (
  `id_sync_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(10) unsigned NOT NULL,
  `origin` text,
  `date` datetime,
  `details` text,
  PRIMARY KEY  (`id_sync_history`)
) ENGINE='INNODB' DEFAULT CHARSET=utf8;