# Struktur Menu & Halaman - Modul Purchasing ERP

## Overview
Dokumen ini berisi rekomendasi lengkap menu, halaman, dan fitur yang perlu dibuat untuk modul Purchasing ERP, termasuk user flow dan fungsi-fungsi yang diperlukan.

---

## 1. MENU UTAMA PURCHASING

### 📊 Dashboard
```
└─ Purchasing Dashboard
```

### 📝 Transactions (Transaksi)
```
├─ Purchase Request (PR)
│  ├─ PR List
│  ├─ Create PR
│  ├─ PR Approval
│  └─ PR Reports
│
├─ Purchase Order (PO)
│  ├─ PO List
│  ├─ Create PO
│  ├─ Convert PR to PO
│  ├─ PO Approval
│  └─ PO Reports
│
└─ Goods Receipt (GR)
   ├─ GR List
   ├─ Create GR
   ├─ GR Inspection
   └─ GR Reports
```

### 📦 Master Data
```
├─ Suppliers
│  ├─ Supplier List
│  ├─ Supplier Registration
│  └─ Supplier Performance
│
├─ Items
│  ├─ Item Master List
│  ├─ Item Category
│  └─ Item Price History
│
└─ Warehouses
   └─ Warehouse List
```

### 📈 Reports & Analytics
```
├─ Purchase Reports
├─ Supplier Reports
├─ Budget vs Actual
└─ Analytics Dashboard
```

### ⚙️ Settings
```
├─ Approval Workflow
├─ Document Numbering
├─ Email Templates
└─ General Settings
```

---

## 2. HALAMAN DETAIL & FITUR

### 2.1 📊 PURCHASING DASHBOARD

#### Halaman: Dashboard Overview
**URL**: `/purchasing/dashboard`

**Widgets/Komponen:**

1. **KPI Cards (Top Row)**
   - Total PR This Month (dengan perbandingan bulan lalu)
   - Total PO This Month (dengan nilai rupiah)
   - Pending Approvals (jumlah yang perlu approval)
   - Outstanding PO (jumlah PO yang belum diterima)
   - On-Time Delivery Rate (%)
   - Average Processing Time (hari)

2. **Charts & Graphs**
   - Line Chart: Purchase Trend (6 bulan terakhir)
   - Pie Chart: PO by Status
   - Bar Chart: Top 10 Suppliers by Value
   - Bar Chart: Purchase by Category
   - Area Chart: Budget vs Actual Spending

3. **Quick Action Buttons**
   - Create New PR
   - Create New PO
   - Record Goods Receipt
   - View Pending Approvals

4. **Tables/Lists**
   - Recent PR (5 terakhir dengan status)
   - Recent PO (5 terakhir dengan status)
   - Urgent PR (required date < 7 hari)
   - Overdue PO (melewati delivery date)
   - Pending My Approval (untuk approver)

5. **Alerts & Notifications**
   - Low Stock Items (perlu reorder)
   - PR Waiting Approval
   - PO Delivery Reminder
   - Quality Issues Alert

**Fitur:**
- Filter by date range
- Refresh data
- Export dashboard to PDF
- Drill-down ke detail

---

### 2.2 📝 PURCHASE REQUEST (PR)

#### A. Halaman: PR List
**URL**: `/purchasing/pr/list`

**Komponen:**

1. **Filter Panel**
   - Date Range (PR Date)
   - Status (Draft, Submitted, Approved, dll)
   - Requester
   - Department
   - Priority
   - PR Number (search)
   - Required Date Range

2. **Action Buttons**
   - Create New PR
   - Bulk Approval (untuk approver)
   - Export to Excel
   - Print List

3. **Data Grid/Table**
   - Kolom: PR Number, PR Date, Requester, Department, Total Items, Total Amount, Status, Required Date, Actions
   - Sorting: semua kolom
   - Pagination: 10/25/50/100 per page
   - Row Actions: View, Edit, Delete, Approve, Reject, Print, Convert to PO

4. **Status Badge Color**
   - Draft: Grey
   - Submitted: Blue
   - Approved: Green
   - Rejected: Red
   - Partially Ordered: Orange
   - Fully Ordered: Dark Green
   - Cancelled: Black

**Fitur:**
- Quick view (modal popup untuk lihat detail tanpa pindah halaman)
- Bulk actions (select multiple PR)
- Save filter preferences
- Column visibility toggle

---

#### B. Halaman: Create/Edit PR
**URL**: `/purchasing/pr/create` atau `/purchasing/pr/edit/{id}`

**Sections:**

1. **Header Information**
   - PR Number (auto-generate atau manual)
   - PR Date (default: today)
   - Request Department (dropdown)
   - Requester (auto dari user login, bisa diganti)
   - PR Type (Standard, Urgent, Stock Replenishment, Project)
   - Priority (Low, Normal, High, Critical)
   - Required Date (date picker dengan validation > today)
   - Warehouse (dropdown)

2. **Reference Information**
   - Purpose (textarea)
   - Project Code (jika PR Type = Project)
   - Cost Center Code (dropdown)
   - Attachment (upload multiple files)

3. **Item Details (Grid/Table)**
   - Kolom: Item Code (searchable), Item Name, Description, Qty Requested, UoM, Est. Unit Price, Est. Total, Specification, Notes, Actions
   - Add Item Button
   - Item Search dengan autocomplete
   - Quick add dari template (jika ada)
   - Import from Excel
   - Inline edit untuk qty dan price
   - Delete item
   - Sub-total calculation

4. **Summary Panel (Right Sidebar atau Bottom)**
   - Total Items: X
   - Total Estimated Amount: Rp XXX
   - Currency: IDR

5. **Notes & Attachments**
   - Internal Notes (textarea)
   - Attachment List (dengan preview dan delete)

6. **Action Buttons (Bottom)**
   - Save as Draft
   - Submit for Approval
   - Cancel
   - Print Preview

**Fitur:**
- Auto-save draft (setiap 30 detik)
- Validation rules:
  - Required Date harus > PR Date
  - Minimal 1 item
  - Semua qty harus > 0
- Item duplicate check
- Copy from existing PR
- Create from template

**Modal/Popup:**
- Item Search & Selection
- Item Detail View
- Attachment Preview

---

#### C. Halaman: PR Approval
**URL**: `/purchasing/pr/approval`

**Komponen:**

1. **Filter Panel**
   - Status: Pending My Approval, Approved by Me, Rejected by Me
   - Date Range
   - Department
   - Priority
   - Amount Range

2. **Approval Queue Table**
   - Kolom: PR Number, PR Date, Requester, Department, Total Amount, Priority, Required Date, Days Pending, Actions
   - Highlight urgent items (red)
   - Sort by priority dan date

3. **Bulk Approval**
   - Select All checkbox
   - Approve Selected button
   - Reject Selected button

4. **Detail View (Modal atau Side Panel)**
   Ketika klik View/Approve:
   - PR Header Info
   - Item Details (read-only table)
   - History & Comments
   - Approval Form:
     - Action: Approve / Reject (radio button)
     - Comments (textarea, required jika reject)
     - Approve/Reject button

**Fitur:**
- Email notification setelah approve/reject
- Approval level indicator (jika multi-level)
- Approval history timeline
- Quick approve (tanpa buka detail)

---

#### D. Halaman: PR Reports
**URL**: `/purchasing/pr/reports`

**Report Types:**

1. **PR Summary Report**
   - Filter: Date range, Department, Status, Priority
   - Group by: Department, Requester, Month
   - Show: Count, Total Amount
   - Export: Excel, PDF

2. **PR by Status Report**
   - Pie chart + table
   - Filter by date range

3. **PR Processing Time Report**
   - Average days from create to approve
   - Group by department
   - Trend line

4. **Outstanding PR Report**
   - PR yang approved tapi belum di-PO
   - Aging analysis

5. **PR vs PO Conversion Report**
   - Berapa % PR yang di-convert ke PO
   - Average conversion time

**Fitur:**
- Interactive charts (drill-down)
- Schedule report (email otomatis)
- Save report template

---

### 2.3 📦 PURCHASE ORDER (PO)

#### A. Halaman: PO List
**URL**: `/purchasing/po/list`

**Komponen:**

1. **Filter Panel**
   - Date Range (PO Date)
   - Status (Draft, Approved, Sent, Partially Received, dll)
   - Supplier (autocomplete)
   - Buyer
   - PO Type
   - Warehouse
   - PO Number (search)
   - Delivery Date Range

2. **Action Buttons**
   - Create New PO
   - Create from PR (shortcut)
   - Bulk Send to Supplier
   - Export to Excel

3. **Data Grid/Table**
   - Kolom: PO Number, PO Date, Supplier, Warehouse, Total Amount, Status, Delivery Date, % Received, Actions
   - Color coding untuk status
   - Progress bar untuk % received
   - Sorting & filtering
   - Pagination

4. **Row Actions**
   - View
   - Edit (hanya jika status = Draft)
   - Delete (hanya jika Draft)
   - Approve
   - Send to Supplier (generate PDF, send email)
   - Print
   - Receive Goods (shortcut ke GR)
   - Cancel PO
   - Close PO

**Fitur:**
- Quick filter buttons (Awaiting Approval, Sent to Supplier, Overdue Delivery)
- Multi-currency display
- Status history tooltip
- Supplier contact quick view

---

#### B. Halaman: Create/Edit PO
**URL**: `/purchasing/po/create` atau `/purchasing/po/edit/{id}`

**Sections:**

1. **Header Information**
   - PO Number (auto-generate)
   - PO Date (default: today)
   - Supplier (autocomplete dengan detail supplier muncul)
     - Contact, Phone, Email (display)
   - Warehouse (dropdown)
   - Buyer (default: user login)
   - PO Type (Standard, Blanket, Contract, Planned)

2. **Supplier Details (Auto-fill dari master)**
   - Supplier Address
   - Contact Person
   - Phone/Email
   - Payment Terms (editable)
   - Payment Method

3. **Delivery Information**
   - Delivery Address (default dari warehouse, editable)
   - Expected Delivery Date (date picker)
   - Shipping Method (dropdown)

4. **Currency & Exchange Rate**
   - Currency (dropdown: IDR, USD, EUR, dll)
   - Exchange Rate (auto-fetch, bisa manual override)

5. **Item Details (Grid/Table)**
   - Kolom: Item Code, Item Name, Qty Ordered, UoM, Unit Price, Discount %, Tax %, Total Price, Promised Delivery, Actions
   - Add Item Button
   - Add from PR (popup untuk pilih PR items)
   - Import from Excel
   - Inline edit
   - Delete item
   - Real-time calculation

6. **Pricing Summary (Right Panel atau Bottom)**
   - Subtotal: Rp XXX
   - Discount %: (input) → Amount: Rp XXX
   - Freight Cost: Rp XXX (input)
   - Other Cost: Rp XXX (input)
   - Subtotal after discount: Rp XXX
   - Tax % (PPN): (input, default 11%) → Amount: Rp XXX
   - **Grand Total: Rp XXX,XXX**

7. **Terms & Conditions**
   - Load from Template (dropdown)
   - Terms & Conditions (rich text editor)
   - Internal Notes (textarea)
   - Supplier Notes (textarea)

8. **Attachments**
   - Upload files
   - Link to PR documents

9. **Action Buttons**
   - Save as Draft
   - Submit for Approval
   - Approve (jika user punya authority)
   - Send to Supplier (generate PDF + email)
   - Print Preview
   - Cancel

**Fitur:**
- Real-time price negotiation history (jika ada)
- Item price comparison dengan last purchase
- Auto-calculate all pricing fields
- Validation:
  - Minimal 1 item
  - Qty > 0
  - Price > 0
  - Delivery date >= PO date
- Copy from existing PO
- PO template

**Modal/Popup:**
- Select Items from PR (checklist PR items)
- Item Search & Add
- Supplier Quick View/Edit
- Email Preview before send

---

#### C. Halaman: Convert PR to PO
**URL**: `/purchasing/po/convert-from-pr`

**Workflow:**

1. **Step 1: Select PR**
   - Table of Approved PR dengan outstanding items
   - Filter: Date, Department, Requester
   - Multi-select PR (checkbox)
   - Show total items & estimated amount

2. **Step 2: Group by Supplier**
   - Otomatis group items by suggested supplier
   - User bisa manual assign/change supplier
   - Preview: 1 PO akan dibuat untuk setiap supplier

3. **Step 3: Review & Adjust**
   - Table of items per supplier
   - Adjust qty (jika partial PO)
   - Adjust price
   - Add/remove items
   - Set delivery date per PO

4. **Step 4: Create PO**
   - Review summary (berapa PO akan dibuat)
   - Confirm & Create
   - Redirect ke PO list dengan success message

**Fitur:**
- Smart grouping by supplier
- Merge multiple PR into 1 PO
- Split 1 PR into multiple PO
- Carry over all PR details (specification, notes)

---

#### D. Halaman: PO Approval
**URL**: `/purchasing/po/approval`

Similar dengan PR Approval, dengan tambahan:

**Komponen:**

1. **Approval Rules Indicator**
   - Show approval limit user
   - Highlight PO yang exceeds limit (need higher approval)

2. **Budget Check**
   - Compare PO amount vs budget available
   - Warning jika over budget
   - Block approval jika exceed tolerance

3. **Detail View**
   - PO Header Info (read-only)
   - Supplier Info
   - Item Details dengan pricing
   - Total Amount breakdown
   - PR Reference (jika ada)
   - Approval Form:
     - Approve / Reject / Request Revision
     - Comments
     - Send notification to buyer

**Fitur:**
- Multi-level approval workflow
- Escalation rules
- Approval delegation
- Email notification

---

#### E. Halaman: PO Reports
**URL**: `/purchasing/po/reports`

**Report Types:**

1. **PO Summary Report**
   - Total PO value by period
   - Group by: Supplier, Category, Department, Buyer
   - Comparison with previous period

2. **Supplier Performance Report**
   - On-time delivery rate
   - Quality acceptance rate
   - Average lead time
   - Total spend per supplier
   - Ranking

3. **Outstanding PO Report**
   - PO yang belum fully received
   - Aging analysis (overdue by days)
   - Expected delivery schedule

4. **PO vs Budget Report**
   - Actual vs Budget by cost center
   - Variance analysis
   - Forecast to year end

5. **Purchase Analysis Report**
   - Spend by category
   - Trend analysis
   - Price variance analysis
   - Savings opportunities

6. **PO Processing Time Report**
   - Average days from PR to PO
   - Average approval time
   - Cycle time by buyer

**Fitur:**
- Drill-down capability
- Export to Excel/PDF
- Email scheduling
- Dashboard widgets

---

### 2.4 📥 GOODS RECEIPT (GR)

#### A. Halaman: GR List
**URL**: `/purchasing/gr/list`

**Komponen:**

1. **Filter Panel**
   - GR Date Range
   - Status (Draft, Inspected, Approved, Posted)
   - PO Number
   - Supplier
   - Warehouse
   - Inspection Status
   - Received By

2. **Action Buttons**
   - Create New GR
   - Post to Inventory (bulk)
   - Export to Excel

3. **Data Grid/Table**
   - Kolom: GR Number, GR Date, PO Number, Supplier, Total Qty Received, Total Qty Accepted, Inspection Status, Status, Actions
   - Color coding:
     - Inspection Passed: Green
     - Inspection Failed: Red
     - Pending Inspection: Yellow

4. **Row Actions**
   - View
   - Edit (jika belum posted)
   - Inspect
   - Approve
   - Post to Inventory
   - Print GR Document
   - Cancel

**Fitur:**
- Filter PO yang ready untuk receive (sent to supplier)
- Quality issue flagging
- Integration status dengan inventory module

---

#### B. Halaman: Create/Edit GR
**URL**: `/purchasing/gr/create` atau `/purchasing/gr/edit/{id}`

**Sections:**

1. **Header Information**
   - GR Number (auto-generate)
   - GR Date (default: today)
   - Select PO (autocomplete, filter: status = sent/partially_received)
     - Auto-load: Supplier, Warehouse dari PO

2. **PO Details (Read-only, Auto-load)**
   - PO Number, Date
   - Supplier Info
   - Warehouse

3. **Delivery Information**
   - Delivery Note Number (dari supplier)
   - Delivery Note Date
   - Vehicle Number
   - Driver Name
   - Received By (default: user login)

4. **Item Receipt Details (Grid/Table)**
   - Kolom: Item Code, Item Name, Qty Ordered (dari PO), Qty Already Received, Qty Outstanding, **Qty Received Now** (input), UoM, Unit Price, Actions
   - Add batch/serial info (button per row)
   - Quality status indicator
   - Notes per item

5. **Additional Item Information (Modal/Expandable per item)**
   - Batch Number (input)
   - Serial Number (input, untuk serialized items)
   - Manufacturing Date
   - Expiry Date
   - Storage Location (dropdown)
   - Quality Status (Pending/Passed/Failed/Conditional)
   - Qty Accepted vs Qty Rejected
   - Rejection Reason (jika ada yang reject)
   - Photos (upload)

6. **Summary Panel**
   - Total Items: X
   - Total Qty Received: XXX
   - Total Qty Accepted: XXX
   - Total Qty Rejected: XXX
   - Total Value: Rp XXX

7. **Inspection Information**
   - Inspected By (dropdown)
   - Overall Inspection Status
   - Inspection Notes (textarea)
   - Inspection Photos/Documents

8. **General Notes**
   - Receipt Notes
   - Internal Notes

9. **Action Buttons**
   - Save as Draft
   - Submit for Inspection
   - Approve (jika user = approver)
   - Post to Inventory
   - Print GR
   - Cancel

**Fitur:**
- Auto-suggest qty to receive (= outstanding)
- Allow partial receipt
- Allow over-receipt (dengan warning/approval)
- Real-time qty validation
- Barcode scanning support (untuk batch/serial)
- Photo capture dari camera
- Integration dengan quality module

**Modal/Popup:**
- Item Detail & Batch Info
- Quality Inspection Form
- Rejection Details

---

#### C. Halaman: GR Inspection
**URL**: `/purchasing/gr/inspection`

**Komponen:**

1. **Inspection Queue**
   - Filter: Pending Inspection, Date Range, Supplier
   - Table: GR Number, Date, Supplier, Items Count, Priority

2. **Inspection Detail View**
   - GR Header Info
   - Item Inspection Checklist
   - For each item:
     - Expected specification
     - Inspection criteria
     - Measured values (input)
     - Pass/Fail decision
     - Comments
     - Photos

3. **Quality Inspection Form**
   - Inspector Name
   - Inspection Date & Time
   - Inspection Criteria Checklist:
     - Visual Inspection
     - Dimension Check
     - Weight Check
     - Quantity Verification
     - Packaging Condition
     - Documentation Check
   - Overall Result: Pass / Fail / Conditional
   - Action Required (jika fail):
     - Return to Supplier
     - Accept with Discount
     - Reject All
     - Partial Accept

4. **Decision & Actions**
   - Accept All
   - Accept Partial
   - Reject All
   - Request Re-inspection
   - Hold for Review

**Fitur:**
- Inspection template by item category
- Photo comparison (expected vs actual)
- Statistical process control (jika applicable)
- Auto-notification to supplier untuk rejected items
- Integration dengan quality management system

---

#### D. Halaman: GR Reports
**URL**: `/purchasing/gr/reports`

**Report Types:**

1. **Goods Receipt Summary**
   - Total receipts by period
   - Total value received
   - Group by: Supplier, Warehouse, Month

2. **Quality Performance Report**
   - Acceptance rate by supplier
   - Rejection rate & reasons
   - Trend analysis
   - Top issues

3. **Receipt vs PO Report**
   - PO fulfillment rate
   - Average receipt time (PO date to GR date)
   - Outstanding PO list

4. **Inventory Movement from GR**
   - Items received
   - Stock increase
   - Value increase

5. **Supplier Delivery Performance**
   - On-time delivery %
   - Early/Late delivery analysis
   - Lead time analysis

**Fitur:**
- Visual charts
- Export & print
- Drill-down to details

---

### 2.5 📦 MASTER DATA

#### A. Halaman: Supplier List
**URL**: `/purchasing/master/suppliers`

**Komponen:**

1. **Filter Panel**
   - Status (Active/Inactive)
   - Supplier Type (Local/International)
   - City/Province
   - Rating (1-5 stars)
   - Search by name/code

2. **Action Buttons**
   - Add New Supplier
   - Import from Excel
   - Export to Excel
   - Bulk Activate/Deactivate

3. **Data Grid**
   - Kolom: Supplier Code, Supplier Name, Type, Contact Person, Phone, Email, City, Rating, Status, Actions
   - Sorting & pagination

4. **Row Actions**
   - View Details
   - Edit
   - View Performance
   - View Purchase History
   - Activate/Deactivate
   - Delete (jika tidak ada transaksi)

**Fitur:**
- Quick view sidebar
- Star rating system
- Tag/label system (Good, Preferred, Blacklist)

---

#### B. Halaman: Supplier Details
**URL**: `/purchasing/master/suppliers/{id}`

**Tabs:**

1. **General Information**
   - Supplier Code, Name
   - Type, Category
   - Contact Information
   - Address
   - Tax Information
   - Bank Information
   - Payment Terms
   - Currency
   - Rating
   - Status

2. **Contact Persons**
   - Table of contacts (Name, Position, Phone, Email)
   - Add/Edit/Delete contact

3. **Items Supplied**
   - Table of items dari supplier ini
   - Last price, Last PO date
   - Lead time per item

4. **Purchase History**
   - Table of PO
   - Total spend (this year, last year)
   - Chart: spending trend

5. **Performance Metrics**
   - On-time delivery rate
   - Quality acceptance rate
   - Average lead time
   - Response time
   - Performance score

6. **Documents**
   - Upload company profile
   - Tax documents
   - Certificates
   - Contracts

**Actions:**
- Edit Supplier
- Add to Preferred List
- Block/Unblock
- Export Supplier Card

---

#### C. Halaman: Item Master List
**URL**: `/purchasing/master/items`

**Komponen:**

1. **Filter Panel**
   - Category (tree view)
   - Item Type
   - Status (Active/Inactive)
   - Stock Status (Below Reorder, Normal, Overstock)
   - Search

2. **Action Buttons**
   - Add New Item
   - Import Items
   - Export to Excel

3. **Data Grid**
   - Kolom: Item Code, Item Name, Category, Type, UoM, Current Stock, Reorder Point, Last Purchase Price, Status, Actions
   - Stock indicator (red jika < reorder point)

4. **Row Actions**
   - View Details
   - Edit
   - View Purchase History
   - View Stock Movement
   - Price History
   - Activate/Deactivate

**Fitur:**
- Quick reorder (create PR for items below reorder point)
- Mass update prices
- Item image preview

---

#### D. Halaman: Item Details
**URL**: `/purchasing/master/items/{id}`

**Tabs:**

1. **General Information**
   - Item Code, Name, Description
   - Category
   - Type, UoM
   - Specifications
   - Images

2. **Inventory Information**
   - Current Stock (per warehouse)
   - Min/Max Stock
   - Reorder Point
   - Safety Stock

3. **Pricing Information**
   - Standard Price
   - Last Purchase Price
   - Average Price
   - Price History Chart
   - Price by Supplier

4. **Suppliers**
   - Table: Supplier, Last Price, Last PO Date, Lead Time
   - Preferred Supplier indicator

5. **Purchase History**
   - Table of PR/PO items
   - Quantity & value trend

6. **Specifications & Documents**
   - Technical specifications
   - Datasheets
   - Certificates
   - Photos

---

### 2.6 📈 REPORTS & ANALYTICS

#### Halaman: Purchase Reports
**URL**: `/purchasing/reports`

**Categories:**

1. **Transaction Reports**
   - PR Report (by status, department, period)
   - PO Report (by supplier, buyer, period)
   - GR Report (by warehouse, period)

2. **Financial Reports**
   - Purchase Spend Analysis
   - Budget vs Actual
   - Cost Variance Report
   - Savings Report

3. **Supplier Reports**
   - Supplier Performance Scorecard
   - Supplier Spend Analysis
   - Supplier Compliance Report
   - New Supplier Report

4. **Operational Reports**
   - Procurement Cycle Time
   - Processing Time Analysis
   - Approval Efficiency
   - Delivery Performance

5. **Inventory Related**
   - Purchase to Stock Ratio
   - Slow Moving Items
   - Fast Moving Items
   - Reorder Suggestions

**Features per Report:**
- Multi-level filters
- Date range selection
- Group by options
- Sort & drill-down
- Export (Excel, PDF, CSV)
- Email scheduling
- Save as favorite
- Share report link

---

#### Halaman: Analytics Dashboard
**URL**: `/purchasing/analytics`

**Widgets:**

1. **Spend Analysis**
   - Total Spend (YTD, MTD)
   - Spend by Category (pie chart)
   - Spend Trend (line chart)
   - Top 10 Suppliers

2. **Efficiency Metrics**
   - Average Procurement Cycle Time
   - PO Processing Time
   - Approval Rate & Time
   - Auto vs Manual PO Ratio

3. **Supplier Analytics**
   - Supplier Concentration (% dari total spend)
   - Supplier Performance Score
   - New Suppliers Added
   - Supplier Risk Index

4. **Compliance & Control**
   - PO Compliance Rate (PO vs no PO)
   - Approval Bypass Rate
   - Budget Adherence %
   - Contract Compliance

5. **Forecasting**
   - Predicted Spend (next quarter)
   - Recommended Reorders
   - Seasonal Trends

**Fitur:**
- Customizable dashboard
- Drag & drop widgets
- Real-time data
- Export dashboard
- Schedule email reports

---

### 2.7 ⚙️ SETTINGS

#### A. Halaman: Approval Workflow
**URL**: `/purchasing/settings/approval-workflow`

**Komponen:**

1. **PR Approval Rules**
   - Define approval levels based on:
     - Amount threshold
     - Department
     - Item category
   - Assign approvers per level
   - Set parallel vs sequential approval

2. **PO Approval Rules**
   - Similar dengan PR
   - Budget check rules
   - Auto-approval rules (jika ada)

3. **Approval Delegation**
   - Set delegate approver
   - Date range untuk delegation
   - Notification settings

**Fitur:**
- Workflow diagram visualization
- Test workflow
- Approval history per rule

---

#### B. Halaman: Document Numbering
**URL**: `/purchasing/settings/numbering`

**Komponen:**

1. **Number Format Settings**
   - PR Number Format
   - PO Number Format
   - GR Number Format
   - Example preview
   - Reset counter options

2. **Format Components**
   - Prefix (text)
   - Date format (YYYY, YYMMM, YYYYMM, dll)
   - Separator
   - Sequence (####, #####)
   - Suffix (optional)

**Example:**
```
PR-202502-0001
PO-2025/02/00123
GR-20250208-0045
```

---

#### C. Halaman: Email Templates
**URL**: `/purchasing/settings/email-templates`

**Templates:**

1. **PR Submitted** (ke approver)
2. **PR Approved** (ke requester)
3. **PR Rejected** (ke requester)
4. **PO Created** (ke buyer)
5. **PO Approved** (ke buyer & supplier)
6. **PO Sent to Supplier**
7. **GR Created** (ke warehouse)
8. **Quality Issue Alert**
9. **Delivery Reminder** (ke supplier)
10. **Overdue PO Alert**

**Template Editor:**
- Rich text editor
- Merge fields ({{po_number}}, {{supplier_name}}, dll)
- Preview
- Test email
- Default template & custom

---

#### D. Halaman: General Settings
**URL**: `/purchasing/settings/general`

**Settings Groups:**

1. **System Settings**
   - Default Currency
   - Base Language
   - Time Zone
   - Date Format

2. **Transaction Settings**
   - Auto-numbering ON/OFF
   - Allow backdated transactions
   - Allow over-receipt
   - Require attachment (PR/PO/GR)
   - Default payment terms
   - Default tax rate

3. **Notification Settings**
   - Email notifications ON/OFF
   - SMS notifications (jika ada)
   - In-app notifications
   - Notification frequency

4. **Integration Settings**
   - Inventory module integration
   - Finance module integration
   - API keys & endpoints
   - Sync frequency

5. **Security Settings**
   - Password policy
   - Session timeout
   - IP whitelist
   - Audit log retention

---

## 3. USER ROLES & PERMISSIONS

### 3.1 Roles

1. **Requester/User**
   - Create PR
   - View own PR
   - View PR status

2. **PR Approver**
   - View PR for approval
   - Approve/Reject PR
   - View all PR

3. **Buyer/Procurement Officer**
   - View all PR
   - Create/Edit PO
   - Send PO to supplier
   - View all PO
   - Create GR

4. **PO Approver**
   - View PO for approval
   - Approve/Reject PO
   - View all PO

5. **Warehouse Staff**
   - Create GR
   - Update GR
   - View GR

6. **Quality Inspector**
   - View GR
   - Perform inspection
   - Approve/Reject items

7. **Purchasing Manager**
   - Full access to all transactions
   - View all reports
   - Approve high-value PO
   - Manage suppliers

8. **Administrator**
   - Full access
   - Settings management
   - User management
   - System configuration

### 3.2 Permission Matrix

| Function | Requester | PR Approver | Buyer | PO Approver | Warehouse | QC | Manager | Admin |
|----------|-----------|-------------|-------|-------------|-----------|-----|---------|-------|
| Create PR | ✓ | ✓ | ✓ | ✓ | ✓ | - | ✓ | ✓ |
| Approve PR | - | ✓ | - | - | - | - | ✓ | ✓ |
| Create PO | - | - | ✓ | - | - | - | ✓ | ✓ |
| Approve PO | - | - | - | ✓ | - | - | ✓ | ✓ |
| Send PO | - | - | ✓ | - | - | - | ✓ | ✓ |
| Create GR | - | - | ✓ | - | ✓ | - | ✓ | ✓ |
| QC Inspection | - | - | - | - | - | ✓ | ✓ | ✓ |
| View Reports | Limited | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage Suppliers | - | - | ✓ | - | - | - | ✓ | ✓ |
| Settings | - | - | - | - | - | - | - | ✓ |

---

## 4. MOBILE APP FEATURES (Optional)

### 4.1 Mobile Menu Structure

```
├─ Dashboard
├─ My Tasks
│  ├─ PR Approvals
│  ├─ PO Approvals
│  └─ Inspection Tasks
├─ Quick Actions
│  ├─ Create PR
│  ├─ Receive Goods
│  └─ Check Stock
├─ Scan
│  ├─ Scan Barcode (for GR)
│  └─ Scan QR (item info)
└─ Notifications
```

### 4.2 Key Mobile Features

1. **Approval On-the-Go**
   - Push notifications
   - Swipe to approve/reject
   - Quick view details

2. **Goods Receipt**
   - Camera untuk foto kondisi barang
   - Barcode scanning
   - Voice notes
   - Offline mode (sync later)

3. **Quick PR**
   - Template-based PR creation
   - Item search dengan autocomplete
   - Photo attachment

4. **Dashboard Widgets**
   - Pending approvals count
   - Outstanding PO
   - Today's deliveries

---

## 5. ADDITIONAL FEATURES & ENHANCEMENTS

### 5.1 Advanced Features

1. **RFQ (Request for Quotation)**
   - Convert PR to RFQ
   - Send to multiple suppliers
   - Compare quotations
   - Convert RFQ to PO

2. **Contract Management**
   - Long-term contracts
   - Contract items catalog
   - Release orders against contract
   - Contract expiry alerts

3. **Blanket PO**
   - Frame agreement
   - Release orders
   - Cumulative tracking

4. **Vendor Portal**
   - Supplier login
   - View PO
   - Confirm PO
   - Update delivery status
   - Submit invoice

5. **Budget Integration**
   - Check budget before PR approval
   - Budget reservation
   - Budget consumption tracking

6. **Requisition Catalog**
   - Shopping cart experience
   - Pre-approved items
   - Quick ordering

7. **AI Features**
   - Auto-suggest supplier based on item
   - Price prediction
   - Anomaly detection (unusual pricing)
   - Smart reorder recommendations

### 5.2 Workflow Automation

1. **Auto PR Generation**
   - Based on reorder point
   - Based on consumption forecast
   - Based on production plan

2. **Auto PO Creation**
   - From approved PR (rules-based)
   - From catalog orders

3. **Auto Email Notifications**
   - Delivery reminders
   - Overdue alerts
   - Approval reminders

4. **Auto Posting**
   - GR auto-post to inventory (jika QC pass)

---

## 6. UI/UX BEST PRACTICES

### 6.1 Design Principles

1. **Consistency**
   - Same layout pattern untuk List → Create/Edit → Detail
   - Consistent button placement
   - Uniform color coding

2. **Accessibility**
   - Keyboard shortcuts untuk power users
   - Breadcrumbs navigation
   - Clear error messages
   - Help tooltips

3. **Performance**
   - Lazy loading untuk large lists
   - Pagination dengan reasonable default
   - Client-side caching
   - Progressive loading

4. **Responsive Design**
   - Mobile-friendly forms
   - Collapsible panels
   - Touch-friendly buttons (48px min)

### 6.2 Color Coding Standards

**Status Colors:**
- Draft: Grey (#6c757d)
- Submitted/Pending: Blue (#0d6efd)
- Approved: Green (#198754)
- Rejected: Red (#dc3545)
- In Progress: Orange (#fd7e14)
- Completed: Dark Green (#146c43)
- Cancelled: Black (#212529)

**Priority Colors:**
- Low: Light Blue
- Normal: Grey
- High: Orange
- Critical: Red

**Alert Colors:**
- Success: Green
- Warning: Yellow
- Error: Red
- Info: Blue

---

## 7. IMPLEMENTATION PRIORITY

### Phase 1 (MVP - 2-3 months)
1. Dashboard (basic)
2. PR Module (List, Create, Approval)
3. PO Module (List, Create, Approval)
4. GR Module (List, Create)
5. Supplier Master
6. Item Master
7. Basic Reports
8. Settings (Numbering, Basic Approval)

### Phase 2 (Enhancement - 2-3 months)
1. Advanced Dashboard
2. Convert PR to PO
3. GR Inspection
4. Advanced Reports & Analytics
5. Email Notifications
6. Document Attachments
7. Approval Workflow (multi-level)
8. Performance Reports

### Phase 3 (Advanced - 3-4 months)
1. RFQ Module
2. Contract Management
3. Vendor Portal
4. Budget Integration
5. Mobile App
6. Advanced Analytics
7. AI Recommendations
8. API for 3rd party integration

---

## 8. TECHNICAL CONSIDERATIONS

### 8.1 Technology Stack Suggestions

**Frontend:**
- React.js / Vue.js / Angular
- UI Framework: Material-UI, Ant Design, or Bootstrap
- Charts: Chart.js, ApexCharts, or Recharts
- State Management: Redux / Vuex / Context API

**Backend:**
- Node.js (Express) / PHP (Laravel) / Python (Django/FastAPI) / Java (Spring Boot)
- RESTful API
- Authentication: JWT

**Database:**
- MySQL / PostgreSQL (sesuai design database yang sudah dibuat)

**Additional Tools:**
- File Storage: AWS S3 / Local Storage
- Email: SendGrid / Amazon SES
- PDF Generation: jsPDF / PDFKit
- Excel Export: ExcelJS / Apache POI
- Barcode: JsBarcode / QuaggaJS

### 8.2 Security Considerations

1. **Authentication & Authorization**
   - Multi-factor authentication (optional)
   - Role-based access control (RBAC)
   - Session management

2. **Data Security**
   - Encryption at rest & in transit
   - SQL injection prevention
   - XSS protection
   - CSRF protection

3. **Audit Trail**
   - Log all create/update/delete actions
   - User activity tracking
   - IP address logging

4. **Backup & Recovery**
   - Daily automated backup
   - Disaster recovery plan

---

**Version**: 1.0  
**Last Updated**: February 2026  
**Total Estimated Pages**: 40-50 halaman untuk full implementation
