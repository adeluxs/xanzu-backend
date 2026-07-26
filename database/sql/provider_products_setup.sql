-- Add provider product integration columns to listings
ALTER TABLE listings
    ADD COLUMN product_url VARCHAR(2048) NULL AFTER provider_id,
    ADD COLUMN provider_product_id VARCHAR(191) NULL AFTER product_url;

-- Optional but recommended index for fast duplicate checks
CREATE INDEX listings_provider_provider_product_id_idx
    ON listings (provider_id, provider_product_id);
