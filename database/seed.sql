-- Du lieu nen cho ptud_quan_ly_ban_hang
-- Khong tao tai khoan dang nhap mau vi can mat khau da bam hash an toan.
USE `ptud_quan_ly_ban_hang`;
SET NAMES utf8mb4;

INSERT INTO `nha_phan_phoi` (`ma_npp`,`ten_npp`,`dia_chi`,`dien_thoai`,`email`,`trang_thai`)
VALUES ('NPP001','Nhà phân phối A&D','123 Đường số 1, TP. Hồ Chí Minh',NULL,NULL,'HOAT_DONG')
ON DUPLICATE KEY UPDATE `ten_npp`=VALUES(`ten_npp`);

INSERT INTO `nhom_quyen` (`ma_nhom_quyen`,`ten_nhom_quyen`,`mo_ta`) VALUES
('ADMIN','Quản trị hệ thống','Quản lý tài khoản và nhóm quyền'),
('SALES','Nhân viên bán hàng','Lập đơn bán, ghi nhận thu hộ và theo dõi khách hàng được phân công'),
('ACCOUNT','Kế toán','Quản lý chứng từ tài chính, hóa đơn và công nợ'),
('WAREHOUSE','Thủ kho','Quản lý nhập, xuất, chuyển kho và kiểm kê'),
('MANAGER','Chủ nhà phân phối','Quản lý nhân sự, khu vực và xem báo cáo')
ON DUPLICATE KEY UPDATE `ten_nhom_quyen`=VALUES(`ten_nhom_quyen`), `mo_ta`=VALUES(`mo_ta`);

INSERT INTO `don_vi_tinh` (`ma_don_vi`,`ten_don_vi`,`mo_ta`) VALUES
('THU','Thùng','Đơn vị đóng gói lớn'),
('HOP','Hộp','Đơn vị nhỏ'),
('VI','Vỉ','Đơn vị nhỏ'),
('LON','Lon','Đơn vị nhỏ'),
('GOI','Gói','Đơn vị nhỏ'),
('KIE','Kiện','Đơn vị đóng gói lớn'),
('TUI','Túi','Đơn vị nhỏ'),
('BLO','Lốc','Đơn vị trung gian'),
('CHA','Chai','Đơn vị nhỏ')
ON DUPLICATE KEY UPDATE `ten_don_vi`=VALUES(`ten_don_vi`), `mo_ta`=VALUES(`mo_ta`);

INSERT INTO `kho` (`nha_phan_phoi_id`,`ma_kho`,`ten_kho`,`dia_diem`,`loai_kho`,`trang_thai`)
SELECT npp.`nha_phan_phoi_id`, 'KHO_TONG', 'Kho Tổng', 'Kho chính nhà phân phối', 'KHO_TONG', 'HOAT_DONG'
FROM `nha_phan_phoi` npp
WHERE npp.`ma_npp`='NPP001'
  AND NOT EXISTS (SELECT 1 FROM `kho` k WHERE k.`nha_phan_phoi_id`=npp.`nha_phan_phoi_id` AND k.`ma_kho`='KHO_TONG');
