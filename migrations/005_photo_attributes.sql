-- mia/migrations/005_photo_attributes.sql
-- Add name, description, price to gallery photos

ALTER TABLE mia_client_photos
  ADD COLUMN photo_name  VARCHAR(150) NOT NULL DEFAULT '' AFTER caption,
  ADD COLUMN description VARCHAR(500) NOT NULL DEFAULT '' AFTER photo_name,
  ADD COLUMN price       VARCHAR(50)  NOT NULL DEFAULT '' AFTER description;
