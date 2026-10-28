-- CHI DUOC dung trong database test rieng do tests/stock-transfer.php tao.
INSERT INTO nha_phan_phoi (ma_npp,ten_npp) VALUES ('TEST_A','Test A'),('TEST_B','Test B');
INSERT INTO nhom_quyen (ma_nhom_quyen,ten_nhom_quyen) VALUES ('WAREHOUSE','Thủ kho'),('SALES','Bán hàng');
INSERT INTO nhan_vien (nha_phan_phoi_id,ma_nhan_vien,ho_ten) VALUES
 (1,'NV01','Thủ kho thử'),(1,'NV02','NVBH thử'),(2,'NV03','NVBH khác NPP'),(2,'NV04','Thủ kho NPP khác');
INSERT INTO tai_khoan (ma_nguoi_dung,nhan_vien_id,nhom_quyen_id,ten_dang_nhap,mat_khau_hash) VALUES
 ('TK01',1,1,'test_warehouse','not-a-login-hash'),('TK02',2,2,'test_sales','not-a-login-hash'),
 ('TK03',4,1,'test_other_warehouse','not-a-login-hash');
INSERT INTO nhan_vien_ban_hang (nhan_vien_id) VALUES (2),(3);
INSERT INTO kho (nha_phan_phoi_id,ma_kho,ten_kho,loai_kho,nhan_vien_phu_trach_id) VALUES
 (1,'TONG','Kho Tổng','KHO_TONG',NULL),(1,'NVBH','Kho NVBH thử','KHO_KY_GUI',2),
 (2,'NVBH','Kho NPP khác','KHO_KY_GUI',3),(1,'CN','Kho chi nhánh','KHO_CHI_NHANH',NULL);
INSERT INTO don_vi_tinh (ma_don_vi,ten_don_vi) VALUES ('LON','Lon');
INSERT INTO hang_hoa (ma_hang,ten_hang,don_vi_nho_id) VALUES ('H01','Hàng thử A',1),('H02','Hàng thử B',1);
INSERT INTO ton_kho (kho_id,hang_hoa_id,so_luong_ton,so_luong_giu_cho) VALUES
 (1,1,100,10),(1,2,50,0),(2,1,5,0);
