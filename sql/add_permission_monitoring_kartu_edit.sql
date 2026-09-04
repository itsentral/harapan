-- ------------------------------------------------------------
-- Tambah permission edit debet/kredit untuk module Monitoring Kartu
-- Jalankan sekali di database aplikasi.
--
-- Catatan:
--   - User dengan role admin otomatis lolos (is_admin bypass), tidak
--     memerlukan permission ini.
--   - Untuk user non-admin, permission 'Monitoring_Kartu.Edit' didaftarkan
--     lalu diberikan ke SEMUA group yang sudah punya 'Monitoring_Kartu.View'
--     agar hak akses tetap konsisten.
-- ------------------------------------------------------------

-- 1. Daftarkan permission (skip bila sudah ada)
INSERT INTO `permissions` (`nm_permission`, `ket`, `id_menu`, `nm_menu`, `created_on`, `created_by`)
SELECT 'Monitoring_Kartu.Edit', 'Edit', 0, 'Monitoring Kartu', NOW(), '1'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `nm_permission` = 'Monitoring_Kartu.Edit'
);

-- 2. Berikan permission Edit ke setiap group yang sudah punya permission View
INSERT INTO `group_permissions` (`id_group`, `id_permission`)
SELECT gp.`id_group`, pe.`id_permission`
FROM `group_permissions` gp
JOIN `permissions` pv ON gp.`id_permission` = pv.`id_permission`
    AND pv.`nm_permission` = 'Monitoring_Kartu.View'
JOIN `permissions` pe ON pe.`nm_permission` = 'Monitoring_Kartu.Edit'
WHERE NOT EXISTS (
    SELECT 1 FROM `group_permissions` gx
    WHERE gx.`id_group` = gp.`id_group`
      AND gx.`id_permission` = pe.`id_permission`
);
