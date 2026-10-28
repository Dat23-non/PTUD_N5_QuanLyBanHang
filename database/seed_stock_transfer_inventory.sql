-- Import SAU seed_dairy_catalog.sql va sau khi da co kho ST_TONG/ST_NVBH cua NPP ST_DEMO.
-- scripts/stock-transfer-demo.php --seed tu chuan bi tai khoan/kho va chay cac file nay.
-- Ton dau ky minh hoa cho do an: 100 don vi/mat hang o Kho Tong, 0 o kho NVBH.
-- Chi them cap (kho, hang) CHUA CO. Import lai KHONG reset ton hien tai.
-- Khong dung de ghi nhan hang nhap thuc te; hang nhap thuc te can chung tu nhap kho.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO ton_kho (kho_id, hang_hoa_id, so_luong_ton, so_luong_giu_cho)
SELECT k.kho_id, h.hang_hoa_id, CASE WHEN k.ma_kho = 'ST_TONG' THEN 100 ELSE 0 END, 0
FROM kho k
JOIN nha_phan_phoi n ON n.nha_phan_phoi_id = k.nha_phan_phoi_id
CROSS JOIN hang_hoa h
WHERE n.ma_npp = 'ST_DEMO' AND k.ma_kho IN ('ST_TONG', 'ST_NVBH')
  AND h.ma_hang IN ('VNM_ST_CD180','VNM_ST_KD180','VNM_SC_CD100','VNM_SCU180','VNM_SD380','VNM_SB400',
                    'TH_ST_CD180','TH_ST_KD180','TH_SC100','TH_SCU180','DLF_ST180','DLF_SC100')
  AND NOT EXISTS (SELECT 1 FROM ton_kho t WHERE t.kho_id = k.kho_id AND t.hang_hoa_id = h.hang_hoa_id);

COMMIT;
