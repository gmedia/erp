# Database Design - Modul Purchasing ERP

## Overview
Design database ini mencakup alur lengkap proses purchasing dari Purchase Request (PR), Purchase Order (PO), hingga penerimaan barang (Goods Receipt/GR).

---

## 1. Master Data Tables

### 1.1 suppliers
Tabel master supplier/vendor
```sql
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_name VARCHAR(200) NOT NULL,
    supplier_type ENUM('local', 'international') DEFAULT 'local',
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    country VARCHAR(50) DEFAULT 'Indonesia',
    tax_id VARCHAR(50), -- NPWP
    payment_term_days INT DEFAULT 30,
    currency_code VARCHAR(3) DEFAULT 'IDR',
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(100),
    rating DECIMAL(3,2), -- Rating supplier (1.00 - 5.00)
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_supplier_name (supplier_name),
    INDEX idx_is_active (is_active)
);
```

### 1.2 items
Tabel master barang/item
```sql
CREATE TABLE items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    item_description TEXT,
    category_id INT,
    unit_of_measure VARCHAR(20) NOT NULL, -- PCS, KG, M, BOX, dll
    item_type ENUM('raw_material', 'finished_good', 'spare_part', 'consumable') NOT NULL,
    minimum_stock DECIMAL(15,2) DEFAULT 0,
    maximum_stock DECIMAL(15,2) DEFAULT 0,
    reorder_point DECIMAL(15,2) DEFAULT 0,
    standard_price DECIMAL(15,2) DEFAULT 0,
    last_purchase_price DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_code (item_code),
    INDEX idx_item_name (item_name),
    INDEX idx_category (category_id),
    INDEX idx_is_active (is_active)
);
```

### 1.3 item_categories
Tabel kategori barang
```sql
CREATE TABLE item_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_code VARCHAR(20) UNIQUE NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    parent_category_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_category_id) REFERENCES item_categories(category_id)
);
```

### 1.4 warehouses
Tabel master gudang
```sql
CREATE TABLE warehouses (
    warehouse_id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_code VARCHAR(20) UNIQUE NOT NULL,
    warehouse_name VARCHAR(100) NOT NULL,
    warehouse_type ENUM('main', 'transit', 'production', 'retail') DEFAULT 'main',
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    pic_name VARCHAR(100),
    pic_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_warehouse_code (warehouse_code)
);
```

---

## 2. Purchase Request (PR) Tables

### 2.1 purchase_requests
Tabel header Purchase Request
```sql
CREATE TABLE purchase_requests (
    pr_id INT PRIMARY KEY AUTO_INCREMENT,
    pr_number VARCHAR(50) UNIQUE NOT NULL,
    pr_date DATE NOT NULL,
    request_department_id INT,
    requester_id INT NOT NULL,
    pr_type ENUM('standard', 'urgent', 'stock_replenishment', 'project') DEFAULT 'standard',
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    required_date DATE NOT NULL,
    warehouse_id INT,
    purpose TEXT,
    project_code VARCHAR(50),
    cost_center_code VARCHAR(50),
    total_estimated_amount DECIMAL(15,2) DEFAULT 0,
    currency_code VARCHAR(3) DEFAULT 'IDR',
    status ENUM('draft', 'submitted', 'approved', 'rejected', 'partially_ordered', 'fully_ordered', 'cancelled') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_by INT NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    INDEX idx_pr_number (pr_number),
    INDEX idx_pr_date (pr_date),
    INDEX idx_status (status),
    INDEX idx_requester (requester_id),
    INDEX idx_required_date (required_date)
);
```

### 2.2 purchase_request_items
Tabel detail item Purchase Request
```sql
CREATE TABLE purchase_request_items (
    pr_item_id INT PRIMARY KEY AUTO_INCREMENT,
    pr_id INT NOT NULL,
    item_id INT NOT NULL,
    item_description TEXT,
    quantity_requested DECIMAL(15,2) NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL,
    estimated_unit_price DECIMAL(15,2) DEFAULT 0,
    estimated_total_price DECIMAL(15,2) DEFAULT 0,
    quantity_ordered DECIMAL(15,2) DEFAULT 0, -- Quantity yang sudah di-PO
    quantity_outstanding DECIMAL(15,2) DEFAULT 0, -- Sisa yang belum di-PO
    specification TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pr_id) REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    INDEX idx_pr_id (pr_id),
    INDEX idx_item_id (item_id)
);
```

### 2.3 pr_approval_history
Tabel riwayat approval PR
```sql
CREATE TABLE pr_approval_history (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    pr_id INT NOT NULL,
    approval_level INT NOT NULL,
    approver_id INT NOT NULL,
    action ENUM('approved', 'rejected') NOT NULL,
    comments TEXT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pr_id) REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    INDEX idx_pr_id (pr_id),
    INDEX idx_approver (approver_id)
);
```

---

## 3. Purchase Order (PO) Tables

### 3.1 purchase_orders
Tabel header Purchase Order
```sql
CREATE TABLE purchase_orders (
    po_id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    po_date DATE NOT NULL,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    buyer_id INT NOT NULL,
    po_type ENUM('standard', 'blanket', 'contract', 'planned') DEFAULT 'standard',
    payment_term_days INT DEFAULT 30,
    payment_method ENUM('cash', 'transfer', 'credit', 'giro') DEFAULT 'transfer',
    delivery_address TEXT,
    delivery_date DATE,
    currency_code VARCHAR(3) DEFAULT 'IDR',
    exchange_rate DECIMAL(15,4) DEFAULT 1.0000,
    subtotal DECIMAL(15,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 11.00, -- PPN 11%
    tax_amount DECIMAL(15,2) DEFAULT 0,
    freight_cost DECIMAL(15,2) DEFAULT 0,
    other_cost DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'submitted', 'approved', 'sent_to_supplier', 'partially_received', 'fully_received', 'cancelled', 'closed') DEFAULT 'draft',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    sent_to_supplier_at TIMESTAMP NULL,
    supplier_confirmation_date DATE NULL,
    supplier_confirmation_number VARCHAR(50),
    terms_and_conditions TEXT,
    internal_notes TEXT,
    supplier_notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    INDEX idx_po_number (po_number),
    INDEX idx_po_date (po_date),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status),
    INDEX idx_delivery_date (delivery_date)
);
```

### 3.2 purchase_order_items
Tabel detail item Purchase Order
```sql
CREATE TABLE purchase_order_items (
    po_item_id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    pr_item_id INT NULL, -- Referensi ke PR Item jika ada
    item_id INT NOT NULL,
    item_description TEXT,
    quantity_ordered DECIMAL(15,2) NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 11.00,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_price DECIMAL(15,2) NOT NULL,
    quantity_received DECIMAL(15,2) DEFAULT 0, -- Quantity yang sudah diterima
    quantity_outstanding DECIMAL(15,2) DEFAULT 0, -- Sisa yang belum diterima
    promised_delivery_date DATE,
    specification TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (pr_item_id) REFERENCES purchase_request_items(pr_item_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    INDEX idx_po_id (po_id),
    INDEX idx_pr_item_id (pr_item_id),
    INDEX idx_item_id (item_id)
);
```

### 3.3 po_approval_history
Tabel riwayat approval PO
```sql
CREATE TABLE po_approval_history (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    approval_level INT NOT NULL,
    approver_id INT NOT NULL,
    action ENUM('approved', 'rejected') NOT NULL,
    comments TEXT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    INDEX idx_po_id (po_id),
    INDEX idx_approver (approver_id)
);
```

---

## 4. Goods Receipt (GR) Tables

### 4.1 goods_receipts
Tabel header penerimaan barang
```sql
CREATE TABLE goods_receipts (
    gr_id INT PRIMARY KEY AUTO_INCREMENT,
    gr_number VARCHAR(50) UNIQUE NOT NULL,
    gr_date DATE NOT NULL,
    po_id INT NOT NULL,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    delivery_note_number VARCHAR(50), -- Nomor surat jalan supplier
    delivery_note_date DATE,
    vehicle_number VARCHAR(20),
    driver_name VARCHAR(100),
    received_by INT NOT NULL,
    inspected_by INT,
    receipt_type ENUM('full', 'partial', 'return') DEFAULT 'partial',
    total_quantity_received DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'inspected', 'approved', 'posted', 'cancelled') DEFAULT 'draft',
    inspection_status ENUM('pending', 'passed', 'failed', 'partial') DEFAULT 'pending',
    inspection_notes TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    posted_to_inventory_at TIMESTAMP NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    INDEX idx_gr_number (gr_number),
    INDEX idx_gr_date (gr_date),
    INDEX idx_po_id (po_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status)
);
```

### 4.2 goods_receipt_items
Tabel detail item penerimaan barang
```sql
CREATE TABLE goods_receipt_items (
    gr_item_id INT PRIMARY KEY AUTO_INCREMENT,
    gr_id INT NOT NULL,
    po_item_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_ordered DECIMAL(15,2) NOT NULL, -- Dari PO
    quantity_received DECIMAL(15,2) NOT NULL, -- Yang diterima kali ini
    quantity_accepted DECIMAL(15,2) DEFAULT 0, -- Yang diterima dengan baik
    quantity_rejected DECIMAL(15,2) DEFAULT 0, -- Yang ditolak/rusak
    unit_of_measure VARCHAR(20) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_value DECIMAL(15,2) NOT NULL,
    batch_number VARCHAR(50),
    serial_number VARCHAR(50),
    manufacturing_date DATE,
    expiry_date DATE,
    quality_status ENUM('passed', 'failed', 'pending', 'conditional') DEFAULT 'pending',
    rejection_reason TEXT,
    storage_location VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gr_id) REFERENCES goods_receipts(gr_id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES purchase_order_items(po_item_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    INDEX idx_gr_id (gr_id),
    INDEX idx_po_item_id (po_item_id),
    INDEX idx_item_id (item_id),
    INDEX idx_batch_number (batch_number)
);
```

### 4.3 gr_quality_inspections
Tabel hasil inspeksi kualitas barang
```sql
CREATE TABLE gr_quality_inspections (
    inspection_id INT PRIMARY KEY AUTO_INCREMENT,
    gr_item_id INT NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    inspection_criteria VARCHAR(200),
    inspection_result ENUM('pass', 'fail', 'conditional') NOT NULL,
    measured_value VARCHAR(100),
    expected_value VARCHAR(100),
    deviation VARCHAR(100),
    comments TEXT,
    attachments JSON, -- Array of file paths
    FOREIGN KEY (gr_item_id) REFERENCES goods_receipt_items(gr_item_id) ON DELETE CASCADE,
    INDEX idx_gr_item_id (gr_item_id),
    INDEX idx_inspector (inspector_id)
);
```

---

## 5. Supporting Tables

### 5.1 pr_po_mapping
Tabel mapping antara PR dan PO (many-to-many)
```sql
CREATE TABLE pr_po_mapping (
    mapping_id INT PRIMARY KEY AUTO_INCREMENT,
    pr_id INT NOT NULL,
    po_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pr_id) REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    UNIQUE KEY unique_pr_po (pr_id, po_id),
    INDEX idx_pr_id (pr_id),
    INDEX idx_po_id (po_id)
);
```

### 5.2 document_attachments
Tabel untuk menyimpan attachment dokumen
```sql
CREATE TABLE document_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    document_type ENUM('PR', 'PO', 'GR') NOT NULL,
    document_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    description TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document (document_type, document_id)
);
```

### 5.3 purchasing_settings
Tabel konfigurasi modul purchasing
```sql
CREATE TABLE purchasing_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50),
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contoh data settings:
-- pr_auto_numbering: TRUE/FALSE
-- pr_number_format: PR-YYYYMM-####
-- po_auto_numbering: TRUE/FALSE
-- po_number_format: PO-YYYYMM-####
-- gr_auto_numbering: TRUE/FALSE
-- gr_number_format: GR-YYYYMM-####
-- pr_approval_required: TRUE/FALSE
-- po_approval_required: TRUE/FALSE
-- default_currency: IDR
-- default_tax_percent: 11.00
```

---

## 6. Workflow & Business Rules

### 6.1 Purchase Request Flow
```
1. Draft → Submitted → Approved/Rejected
2. Approved PR dapat di-convert menjadi PO (1 PR bisa jadi beberapa PO)
3. Status PR berubah menjadi:
   - partially_ordered: sebagian item sudah di-PO
   - fully_ordered: semua item sudah di-PO
```

### 6.2 Purchase Order Flow
```
1. Draft → Submitted → Approved → Sent to Supplier
2. Supplier memberikan konfirmasi
3. Status PO berubah berdasarkan penerimaan barang:
   - partially_received: sebagian barang diterima
   - fully_received: semua barang diterima
   - closed: PO ditutup (manual/otomatis)
```

### 6.3 Goods Receipt Flow
```
1. Draft → Inspected → Approved → Posted to Inventory
2. GR harus mengacu ke PO
3. Quantity received update PO item
4. Status inspection menentukan apakah barang bisa masuk inventory
5. Posted to Inventory: update stock di warehouse
```

---

## 7. Key Indexes & Performance

### 7.1 Composite Indexes
```sql
-- PR Performance
CREATE INDEX idx_pr_status_date ON purchase_requests(status, pr_date);
CREATE INDEX idx_pr_requester_status ON purchase_requests(requester_id, status);

-- PO Performance
CREATE INDEX idx_po_supplier_date ON purchase_orders(supplier_id, po_date);
CREATE INDEX idx_po_status_date ON purchase_orders(status, po_date);
CREATE INDEX idx_po_warehouse_status ON purchase_orders(warehouse_id, status);

-- GR Performance
CREATE INDEX idx_gr_po_status ON goods_receipts(po_id, status);
CREATE INDEX idx_gr_warehouse_date ON goods_receipts(warehouse_id, gr_date);
```

---

## 8. Sample Queries

### 8.1 Outstanding PR (belum di-PO)
```sql
SELECT 
    pr.pr_number,
    pr.pr_date,
    pr.required_date,
    pr.requester_id,
    i.item_code,
    i.item_name,
    pri.quantity_requested,
    pri.quantity_ordered,
    pri.quantity_outstanding
FROM purchase_requests pr
JOIN purchase_request_items pri ON pr.pr_id = pri.pr_id
JOIN items i ON pri.item_id = i.item_id
WHERE pr.status IN ('approved', 'partially_ordered')
  AND pri.quantity_outstanding > 0
ORDER BY pr.required_date ASC;
```

### 8.2 Outstanding PO (belum diterima penuh)
```sql
SELECT 
    po.po_number,
    po.po_date,
    po.delivery_date,
    s.supplier_name,
    i.item_code,
    i.item_name,
    poi.quantity_ordered,
    poi.quantity_received,
    poi.quantity_outstanding
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.supplier_id
JOIN purchase_order_items poi ON po.po_id = poi.po_id
JOIN items i ON poi.item_id = i.item_id
WHERE po.status IN ('sent_to_supplier', 'partially_received')
  AND poi.quantity_outstanding > 0
ORDER BY po.delivery_date ASC;
```

### 8.3 Goods Receipt Summary by Supplier
```sql
SELECT 
    s.supplier_name,
    COUNT(DISTINCT gr.gr_id) as total_receipts,
    SUM(gri.quantity_received) as total_qty_received,
    SUM(gri.quantity_accepted) as total_qty_accepted,
    SUM(gri.quantity_rejected) as total_qty_rejected,
    ROUND((SUM(gri.quantity_rejected) / SUM(gri.quantity_received) * 100), 2) as rejection_rate
FROM goods_receipts gr
JOIN suppliers s ON gr.supplier_id = s.supplier_id
JOIN goods_receipt_items gri ON gr.gr_id = gri.gr_id
WHERE gr.gr_date BETWEEN '2025-01-01' AND '2025-12-31'
GROUP BY s.supplier_id, s.supplier_name
ORDER BY rejection_rate DESC;
```

---

## 9. Notes & Recommendations

### 9.1 Audit Trail
- Semua tabel utama memiliki `created_by`, `created_at`, `updated_by`, `updated_at`
- Pertimbangkan untuk menambahkan tabel `audit_log` untuk tracking perubahan detail

### 9.2 Number Generation
- Gunakan stored procedure atau application logic untuk generate nomor dokumen
- Format: PREFIX-YYYYMM-SEQUENCE (contoh: PR-202501-0001)

### 9.3 Currency Handling
- Simpan exchange rate di PO untuk historical accuracy
- Semua amount dalam currency PO, bisa dikonversi ke base currency untuk reporting

### 9.4 Integration Points
- **Inventory**: GR posted → update stock
- **Finance**: PO approved → budget check, GR posted → create payable
- **Accounting**: Accrual entries saat GR posted

### 9.5 Performance Optimization
- Partition tabel berdasarkan tahun untuk tabel transaksi besar
- Archive data lama ke tabel history
- Regular ANALYZE TABLE untuk update statistics

### 9.6 Security
- Implement row-level security berdasarkan department/cost center
- Approval limit berdasarkan total amount PO
- Audit log untuk semua perubahan status

---

## 10. ERD Relationship Summary

```
suppliers (1) ----< (N) purchase_orders
items (1) ----< (N) purchase_request_items
items (1) ----< (N) purchase_order_items
items (1) ----< (N) goods_receipt_items
warehouses (1) ----< (N) purchase_requests
warehouses (1) ----< (N) purchase_orders
warehouses (1) ----< (N) goods_receipts
purchase_requests (1) ----< (N) purchase_request_items
purchase_orders (1) ----< (N) purchase_order_items
goods_receipts (1) ----< (N) goods_receipt_items
purchase_request_items (1) ----< (N) purchase_order_items
purchase_order_items (1) ----< (N) goods_receipt_items
purchase_requests (N) ----< (N) purchase_orders (via pr_po_mapping)
```

---

**Version**: 1.0  
**Last Updated**: February 2026  
**Database**: MySQL 8.0+ / MariaDB 10.5+
