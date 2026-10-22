-- CSDL he thong quan ly ban hang NPP A&D
-- Phien ban 1.1 - da doi chieu bo sung voi QLBAN_HANG(1).xlsx
-- Luu file ma hoa UTF-8. Script khong DROP database/bang va co the import lai.

CREATE DATABASE IF NOT EXISTS `ptud_quan_ly_ban_hang`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ptud_quan_ly_ban_hang`;

SET NAMES utf8mb4;

-- ============================================================
-- 1. TO CHUC, NHAN SU, TAI KHOAN VA PHAN QUYEN
-- ============================================================
CREATE TABLE IF NOT EXISTS `nha_phan_phoi` (
  `nha_phan_phoi_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_npp` VARCHAR(20) NOT NULL UNIQUE,
  `ten_npp` VARCHAR(150) NOT NULL,
  `dia_chi` VARCHAR(255) NULL,
  `dien_thoai` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `trang_thai` ENUM('HOAT_DONG','NGUNG_HOAT_DONG') NOT NULL DEFAULT 'HOAT_DONG',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nhom_quyen` (
  `nhom_quyen_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_nhom_quyen` VARCHAR(30) NOT NULL UNIQUE,
  `ten_nhom_quyen` VARCHAR(100) NOT NULL,
  `mo_ta` VARCHAR(255) NULL,
  `trang_thai` ENUM('HOAT_DONG','NGUNG_HOAT_DONG') NOT NULL DEFAULT 'HOAT_DONG',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nhan_vien` (
  `nhan_vien_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nha_phan_phoi_id` INT UNSIGNED NOT NULL,
  `ma_nhan_vien` VARCHAR(20) NOT NULL UNIQUE,
  `ho_ten` VARCHAR(150) NOT NULL,
  `dia_chi` VARCHAR(255) NULL,
  `dien_thoai` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `ngay_vao_lam` DATE NULL,
  `trang_thai` ENUM('DANG_LAM_VIEC','TAM_NGHI','DA_NGHI_VIEC') NOT NULL DEFAULT 'DANG_LAM_VIEC',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_nhan_vien_npp` FOREIGN KEY (`nha_phan_phoi_id`) REFERENCES `nha_phan_phoi` (`nha_phan_phoi_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_nhan_vien_ho_ten` (`ho_ten`),
  INDEX `idx_nhan_vien_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tai_khoan` (
  `tai_khoan_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_nguoi_dung` VARCHAR(30) NOT NULL UNIQUE,
  `nhan_vien_id` INT UNSIGNED NOT NULL UNIQUE,
  `nhom_quyen_id` INT UNSIGNED NOT NULL,
  `ten_dang_nhap` VARCHAR(80) NOT NULL UNIQUE,
  `mat_khau_hash` VARCHAR(255) NOT NULL,
  `trang_thai` ENUM('HOAT_DONG','BI_KHOA','CHO_KICH_HOAT') NOT NULL DEFAULT 'HOAT_DONG',
  `lan_dang_nhap_cuoi` DATETIME NULL,
  `doi_mat_khau_lan_dau` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tai_khoan_nhan_vien` FOREIGN KEY (`nhan_vien_id`) REFERENCES `nhan_vien` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_tai_khoan_nhom_quyen` FOREIGN KEY (`nhom_quyen_id`) REFERENCES `nhom_quyen` (`nhom_quyen_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_tai_khoan_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nhan_vien_ban_hang` (
  `nhan_vien_id` INT UNSIGNED PRIMARY KEY,
  `han_muc_nhan_hang` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_nvbh_nhan_vien` FOREIGN KEY (`nhan_vien_id`) REFERENCES `nhan_vien` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `khu_vuc` (
  `khu_vuc_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nha_phan_phoi_id` INT UNSIGNED NOT NULL,
  `ma_khu_vuc` VARCHAR(30) NOT NULL,
  `ten_khu_vuc` VARCHAR(120) NOT NULL,
  `mo_ta` VARCHAR(255) NULL,
  `trang_thai` ENUM('HOAT_DONG','NGUNG_HOAT_DONG') NOT NULL DEFAULT 'HOAT_DONG',
  CONSTRAINT `fk_khu_vuc_npp` FOREIGN KEY (`nha_phan_phoi_id`) REFERENCES `nha_phan_phoi` (`nha_phan_phoi_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY `uq_khu_vuc_npp_ma` (`nha_phan_phoi_id`,`ma_khu_vuc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phan_cong_khu_vuc` (
  `phan_cong_khu_vuc_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nhan_vien_id` INT UNSIGNED NOT NULL,
  `khu_vuc_id` INT UNSIGNED NOT NULL,
  `ngay_bat_dau` DATE NOT NULL,
  `ngay_ket_thuc` DATE NULL,
  `nguoi_phan_cong_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu_ban_giao` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_phan_cong_nvbh` FOREIGN KEY (`nhan_vien_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phan_cong_khu_vuc` FOREIGN KEY (`khu_vuc_id`) REFERENCES `khu_vuc` (`khu_vuc_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phan_cong_tai_khoan` FOREIGN KEY (`nguoi_phan_cong_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phan_cong_nv_ngay` (`nhan_vien_id`,`ngay_bat_dau`,`ngay_ket_thuc`),
  INDEX `idx_phan_cong_kv_ngay` (`khu_vuc_id`,`ngay_bat_dau`,`ngay_ket_thuc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. DANH MUC DOI TAC, HANG HOA, DON VI VA KHO
-- ============================================================
CREATE TABLE IF NOT EXISTS `khach_hang` (
  `khach_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_khach_hang` VARCHAR(20) NOT NULL UNIQUE,
  `ten_khach_hang` VARCHAR(180) NOT NULL,
  `dia_chi` VARCHAR(255) NULL,
  `dien_thoai` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `khu_vuc_id` INT UNSIGNED NULL,
  `nhan_vien_phu_trach_id` INT UNSIGNED NULL,
  `han_muc_no` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `so_ngay_no` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `loai_khach_hang` VARCHAR(50) NULL,
  `ghi_chu` TEXT NULL,
  `trang_thai` ENUM('HOAT_DONG','TAM_KHOA_NO','NGUNG_HOAT_DONG') NOT NULL DEFAULT 'HOAT_DONG',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_khach_hang_khu_vuc` FOREIGN KEY (`khu_vuc_id`) REFERENCES `khu_vuc` (`khu_vuc_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_khach_hang_nvbh` FOREIGN KEY (`nhan_vien_phu_trach_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_khach_hang_ten` (`ten_khach_hang`),
  INDEX `idx_khach_hang_nvbh` (`nhan_vien_phu_trach_id`),
  INDEX `idx_khach_hang_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nha_cung_cap` (
  `nha_cung_cap_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_ncc` VARCHAR(20) NOT NULL UNIQUE,
  `ten_ncc` VARCHAR(180) NOT NULL,
  `dia_chi` VARCHAR(255) NULL,
  `dien_thoai` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `ma_so_thue` VARCHAR(30) NULL,
  `han_muc_no` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `ghi_chu` TEXT NULL,
  `trang_thai` ENUM('DANG_GIAO_DICH','TAM_NGUNG','NGUNG_GIAO_DICH') NOT NULL DEFAULT 'DANG_GIAO_DICH',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ncc_ten` (`ten_ncc`),
  INDEX `idx_ncc_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `don_vi_tinh` (
  `don_vi_tinh_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_don_vi` VARCHAR(20) NOT NULL UNIQUE,
  `ten_don_vi` VARCHAR(60) NOT NULL UNIQUE,
  `mo_ta` VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hang_hoa` (
  `hang_hoa_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_hang` VARCHAR(30) NOT NULL UNIQUE,
  `ten_hang` VARCHAR(180) NOT NULL,
  `chung_loai` VARCHAR(100) NULL,
  `don_vi_lon_id` INT UNSIGNED NULL,
  `don_vi_nho_id` INT UNSIGNED NOT NULL,
  `ty_le_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `gia_si` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `gia_le` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thue_suat` DECIMAL(7,4) NOT NULL DEFAULT 0 COMMENT 'Luu theo ty le thap phan: 0.1000 = 10%, 0.0800 = 8%',
  `trang_thai` ENUM('DANG_KINH_DOANH','HET_HANG','NGUNG_KINH_DOANH') NOT NULL DEFAULT 'DANG_KINH_DOANH',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hang_hoa_dvt_lon` FOREIGN KEY (`don_vi_lon_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_hang_hoa_dvt_nho` FOREIGN KEY (`don_vi_nho_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_hang_hoa_ten` (`ten_hang`),
  INDEX `idx_hang_hoa_chung_loai` (`chung_loai`),
  INDEX `idx_hang_hoa_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kho` (
  `kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nha_phan_phoi_id` INT UNSIGNED NOT NULL,
  `ma_kho` VARCHAR(30) NOT NULL,
  `ten_kho` VARCHAR(120) NOT NULL,
  `dia_diem` VARCHAR(255) NULL,
  `loai_kho` ENUM('KHO_TONG','KHO_CHI_NHANH','KHO_KY_GUI') NOT NULL,
  `nhan_vien_phu_trach_id` INT UNSIGNED NULL UNIQUE COMMENT 'Chi dung cho kho ky gui cua NVBH',
  `trang_thai` ENUM('HOAT_DONG','NGUNG_HOAT_DONG') NOT NULL DEFAULT 'HOAT_DONG',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kho_npp` FOREIGN KEY (`nha_phan_phoi_id`) REFERENCES `nha_phan_phoi` (`nha_phan_phoi_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_kho_nvbh` FOREIGN KEY (`nhan_vien_phu_trach_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY `uq_kho_npp_ma` (`nha_phan_phoi_id`,`ma_kho`),
  INDEX `idx_kho_loai` (`loai_kho`,`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_kho` (
  `kho_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `so_luong_ton` DECIMAL(15,3) NOT NULL DEFAULT 0 COMMENT 'Ton theo don vi nho/co so cua hang hoa',
  `so_luong_giu_cho` DECIMAL(15,3) NOT NULL DEFAULT 0 COMMENT 'Hang da duyet don nhung chua xuat',
  `cap_nhat_luc` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kho_id`,`hang_hoa_id`),
  CONSTRAINT `fk_ton_kho_kho` FOREIGN KEY (`kho_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ton_kho_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DANH MUC KHUYEN MAI (TAO TRUOC BANG DON BAN DE TAO FK)
-- ============================================================
CREATE TABLE IF NOT EXISTS `chuong_trinh_khuyen_mai` (
  `chuong_trinh_khuyen_mai_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_khuyen_mai` VARCHAR(30) NOT NULL UNIQUE,
  `ten_khuyen_mai` VARCHAR(150) NOT NULL,
  `loai_khuyen_mai` ENUM('GIAM_PHAN_TRAM','GIAM_TIEN','TANG_HANG') NOT NULL,
  `ngay_bat_dau` DATE NOT NULL,
  `ngay_ket_thuc` DATE NOT NULL,
  `trang_thai` ENUM('NHAP','DANG_AP_DUNG','KET_THUC','HUY') NOT NULL DEFAULT 'NHAP',
  `mo_ta` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_khuyen_mai_thoi_gian` (`ngay_bat_dau`,`ngay_ket_thuc`,`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_chuong_trinh_khuyen_mai` (
  `chi_tiet_khuyen_mai_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chuong_trinh_khuyen_mai_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `so_luong_toi_thieu` DECIMAL(15,3) NOT NULL DEFAULT 1,
  `so_luong_tang` DECIMAL(15,3) NOT NULL DEFAULT 0,
  `ty_le_giam` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `tien_giam` DECIMAL(15,2) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_ct_khuyen_mai_header` FOREIGN KEY (`chuong_trinh_khuyen_mai_id`) REFERENCES `chuong_trinh_khuyen_mai` (`chuong_trinh_khuyen_mai_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_khuyen_mai_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY `uq_ct_khuyen_mai_hang` (`chuong_trinh_khuyen_mai_id`,`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. KE HOACH NHAP HANG VA CHUNG TU NHAP TU NHA CUNG CAP
-- ============================================================
CREATE TABLE IF NOT EXISTS `ke_hoach_nhap_hang` (
  `ke_hoach_nhap_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_ke_hoach` VARCHAR(30) NOT NULL UNIQUE,
  `ngay_lap` DATE NOT NULL,
  `ngay_du_kien_nhap` DATE NULL,
  `trang_thai` ENUM('NHAP','CHO_DUYET','DA_DUYET','TU_CHOI','DA_CHUYEN_THANH_DON_NHAP','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_duyet_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ke_hoach_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ke_hoach_nguoi_duyet` FOREIGN KEY (`nguoi_duyet_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_ke_hoach_ngay_trang_thai` (`ngay_lap`,`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_ke_hoach_nhap_hang` (
  `chi_tiet_ke_hoach_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ke_hoach_nhap_hang_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `so_luong_du_kien` DECIMAL(15,3) NOT NULL,
  `don_vi_tinh_id` INT UNSIGNED NOT NULL,
  `he_so_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `don_gia_du_kien` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ctkh_ke_hoach` FOREIGN KEY (`ke_hoach_nhap_hang_id`) REFERENCES `ke_hoach_nhap_hang` (`ke_hoach_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ctkh_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ctkh_dvt` FOREIGN KEY (`don_vi_tinh_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ctkh_ke_hoach_hang` (`ke_hoach_nhap_hang_id`,`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `don_nhap_hang` (
  `don_nhap_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_don_nhap` VARCHAR(30) NOT NULL UNIQUE,
  `nha_cung_cap_id` INT UNSIGNED NOT NULL,
  `ke_hoach_nhap_hang_id` INT UNSIGNED NULL,
  `ngay_lap` DATE NOT NULL,
  `ngay_den_han` DATE NULL,
  `loai_thanh_toan` ENUM('TRA_NGAY','CONG_NO') NOT NULL DEFAULT 'CONG_NO',
  `tien_chiet_khau` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tien_khuyen_mai` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `hoa_hong` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tien_thue` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tong_thanh_toan` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Tong gia tri chung tu tai thoi diem ghi nhan',
  `trang_thai` ENUM('NHAP','DA_GHI_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_don_nhap_ncc` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`nha_cung_cap_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_don_nhap_ke_hoach` FOREIGN KEY (`ke_hoach_nhap_hang_id`) REFERENCES `ke_hoach_nhap_hang` (`ke_hoach_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_don_nhap_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_don_nhap_ncc_ngay` (`nha_cung_cap_id`,`ngay_lap`),
  INDEX `idx_don_nhap_han_trang_thai` (`ngay_den_han`,`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_don_nhap_hang` (
  `chi_tiet_don_nhap_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `don_nhap_hang_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `don_vi_tinh_id` INT UNSIGNED NOT NULL,
  `so_luong` DECIMAL(15,3) NOT NULL,
  `he_so_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `so_luong_khuyen_mai` DECIMAL(15,3) NOT NULL DEFAULT 0,
  `gia_nhap` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tien_khuyen_mai` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tien_chiet_khau` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thue_suat` DECIMAL(7,4) NOT NULL DEFAULT 0 COMMENT 'Ty le thap phan: 0.1000 = 10%' ,
  `tien_thue` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thanh_tien` DECIMAL(15,2) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_ct_don_nhap_header` FOREIGN KEY (`don_nhap_hang_id`) REFERENCES `don_nhap_hang` (`don_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_don_nhap_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_don_nhap_dvt` FOREIGN KEY (`don_vi_tinh_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_don_nhap_hang` (`don_nhap_hang_id`,`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. BAN HANG, XUAT KHO VA HOA DON BAN
-- ============================================================
CREATE TABLE IF NOT EXISTS `don_ban_hang` (
  `don_ban_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_don_hang` VARCHAR(30) NOT NULL UNIQUE,
  `ngay_lap` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `khach_hang_id` INT UNSIGNED NOT NULL,
  `nhan_vien_ban_hang_id` INT UNSIGNED NOT NULL,
  `kho_xuat_id` INT UNSIGNED NOT NULL,
  `loai_thanh_toan` ENUM('THU_NGAY','CONG_NO') NOT NULL DEFAULT 'CONG_NO',
  `trang_thai` ENUM('CHO_DUYET','DA_DUYET','TU_CHOI','HUY','DA_XUAT_KHO','DA_LAP_HOA_DON') NOT NULL DEFAULT 'CHO_DUYET',
  `nguoi_duyet_tai_khoan_id` INT UNSIGNED NULL,
  `duyet_luc` DATETIME NULL,
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_don_ban_khach_hang` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`khach_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_don_ban_nvbh` FOREIGN KEY (`nhan_vien_ban_hang_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_don_ban_kho` FOREIGN KEY (`kho_xuat_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_don_ban_nguoi_duyet` FOREIGN KEY (`nguoi_duyet_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_don_ban_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_don_ban_khach_ngay` (`khach_hang_id`,`ngay_lap`),
  INDEX `idx_don_ban_nvbh_ngay` (`nhan_vien_ban_hang_id`,`ngay_lap`),
  INDEX `idx_don_ban_trang_thai_ngay` (`trang_thai`,`ngay_lap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_don_ban_hang` (
  `chi_tiet_don_ban_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `don_ban_hang_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `don_vi_tinh_id` INT UNSIGNED NOT NULL,
  `so_luong_dat` DECIMAL(15,3) NOT NULL,
  `he_so_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `don_gia_du_kien` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `chiet_khau_du_kien` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `chuong_trinh_khuyen_mai_id` INT UNSIGNED NULL,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_don_ban_header` FOREIGN KEY (`don_ban_hang_id`) REFERENCES `don_ban_hang` (`don_ban_hang_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_don_ban_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_don_ban_dvt` FOREIGN KEY (`don_vi_tinh_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_don_ban_khuyen_mai` FOREIGN KEY (`chuong_trinh_khuyen_mai_id`) REFERENCES `chuong_trinh_khuyen_mai` (`chuong_trinh_khuyen_mai_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_ct_don_ban_hang` (`don_ban_hang_id`,`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phieu_xuat_kho` (
  `phieu_xuat_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_xuat` VARCHAR(30) NOT NULL UNIQUE,
  `don_ban_hang_id` INT UNSIGNED NOT NULL UNIQUE,
  `kho_xuat_id` INT UNSIGNED NOT NULL,
  `ngay_lap` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trang_thai` ENUM('NHAP','DA_XAC_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `xac_nhan_luc` DATETIME NULL,
  `ghi_chu` TEXT NULL,
  CONSTRAINT `fk_phieu_xuat_don_ban` FOREIGN KEY (`don_ban_hang_id`) REFERENCES `don_ban_hang` (`don_ban_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_xuat_kho` FOREIGN KEY (`kho_xuat_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_xuat_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_xuat_nguoi_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phieu_xuat_kho_ngay` (`kho_xuat_id`,`ngay_lap`),
  INDEX `idx_phieu_xuat_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_xuat_kho` (
  `chi_tiet_phieu_xuat_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_xuat_kho_id` INT UNSIGNED NOT NULL,
  `chi_tiet_don_ban_hang_id` INT UNSIGNED NOT NULL UNIQUE,
  `so_luong_thuc_xuat` DECIMAL(15,3) NOT NULL,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_phieu_xuat_header` FOREIGN KEY (`phieu_xuat_kho_id`) REFERENCES `phieu_xuat_kho` (`phieu_xuat_kho_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_phieu_xuat_ct_don` FOREIGN KEY (`chi_tiet_don_ban_hang_id`) REFERENCES `chi_tiet_don_ban_hang` (`chi_tiet_don_ban_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_phieu_xuat_header` (`phieu_xuat_kho_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hoa_don_ban` (
  `hoa_don_ban_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_hoa_don` VARCHAR(30) NOT NULL UNIQUE,
  `phieu_xuat_kho_id` INT UNSIGNED NOT NULL UNIQUE,
  `ngay_phat_hanh` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngay_den_han` DATE NULL,
  `tien_chiet_khau` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tien_thue` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `tong_thanh_toan` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Tong hoa don da chot; khong phai so du cong no',
  `trang_thai` ENUM('DA_PHAT_HANH','DA_HUY') NOT NULL DEFAULT 'DA_PHAT_HANH',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `ghi_chu` TEXT NULL,
  CONSTRAINT `fk_hoa_don_ban_phieu_xuat` FOREIGN KEY (`phieu_xuat_kho_id`) REFERENCES `phieu_xuat_kho` (`phieu_xuat_kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_hoa_don_ban_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_hoa_don_ban_ngay_han` (`ngay_den_han`,`trang_thai`),
  INDEX `idx_hoa_don_ban_ngay_phat_hanh` (`ngay_phat_hanh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_hoa_don_ban` (
  `chi_tiet_hoa_don_ban_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hoa_don_ban_id` INT UNSIGNED NOT NULL,
  `chi_tiet_phieu_xuat_kho_id` INT UNSIGNED NOT NULL UNIQUE,
  `don_gia_ap_dung` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `chiet_khau` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thue_suat` DECIMAL(7,4) NOT NULL DEFAULT 0 COMMENT 'Ty le thap phan: 0.1000 = 10%' ,
  `tien_thue` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thanh_tien` DECIMAL(15,2) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_ct_hoa_don_ban_header` FOREIGN KEY (`hoa_don_ban_id`) REFERENCES `hoa_don_ban` (`hoa_don_ban_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_hoa_don_ban_ct_xuat` FOREIGN KEY (`chi_tiet_phieu_xuat_kho_id`) REFERENCES `chi_tiet_phieu_xuat_kho` (`chi_tiet_phieu_xuat_kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_hoa_don_ban_header` (`hoa_don_ban_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TRA HANG VA NHAP KHO
-- ============================================================
CREATE TABLE IF NOT EXISTS `phieu_tra_hang` (
  `phieu_tra_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_tra` VARCHAR(30) NOT NULL UNIQUE,
  `hoa_don_ban_id` INT UNSIGNED NOT NULL,
  `kho_nhan_id` INT UNSIGNED NOT NULL,
  `ngay_yeu_cau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trang_thai` ENUM('CHO_KIEM_TRA','DA_XAC_NHAN','TU_CHOI','DA_NHAP_KHO','HOAN_TAT','HUY') NOT NULL DEFAULT 'CHO_KIEM_TRA',
  `phuong_an_tai_chinh` ENUM('CHO_XU_LY','GIAM_CONG_NO','HOAN_TIEN') NOT NULL DEFAULT 'CHO_XU_LY',
  `nguoi_tiep_nhan_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_phieu_tra_hoa_don` FOREIGN KEY (`hoa_don_ban_id`) REFERENCES `hoa_don_ban` (`hoa_don_ban_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_tra_kho` FOREIGN KEY (`kho_nhan_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_tra_tiep_nhan` FOREIGN KEY (`nguoi_tiep_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_tra_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phieu_tra_hoa_don` (`hoa_don_ban_id`),
  INDEX `idx_phieu_tra_trang_thai` (`trang_thai`,`ngay_yeu_cau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_tra_hang` (
  `chi_tiet_phieu_tra_hang_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_tra_hang_id` INT UNSIGNED NOT NULL,
  `chi_tiet_hoa_don_ban_id` INT UNSIGNED NOT NULL,
  `so_luong_tra` DECIMAL(15,3) NOT NULL,
  `tinh_trang_hang` ENUM('NGUYEN_VEN','HU_HONG','LOI','KHAC') NOT NULL DEFAULT 'NGUYEN_VEN',
  `so_tien_dieu_chinh` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_tra_header` FOREIGN KEY (`phieu_tra_hang_id`) REFERENCES `phieu_tra_hang` (`phieu_tra_hang_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_tra_ct_hoa_don` FOREIGN KEY (`chi_tiet_hoa_don_ban_id`) REFERENCES `chi_tiet_hoa_don_ban` (`chi_tiet_hoa_don_ban_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_tra_header` (`phieu_tra_hang_id`),
  INDEX `idx_ct_tra_ct_hoa_don` (`chi_tiet_hoa_don_ban_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phieu_nhap_kho` (
  `phieu_nhap_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_nhap` VARCHAR(30) NOT NULL UNIQUE,
  `kho_nhan_id` INT UNSIGNED NOT NULL,
  `loai_nhap` ENUM('MUA_HANG','KHACH_TRA','DIEU_CHINH') NOT NULL,
  `don_nhap_hang_id` INT UNSIGNED NULL,
  `phieu_tra_hang_id` INT UNSIGNED NULL UNIQUE,
  `ngay_nhap` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trang_thai` ENUM('NHAP','DA_XAC_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `xac_nhan_luc` DATETIME NULL,
  `ghi_chu` TEXT NULL,
  CONSTRAINT `fk_phieu_nhap_kho_nhan` FOREIGN KEY (`kho_nhan_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_nhap_don_nhap` FOREIGN KEY (`don_nhap_hang_id`) REFERENCES `don_nhap_hang` (`don_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_nhap_tra_hang` FOREIGN KEY (`phieu_tra_hang_id`) REFERENCES `phieu_tra_hang` (`phieu_tra_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_nhap_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_nhap_nguoi_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phieu_nhap_kho_ngay` (`kho_nhan_id`,`ngay_nhap`),
  INDEX `idx_phieu_nhap_trang_thai` (`trang_thai`,`ngay_nhap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_nhap_kho` (
  `chi_tiet_phieu_nhap_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_nhap_kho_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `chi_tiet_don_nhap_hang_id` INT UNSIGNED NULL,
  `chi_tiet_phieu_tra_hang_id` INT UNSIGNED NULL,
  `don_vi_tinh_id` INT UNSIGNED NOT NULL,
  `so_luong_thuc_nhap` DECIMAL(15,3) NOT NULL,
  `he_so_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `so_luong_khuyen_mai` DECIMAL(15,3) NOT NULL DEFAULT 0,
  `don_gia_ghi_nhan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thanh_tien` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Snapshot gia tri dong dieu chuyen',
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_phieu_nhap_header` FOREIGN KEY (`phieu_nhap_kho_id`) REFERENCES `phieu_nhap_kho` (`phieu_nhap_kho_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_phieu_nhap_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_phieu_nhap_ct_don` FOREIGN KEY (`chi_tiet_don_nhap_hang_id`) REFERENCES `chi_tiet_don_nhap_hang` (`chi_tiet_don_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_phieu_nhap_ct_tra` FOREIGN KEY (`chi_tiet_phieu_tra_hang_id`) REFERENCES `chi_tiet_phieu_tra_hang` (`chi_tiet_phieu_tra_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_phieu_nhap_dvt` FOREIGN KEY (`don_vi_tinh_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_phieu_nhap_header` (`phieu_nhap_kho_id`),
  INDEX `idx_ct_phieu_nhap_hang_hoa` (`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. DIEU CHUYEN KHO VA KIEM KE
-- ============================================================
CREATE TABLE IF NOT EXISTS `phieu_chuyen_kho` (
  `phieu_chuyen_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_chuyen_kho` VARCHAR(30) NOT NULL UNIQUE,
  `kho_xuat_id` INT UNSIGNED NOT NULL,
  `kho_nhap_id` INT UNSIGNED NOT NULL,
  `ngay_chuyen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tong_gia_tri` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Gia tri tham chieu cua hang dieu chuyen; khong phai doanh thu',
  `trang_thai` ENUM('NHAP','DA_XAC_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `xac_nhan_luc` DATETIME NULL,
  `ghi_chu` TEXT NULL,
  CONSTRAINT `fk_chuyen_kho_xuat` FOREIGN KEY (`kho_xuat_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_chuyen_kho_nhap` FOREIGN KEY (`kho_nhap_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_chuyen_kho_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_chuyen_kho_nguoi_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_chuyen_kho_ngay` (`ngay_chuyen`),
  INDEX `idx_chuyen_kho_xuat_nhap` (`kho_xuat_id`,`kho_nhap_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_chuyen_kho` (
  `chi_tiet_phieu_chuyen_kho_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_chuyen_kho_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `don_vi_tinh_id` INT UNSIGNED NOT NULL,
  `so_luong` DECIMAL(15,3) NOT NULL,
  `he_so_quy_doi` DECIMAL(12,4) NOT NULL DEFAULT 1,
  `don_gia_ghi_nhan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `thanh_tien` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Snapshot gia tri dong dieu chuyen',
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_chuyen_header` FOREIGN KEY (`phieu_chuyen_kho_id`) REFERENCES `phieu_chuyen_kho` (`phieu_chuyen_kho_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_chuyen_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ct_chuyen_dvt` FOREIGN KEY (`don_vi_tinh_id`) REFERENCES `don_vi_tinh` (`don_vi_tinh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_chuyen_header` (`phieu_chuyen_kho_id`),
  INDEX `idx_ct_chuyen_hang_hoa` (`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phieu_kiem_ke` (
  `phieu_kiem_ke_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_kiem_ke` VARCHAR(30) NOT NULL UNIQUE,
  `kho_id` INT UNSIGNED NOT NULL,
  `ngay_kiem_ke` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trang_thai` ENUM('NHAP','CHO_DUYET','DA_DUYET','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_duyet_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  CONSTRAINT `fk_kiem_ke_kho` FOREIGN KEY (`kho_id`) REFERENCES `kho` (`kho_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_kiem_ke_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_kiem_ke_nguoi_duyet` FOREIGN KEY (`nguoi_duyet_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_kiem_ke_kho_ngay` (`kho_id`,`ngay_kiem_ke`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_kiem_ke` (
  `chi_tiet_phieu_kiem_ke_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_kiem_ke_id` INT UNSIGNED NOT NULL,
  `hang_hoa_id` INT UNSIGNED NOT NULL,
  `so_luong_so_sach` DECIMAL(15,3) NOT NULL,
  `so_luong_thuc_te` DECIMAL(15,3) NOT NULL,
  `chenh_lech` DECIMAL(15,3) NOT NULL COMMENT 'Thuc te tru so sach, snapshot tai thoi diem kiem ke',
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_kiem_ke_header` FOREIGN KEY (`phieu_kiem_ke_id`) REFERENCES `phieu_kiem_ke` (`phieu_kiem_ke_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_kiem_ke_hang_hoa` FOREIGN KEY (`hang_hoa_id`) REFERENCES `hang_hoa` (`hang_hoa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY `uq_ct_kiem_ke_hang` (`phieu_kiem_ke_id`,`hang_hoa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. THU HO QUA NVBH, DOI SOAT VA PHIEU THU/CHI
-- ============================================================
CREATE TABLE IF NOT EXISTS `phieu_thu_nvbh` (
  `phieu_thu_nvbh_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_phieu_thu_nvbh` VARCHAR(30) NOT NULL UNIQUE,
  `nhan_vien_ban_hang_id` INT UNSIGNED NOT NULL,
  `khach_hang_id` INT UNSIGNED NOT NULL,
  `thoi_diem_thu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `so_tien_thuc_thu` DECIMAL(15,2) NOT NULL,
  `phuong_thuc` ENUM('TIEN_MAT','CHUYEN_KHOAN','KHAC') NOT NULL DEFAULT 'TIEN_MAT',
  `trang_thai` ENUM('CHO_NOP','DA_GAN_DOT_NOP','HUY') NOT NULL DEFAULT 'CHO_NOP',
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_thu_nvbh_nvbh` FOREIGN KEY (`nhan_vien_ban_hang_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_thu_nvbh_khach_hang` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`khach_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_thu_nvbh_nv_ngay` (`nhan_vien_ban_hang_id`,`thoi_diem_thu`),
  INDEX `idx_thu_nvbh_kh_ngay` (`khach_hang_id`,`thoi_diem_thu`),
  INDEX `idx_thu_nvbh_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dot_nop_tien_nvbh` (
  `dot_nop_tien_nvbh_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ma_dot_nop` VARCHAR(30) NOT NULL UNIQUE,
  `nhan_vien_ban_hang_id` INT UNSIGNED NOT NULL,
  `ngay_nop` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `so_tien_khai_bao` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `so_tien_thuc_nhan` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `trang_thai` ENUM('CHO_DOI_SOAT','DA_XAC_NHAN','LECH_SO_LIEU','TU_CHOI','HUY') NOT NULL DEFAULT 'CHO_DOI_SOAT',
  `nguoi_tiep_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_dot_nop_nvbh` FOREIGN KEY (`nhan_vien_ban_hang_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_dot_nop_tiep_nhan` FOREIGN KEY (`nguoi_tiep_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_dot_nop_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_dot_nop_nv_ngay` (`nhan_vien_ban_hang_id`,`ngay_nop`),
  INDEX `idx_dot_nop_trang_thai` (`trang_thai`,`ngay_nop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_dot_nop_tien_nvbh` (
  `chi_tiet_dot_nop_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `dot_nop_tien_nvbh_id` INT UNSIGNED NOT NULL,
  `phieu_thu_nvbh_id` INT UNSIGNED NOT NULL,
  `so_tien_doi_soat` DECIMAL(15,2) NOT NULL,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_dot_nop_header` FOREIGN KEY (`dot_nop_tien_nvbh_id`) REFERENCES `dot_nop_tien_nvbh` (`dot_nop_tien_nvbh_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_dot_nop_phieu_thu_nvbh` FOREIGN KEY (`phieu_thu_nvbh_id`) REFERENCES `phieu_thu_nvbh` (`phieu_thu_nvbh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_dot_nop_header` (`dot_nop_tien_nvbh_id`),
  INDEX `idx_ct_dot_nop_phieu_thu` (`phieu_thu_nvbh_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phieu_thu` (
  `phieu_thu_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_thu` VARCHAR(30) NOT NULL UNIQUE,
  `nguon_thu` ENUM('KHACH_HANG','NVBH_NOP_TIEN') NOT NULL,
  `khach_hang_id` INT UNSIGNED NULL COMMENT 'Bat buoc voi thu truc tiep; co the NULL khi NVBH nop tien gom nhieu khach',
  `nhan_vien_ban_hang_id` INT UNSIGNED NULL COMMENT 'NV ban hang gan voi phieu thu theo cot SalesPersonID cua Excel',
  `dot_nop_tien_nvbh_id` INT UNSIGNED NULL UNIQUE,
  `ngay_thu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phuong_thuc` ENUM('TIEN_MAT','CHUYEN_KHOAN','KHAC') NOT NULL DEFAULT 'TIEN_MAT',
  `so_tien_thu` DECIMAL(15,2) NOT NULL,
  `trang_thai` ENUM('NHAP','DA_XAC_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_phieu_thu_khach_hang` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`khach_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_thu_nvbh` FOREIGN KEY (`nhan_vien_ban_hang_id`) REFERENCES `nhan_vien_ban_hang` (`nhan_vien_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_phieu_thu_dot_nop` FOREIGN KEY (`dot_nop_tien_nvbh_id`) REFERENCES `dot_nop_tien_nvbh` (`dot_nop_tien_nvbh_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_thu_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_thu_nguoi_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phieu_thu_ngay_trang_thai` (`ngay_thu`,`trang_thai`),
  INDEX `idx_phieu_thu_khach_hang` (`khach_hang_id`),
  INDEX `idx_phieu_thu_nvbh` (`nhan_vien_ban_hang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_thu` (
  `chi_tiet_phieu_thu_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_thu_id` INT UNSIGNED NOT NULL,
  `hoa_don_ban_id` INT UNSIGNED NOT NULL,
  `so_tien_phan_bo` DECIMAL(15,2) NOT NULL,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_phieu_thu_header` FOREIGN KEY (`phieu_thu_id`) REFERENCES `phieu_thu` (`phieu_thu_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_phieu_thu_hoa_don` FOREIGN KEY (`hoa_don_ban_id`) REFERENCES `hoa_don_ban` (`hoa_don_ban_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_phieu_thu_header` (`phieu_thu_id`),
  INDEX `idx_ct_phieu_thu_hoa_don` (`hoa_don_ban_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phieu_chi` (
  `phieu_chi_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `so_phieu_chi` VARCHAR(30) NOT NULL UNIQUE,
  `loai_chi` ENUM('THANH_TOAN_NCC','HOAN_TIEN_KHACH','KHAC') NOT NULL,
  `nha_cung_cap_id` INT UNSIGNED NULL,
  `phieu_tra_hang_id` INT UNSIGNED NULL UNIQUE,
  `ngay_chi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phuong_thuc` ENUM('TIEN_MAT','CHUYEN_KHOAN','KHAC') NOT NULL DEFAULT 'TIEN_MAT',
  `so_tien_chi` DECIMAL(15,2) NOT NULL,
  `trang_thai` ENUM('NHAP','DA_XAC_NHAN','HUY') NOT NULL DEFAULT 'NHAP',
  `nguoi_lap_tai_khoan_id` INT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_tai_khoan_id` INT UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_phieu_chi_ncc` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`nha_cung_cap_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_chi_tra_hang` FOREIGN KEY (`phieu_tra_hang_id`) REFERENCES `phieu_tra_hang` (`phieu_tra_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_chi_nguoi_lap` FOREIGN KEY (`nguoi_lap_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_phieu_chi_nguoi_xac_nhan` FOREIGN KEY (`nguoi_xac_nhan_tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_phieu_chi_ncc_ngay` (`nha_cung_cap_id`,`ngay_chi`),
  INDEX `idx_phieu_chi_trang_thai` (`trang_thai`,`ngay_chi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chi_tiet_phieu_chi` (
  `chi_tiet_phieu_chi_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phieu_chi_id` INT UNSIGNED NOT NULL,
  `don_nhap_hang_id` INT UNSIGNED NOT NULL,
  `so_tien_phan_bo` DECIMAL(15,2) NOT NULL,
  `ghi_chu` VARCHAR(255) NULL,
  CONSTRAINT `fk_ct_phieu_chi_header` FOREIGN KEY (`phieu_chi_id`) REFERENCES `phieu_chi` (`phieu_chi_id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ct_phieu_chi_don_nhap` FOREIGN KEY (`don_nhap_hang_id`) REFERENCES `don_nhap_hang` (`don_nhap_hang_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX `idx_ct_phieu_chi_header` (`phieu_chi_id`),
  INDEX `idx_ct_phieu_chi_don_nhap` (`don_nhap_hang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. KHUYEN MAI VA NHAT KY KIEM SOAT
-- ============================================================
CREATE TABLE IF NOT EXISTS `nhat_ky_he_thong` (
  `nhat_ky_he_thong_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tai_khoan_id` INT UNSIGNED NULL,
  `hanh_dong` VARCHAR(50) NOT NULL,
  `ten_bang` VARCHAR(100) NOT NULL,
  `ma_ban_ghi` VARCHAR(50) NULL,
  `gia_tri_truoc` LONGTEXT NULL COMMENT 'JSON string; co the NULL neu them moi',
  `gia_tri_sau` LONGTEXT NULL COMMENT 'JSON string; co the NULL neu xoa',
  `dia_chi_ip` VARCHAR(45) NULL,
  `thoi_diem` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_nhat_ky_tai_khoan` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoan` (`tai_khoan_id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_nhat_ky_tai_khoan_thoi_diem` (`tai_khoan_id`,`thoi_diem`),
  INDEX `idx_nhat_ky_bang_ban_ghi` (`ten_bang`,`ma_ban_ghi`),
  INDEX `idx_nhat_ky_thoi_diem` (`thoi_diem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Luu y nghiep vu:
-- 1. Moi thao tac xac nhan chung tu phai cap nhat ton_kho trong cung transaction.
-- 2. So no khach hang/NCC va so da thu/da chi la gia tri tong hop tu hoa don + phan bo
--    phieu thu/chi + chung tu tra hang; khong luu lai thanh cot du tren danh muc.
-- 3. Khong xoa vat ly chung tu da xac nhan; dung trang_thai va nhat_ky_he_thong.
-- 4. Don vi co so cua ton kho la don_vi_nho cua hang hoa; quy doi dung he_so_quy_doi.
