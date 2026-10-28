-- Nang cap bo du lieu da tao bang scripts/stock-transfer-demo.php phien ban cu.
-- Chi sua dung nhan goc cua bo ST_DEMO, khong doi ID hoac xoa chung tu/ton kho.
SET NAMES utf8mb4;
START TRANSACTION;

UPDATE nha_phan_phoi SET ten_npp = 'Nhà phân phối A&D'
WHERE ma_npp = 'ST_DEMO' AND ten_npp = 'NPP thử chuyển kho';
UPDATE nhan_vien SET ho_ten = 'Đỗ Văn Quang'
WHERE ma_nhan_vien = 'ST_DEMO_TK' AND ho_ten = 'Quang · Thủ kho thử';
UPDATE nhan_vien SET ho_ten = 'Nguyễn Văn An'
WHERE ma_nhan_vien = 'ST_DEMO_BH' AND ho_ten = 'Nhân viên bán hàng thử';
UPDATE kho k JOIN nha_phan_phoi n ON n.nha_phan_phoi_id = k.nha_phan_phoi_id
SET k.ten_kho = 'Kho Tổng'
WHERE n.ma_npp = 'ST_DEMO' AND k.ma_kho = 'ST_TONG' AND k.ten_kho = 'Kho tổng thử';
UPDATE kho k JOIN nha_phan_phoi n ON n.nha_phan_phoi_id = k.nha_phan_phoi_id
SET k.ten_kho = 'Kho NVBH Nguyễn Văn An'
WHERE n.ma_npp = 'ST_DEMO' AND k.ma_kho = 'ST_NVBH' AND k.ten_kho = 'Kho NVBH thử';
UPDATE don_vi_tinh SET ten_don_vi = 'Đơn vị chuyển kho'
WHERE ma_don_vi = 'ST_CAI' AND ten_don_vi = 'Cái thử chuyển kho'
  AND NOT EXISTS (SELECT 1 FROM (SELECT ten_don_vi FROM don_vi_tinh) u WHERE u.ten_don_vi = 'Đơn vị chuyển kho');

-- Hai hang cu co the da duoc tham chieu boi phieu: giu ID va lich su, ngung cho lap phieu moi.
UPDATE hang_hoa SET ten_hang = 'Hàng chuyển kho A (ngừng sử dụng)', trang_thai = 'NGUNG_KINH_DOANH'
WHERE ma_hang = 'ST_DEMO_A' AND ten_hang = 'Hàng thử chuyển kho A';
UPDATE hang_hoa SET ten_hang = 'Hàng chuyển kho B (ngừng sử dụng)', trang_thai = 'NGUNG_KINH_DOANH'
WHERE ma_hang = 'ST_DEMO_B' AND ten_hang = 'Hàng thử chuyển kho B';

COMMIT;
