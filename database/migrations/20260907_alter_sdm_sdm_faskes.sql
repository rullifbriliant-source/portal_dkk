-- ALTER struktur untuk modul SDMK Puskesmas (sudah ter-apply di DB, file ini untuk dokumentasi)
-- 1. Kategori + hierarki induk-anak di master jenis SDM
ALTER TABLE `tbl_sdm_items`
  ADD COLUMN `kategori` ENUM('Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang')
      NOT NULL DEFAULT 'Tenaga Kesehatan' AFTER `nama_item`,
  ADD COLUMN `id_parent` INT DEFAULT NULL AFTER `kategori`,
  ADD CONSTRAINT `fk_sdmitems_parent` FOREIGN KEY (`id_parent`)
      REFERENCES `tbl_sdm_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Breakdown ASN/Non-ASN x L/P di rekap per faskes
ALTER TABLE `tbl_sdm_faskes`
  ADD COLUMN `asn_l` INT NOT NULL DEFAULT 0 AFTER `jumlah`,
  ADD COLUMN `asn_p` INT NOT NULL DEFAULT 0 AFTER `asn_l`,
  ADD COLUMN `nonasn_l` INT NOT NULL DEFAULT 0 AFTER `asn_p`,
  ADD COLUMN `nonasn_p` INT NOT NULL DEFAULT 0 AFTER `nonasn_l`;

-- 3. jumlah jadi generated STORED
ALTER TABLE `tbl_sdm_faskes`
  MODIFY COLUMN `jumlah` INT GENERATED ALWAYS AS (`asn_l` + `asn_p` + `nonasn_l` + `nonasn_p`) STORED;
