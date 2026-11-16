-- ============================================================================
-- SIKES — REPAIR_1932.sql
-- Perbaikan error: "1932 Table 'X' doesn't exist in engine"
-- dan "#1109 Unknown table in information_schema"
--
-- CARA PAKAI:
--   1. Stop MySQL, hapus folder mysql/data/sikes_pondok, Start MySQL.
--   2. Jalankan file ini via phpMyAdmin (SQL) ATAU command line:
--      mysql -u root < REPAIR_1932.sql
--   3. Import sikes_db.sql seperti biasa.
-- Aman dijalankan kapan pun: hanya membersihkan statistik yatim,
-- data aplikasi tidak tersentuh.
-- ============================================================================

DELETE FROM mysql.innodb_table_stats  WHERE database_name = 'sikes_pondok';
DELETE FROM mysql.innodb_index_stats  WHERE database_name = 'sikes_pondok';
FLUSH TABLES;