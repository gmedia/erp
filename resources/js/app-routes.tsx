import { Routes, Route, Navigate } from 'react-router-dom';
import { lazy, Suspense } from 'react';
import ProtectedRoute from './components/protected-route';
import GuestRoute from './components/guest-route';
import { LoaderCircle } from 'lucide-react';

// Loading fallback for lazy-loaded pages
const PageLoader = () => (
    <div className="flex h-screen w-full items-center justify-center bg-background">
        <LoaderCircle className="h-8 w-8 animate-spin text-muted-foreground" />
    </div>
);

// --- Auth pages (guest only) ---
const Login = lazy(() => import('./pages/auth/login'));
const ForgotPassword = lazy(() => import('./pages/auth/forgot-password'));
const ResetPassword = lazy(() => import('./pages/auth/reset-password'));
const VerifyEmail = lazy(() => import('./pages/auth/verify-email'));
const ConfirmPassword = lazy(() => import('./pages/auth/confirm-password'));
const TwoFactorChallenge = lazy(() => import('./pages/auth/two-factor-challenge'));

// --- Main pages ---
const Dashboard = lazy(() => import('./pages/dashboard'));

// Master Data
const Branches = lazy(() => import('./pages/branches/index'));
const Departments = lazy(() => import('./pages/departments/index'));
const Positions = lazy(() => import('./pages/positions/index'));
const Employees = lazy(() => import('./pages/employees/index'));
const Customers = lazy(() => import('./pages/customers/index'));
const CustomerCategories = lazy(() => import('./pages/customer-categories/index'));
const Suppliers = lazy(() => import('./pages/suppliers/index'));
const SupplierCategories = lazy(() => import('./pages/supplier-categories/index'));

// Products & Inventory
const Products = lazy(() => import('./pages/products/index'));
const ProductCategories = lazy(() => import('./pages/product-categories/index'));
const Units = lazy(() => import('./pages/units/index'));
const Warehouses = lazy(() => import('./pages/warehouses/index'));
const StockMonitor = lazy(() => import('./pages/stock-monitor/index'));
const StockTransfers = lazy(() => import('./pages/stock-transfers/index'));
const StockAdjustments = lazy(() => import('./pages/stock-adjustments/index'));
const StockMovements = lazy(() => import('./pages/stock-movements/index'));
const InventoryStocktakes = lazy(() => import('./pages/inventory-stocktakes/index'));

// Assets
const Assets = lazy(() => import('./pages/assets/index'));
const AssetProfile = lazy(() => import('./pages/assets/profile'));
const AssetCategories = lazy(() => import('./pages/asset-categories/index'));
const AssetModels = lazy(() => import('./pages/asset-models/index'));
const AssetLocations = lazy(() => import('./pages/asset-locations/index'));
const AssetMaintenances = lazy(() => import('./pages/asset-maintenances/index'));
const AssetMovements = lazy(() => import('./pages/asset-movements/index'));
const AssetStocktakes = lazy(() => import('./pages/asset-stocktakes/index'));
const AssetStocktakePerform = lazy(() => import('./pages/asset-stocktakes/perform'));
const AssetDepreciationRuns = lazy(() => import('./pages/asset-depreciation-runs/index'));
const AssetDashboard = lazy(() => import('./pages/asset-dashboard/index'));

// Accounting
const Accounts = lazy(() => import('./pages/accounts/index'));
const AccountMappings = lazy(() => import('./pages/account-mappings/index'));
const CoaVersions = lazy(() => import('./pages/coa-versions/index'));
const FiscalYears = lazy(() => import('./pages/fiscal-years/index'));
const JournalEntries = lazy(() => import('./pages/journal-entries/index'));
const PostingJournals = lazy(() => import('./pages/posting-journals/index'));

// Pipeline & Approvals
const Pipelines = lazy(() => import('./pages/pipelines/index'));
const PipelineDashboard = lazy(() => import('./pages/pipeline-dashboard/index'));
const PipelineAuditTrail = lazy(() => import('./pages/pipeline-audit-trail/index'));
const ApprovalFlows = lazy(() => import('./pages/approval-flows/index'));
const ApprovalDelegations = lazy(() => import('./pages/approval-delegations/index'));
const ApprovalMonitoring = lazy(() => import('./pages/approval-monitoring/index'));
const ApprovalAuditTrail = lazy(() => import('./pages/approval-audit-trail/index'));
const MyApprovals = lazy(() => import('./pages/my-approvals/index'));

// Users & Permissions
const Users = lazy(() => import('./pages/users/index'));
const Permissions = lazy(() => import('./pages/permissions/index'));

// Settings
const SettingsProfile = lazy(() => import('./pages/settings/profile'));
const SettingsPassword = lazy(() => import('./pages/settings/password'));
const SettingsAppearance = lazy(() => import('./pages/settings/appearance'));
const SettingsTwoFactor = lazy(() => import('./pages/settings/two-factor'));
const AdminSettings = lazy(() => import('./pages/admin-settings/index'));

// Reports
const BalanceSheet = lazy(() => import('./pages/reports/balance-sheet/index'));
const CashFlow = lazy(() => import('./pages/reports/cash-flow/index'));
const Comparative = lazy(() => import('./pages/reports/comparative/index'));
const IncomeStatement = lazy(() => import('./pages/reports/income-statement/index'));
const TrialBalance = lazy(() => import('./pages/reports/trial-balance/index'));
const ReportAssetRegister = lazy(() => import('./pages/reports/assets/register/index'));
const ReportBookValueDepreciation = lazy(() => import('./pages/reports/book-value-depreciation/index'));
const ReportMaintenanceCost = lazy(() => import('./pages/reports/maintenance-cost/index'));
const ReportAssetStocktakeVariances = lazy(() => import('./pages/reports/asset-stocktake-variances/index'));
const ReportInventoryValuation = lazy(() => import('./pages/reports/inventory-valuation/index'));
const ReportInventoryStocktakeVariance = lazy(() => import('./pages/reports/inventory-stocktake-variance/index'));
const ReportStockMovement = lazy(() => import('./pages/reports/stock-movement/index'));
const ReportStockAdjustment = lazy(() => import('./pages/reports/stock-adjustment/index'));

// Helper to wrap protected routes
const P = ({ children }: { children: React.ReactNode }) => (
    <ProtectedRoute>{children}</ProtectedRoute>
);

export default function AppRoutes() {
    return (
        <Suspense fallback={<PageLoader />}>
            <Routes>
                {/* Guest-only routes */}
                <Route path="/login" element={<GuestRoute><Login /></GuestRoute>} />
                <Route path="/forgot-password" element={<GuestRoute><ForgotPassword /></GuestRoute>} />
                <Route path="/reset-password" element={<GuestRoute><ResetPassword /></GuestRoute>} />
                <Route path="/verify-email" element={<P><VerifyEmail /></P>} />
                <Route path="/confirm-password" element={<P><ConfirmPassword /></P>} />
                <Route path="/two-factor-challenge" element={<GuestRoute><TwoFactorChallenge /></GuestRoute>} />

                {/* Dashboard */}
                <Route path="/dashboard" element={<P><Dashboard /></P>} />

                {/* Master Data */}
                <Route path="/branches" element={<P><Branches /></P>} />
                <Route path="/departments" element={<P><Departments /></P>} />
                <Route path="/positions" element={<P><Positions /></P>} />
                <Route path="/employees" element={<P><Employees /></P>} />
                <Route path="/customers" element={<P><Customers /></P>} />
                <Route path="/customer-categories" element={<P><CustomerCategories /></P>} />
                <Route path="/suppliers" element={<P><Suppliers /></P>} />
                <Route path="/supplier-categories" element={<P><SupplierCategories /></P>} />

                {/* Products & Inventory */}
                <Route path="/products" element={<P><Products /></P>} />
                <Route path="/product-categories" element={<P><ProductCategories /></P>} />
                <Route path="/units" element={<P><Units /></P>} />
                <Route path="/warehouses" element={<P><Warehouses /></P>} />
                <Route path="/stock-monitor" element={<P><StockMonitor /></P>} />
                <Route path="/stock-transfers" element={<P><StockTransfers /></P>} />
                <Route path="/stock-adjustments" element={<P><StockAdjustments /></P>} />
                <Route path="/stock-movements" element={<P><StockMovements /></P>} />
                <Route path="/inventory-stocktakes" element={<P><InventoryStocktakes /></P>} />

                {/* Assets */}
                <Route path="/assets" element={<P><Assets /></P>} />
                <Route path="/assets/:id" element={<P><AssetProfile /></P>} />
                <Route path="/asset-categories" element={<P><AssetCategories /></P>} />
                <Route path="/asset-models" element={<P><AssetModels /></P>} />
                <Route path="/asset-locations" element={<P><AssetLocations /></P>} />
                <Route path="/asset-maintenances" element={<P><AssetMaintenances /></P>} />
                <Route path="/asset-movements" element={<P><AssetMovements /></P>} />
                <Route path="/asset-stocktakes" element={<P><AssetStocktakes /></P>} />
                <Route path="/asset-stocktakes/:id/perform" element={<P><AssetStocktakePerform /></P>} />
                <Route path="/asset-depreciation-runs" element={<P><AssetDepreciationRuns /></P>} />
                <Route path="/asset-dashboard" element={<P><AssetDashboard /></P>} />

                {/* Accounting */}
                <Route path="/accounts" element={<P><Accounts /></P>} />
                <Route path="/account-mappings" element={<P><AccountMappings /></P>} />
                <Route path="/coa-versions" element={<P><CoaVersions /></P>} />
                <Route path="/fiscal-years" element={<P><FiscalYears /></P>} />
                <Route path="/journal-entries" element={<P><JournalEntries /></P>} />
                <Route path="/posting-journals" element={<P><PostingJournals /></P>} />

                {/* Pipeline & Approvals */}
                <Route path="/pipelines" element={<P><Pipelines /></P>} />
                <Route path="/pipeline-dashboard" element={<P><PipelineDashboard /></P>} />
                <Route path="/pipeline-audit-trail" element={<P><PipelineAuditTrail /></P>} />
                <Route path="/approval-flows" element={<P><ApprovalFlows /></P>} />
                <Route path="/approval-delegations" element={<P><ApprovalDelegations /></P>} />
                <Route path="/approval-monitoring" element={<P><ApprovalMonitoring /></P>} />
                <Route path="/approval-audit-trail" element={<P><ApprovalAuditTrail /></P>} />
                <Route path="/my-approvals" element={<P><MyApprovals /></P>} />

                {/* Users & Permissions */}
                <Route path="/users" element={<P><Users /></P>} />
                <Route path="/permissions" element={<P><Permissions /></P>} />

                {/* Settings */}
                <Route path="/settings/profile" element={<P><SettingsProfile /></P>} />
                <Route path="/settings/password" element={<P><SettingsPassword /></P>} />
                <Route path="/settings/appearance" element={<P><SettingsAppearance /></P>} />
                <Route path="/settings/two-factor" element={<P><SettingsTwoFactor /></P>} />
                <Route path="/admin-settings" element={<P><AdminSettings /></P>} />

                {/* Reports */}
                <Route path="/reports/balance-sheet" element={<P><BalanceSheet /></P>} />
                <Route path="/reports/cash-flow" element={<P><CashFlow /></P>} />
                <Route path="/reports/comparative" element={<P><Comparative /></P>} />
                <Route path="/reports/income-statement" element={<P><IncomeStatement /></P>} />
                <Route path="/reports/trial-balance" element={<P><TrialBalance /></P>} />
                <Route path="/reports/assets/register" element={<P><ReportAssetRegister /></P>} />
                <Route path="/reports/book-value-depreciation" element={<P><ReportBookValueDepreciation /></P>} />
                <Route path="/reports/maintenance-cost" element={<P><ReportMaintenanceCost /></P>} />
                <Route path="/reports/asset-stocktake-variances" element={<P><ReportAssetStocktakeVariances /></P>} />
                <Route path="/reports/inventory-valuation" element={<P><ReportInventoryValuation /></P>} />
                <Route path="/reports/inventory-stocktake-variance" element={<P><ReportInventoryStocktakeVariance /></P>} />
                <Route path="/reports/stock-movement" element={<P><ReportStockMovement /></P>} />
                <Route path="/reports/stock-adjustment" element={<P><ReportStockAdjustment /></P>} />

                {/* Root & fallback */}
                <Route path="/" element={<Navigate to="/dashboard" replace />} />
                <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Routes>
        </Suspense>
    );
}
