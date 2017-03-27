DELETE ps_product.reverb_enabled column
RENAME ps_reverb_sync.reverb_ref (varchar(32)) TO ps_reverb_sync.reverb_id INT
RENAME ps_reverb_sync.url_reverb_ref (varchar(150)) TO ps_reverb_sync.reverb_url TEXT

Replace LEFT JOIN to JOIN on reverb_attributes for sync request