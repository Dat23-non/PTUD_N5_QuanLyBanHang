-- Chon database ptud_quan_ly_ban_hang trong phpMyAdmin truoc khi import.
-- Danh muc khoi tao cho do an, KHONG phai catalogue/bao gia chinh thuc.
-- Ten NCC DalatFarm theo yeu cau nhom; can doi chieu ten thuong mai khi chot bao cao.
-- Gia va thue de 0 vi chua chot. Don gia chuyen kho van nhap tren phieu.
-- Chi them ma chua co, khong ghi de gia/ten/trang thai nguoi dung da sua.
-- Khong co FK hang_hoa -> nha_cung_cap trong schema hien tai: chua gan NCC cho tung hang.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO nha_cung_cap (ma_ncc, ten_ncc, ghi_chu)
SELECT s.ma, s.ten, 'Danh mục nhà cung cấp sản phẩm sữa; cần bổ sung hồ sơ giao dịch thực tế.'
FROM (
    SELECT 'NCC_VNM' AS ma, 'Vinamilk' AS ten
    UNION ALL SELECT 'NCC_TH', 'TH'
    UNION ALL SELECT 'NCC_DLF', 'DalatFarm'
) s
WHERE NOT EXISTS (SELECT 1 FROM nha_cung_cap n WHERE n.ma_ncc = s.ma);

INSERT INTO don_vi_tinh (ma_don_vi, ten_don_vi)
VALUES ('HOP','Hộp'), ('HU','Hũ'), ('CHA','Chai'), ('LON','Lon')
ON DUPLICATE KEY UPDATE don_vi_tinh_id = don_vi_tinh_id;

-- Chi dung don vi nho/co so. Chua tu dat quy cach thung/loc va he so quy doi.
INSERT INTO hang_hoa (ma_hang, ten_hang, chung_loai, don_vi_nho_id, ty_le_quy_doi, gia_si, gia_le, thue_suat)
SELECT s.ma, s.ten, s.loai, d.don_vi_tinh_id, 1, 0, 0, 0
FROM (
    SELECT 'VNM_ST_CD180' AS ma, 'Vinamilk · Sữa tươi có đường 180 ml' AS ten, 'Sữa tươi' AS loai, 'Hộp' AS dvt
    UNION ALL SELECT 'VNM_ST_KD180', 'Vinamilk · Sữa tươi không đường 180 ml', 'Sữa tươi', 'Hộp'
    UNION ALL SELECT 'VNM_SC_CD100', 'Vinamilk · Sữa chua có đường 100 g', 'Sữa chua ăn', 'Hũ'
    UNION ALL SELECT 'VNM_SCU180', 'Vinamilk · Sữa chua uống 180 ml', 'Sữa chua uống', 'Chai'
    UNION ALL SELECT 'VNM_SD380', 'Vinamilk · Sữa đặc 380 g', 'Sữa đặc', 'Lon'
    UNION ALL SELECT 'VNM_SB400', 'Vinamilk · Sữa bột 400 g', 'Sữa bột', 'Lon'
    UNION ALL SELECT 'TH_ST_CD180', 'TH · Sữa tươi có đường 180 ml', 'Sữa tươi', 'Hộp'
    UNION ALL SELECT 'TH_ST_KD180', 'TH · Sữa tươi không đường 180 ml', 'Sữa tươi', 'Hộp'
    UNION ALL SELECT 'TH_SC100', 'TH · Sữa chua 100 g', 'Sữa chua ăn', 'Hũ'
    UNION ALL SELECT 'TH_SCU180', 'TH · Sữa chua uống 180 ml', 'Sữa chua uống', 'Chai'
    UNION ALL SELECT 'DLF_ST180', 'DalatFarm · Sữa tươi 180 ml', 'Sữa tươi', 'Hộp'
    UNION ALL SELECT 'DLF_SC100', 'DalatFarm · Sữa chua 100 g', 'Sữa chua ăn', 'Hũ'
) s
JOIN don_vi_tinh d ON d.ten_don_vi = s.dvt
WHERE NOT EXISTS (SELECT 1 FROM hang_hoa h WHERE h.ma_hang = s.ma);

COMMIT;
