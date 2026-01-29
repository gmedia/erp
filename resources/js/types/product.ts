import { BaseEntity } from './entity';

export interface Product extends BaseEntity {
    code: string;
    name: string;
    description: string | null;
    type: 'raw_material' | 'work_in_progress' | 'finished_good' | 'purchased_good' | 'service';
    category: {
        id: number;
        name: string;
    };
    unit: {
        id: number;
        name: string;
        symbol: string | null;
    };
    branch: {
        id: number;
        name: string;
    } | null;
    cost: string;
    selling_price: string;
    markup_percentage: string | null;
    billing_model: 'one_time' | 'subscription' | 'both';
    is_recurring: boolean;
    trial_period_days: number | null;
    allow_one_time_purchase: boolean;
    is_manufactured: boolean;
    is_purchasable: boolean;
    is_sellable: boolean;
    is_taxable: boolean;
    status: 'active' | 'inactive' | 'discontinued';
    notes: string | null;
}

export interface ProductFormData {
    code: string;
    name: string;
    description?: string;
    type: 'raw_material' | 'work_in_progress' | 'finished_good' | 'purchased_good' | 'service';
    category_id: string;
    unit_id: string;
    branch_id?: string;
    cost: string;
    selling_price: string;
    markup_percentage?: string;
    billing_model: 'one_time' | 'subscription' | 'both';
    is_recurring: boolean;
    trial_period_days?: string;
    allow_one_time_purchase: boolean;
    is_manufactured: boolean;
    is_purchasable: boolean;
    is_sellable: boolean;
    is_taxable: boolean;
    status: 'active' | 'inactive' | 'discontinued';
    notes?: string;
}
