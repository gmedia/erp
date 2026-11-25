'use client';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import type { TranslatableLabel } from '@/components/common/TranslatedText';

type PartyLabelTranslator = (
    key: string,
    fallback: string,
) => TranslatableLabel;

interface PartyEmailPhoneFieldsProps {
    readonly fl: PartyLabelTranslator;
    readonly emailPlaceholder: string;
    readonly phonePlaceholder: string;
}

/**
 * Email and phone inputs shared by party master data (suppliers, customers).
 */
export function PartyEmailPhoneFields({
    fl,
    emailPlaceholder,
    phonePlaceholder,
}: Readonly<PartyEmailPhoneFieldsProps>) {
    return (
        <>
            <InputField
                name="email"
                label={fl('email', 'Email')}
                type="email"
                placeholder={fl('email_placeholder', emailPlaceholder)}
            />
            <InputField
                name="phone"
                label={fl('phone', 'Phone')}
                placeholder={fl('phone_placeholder', phonePlaceholder)}
            />
        </>
    );
}

interface PartyBranchCategoryFieldsProps {
    readonly fl: PartyLabelTranslator;
    readonly categoryUrl: string;
    readonly categoryPlaceholder: string;
}

/**
 * Branch and category selectors shared by party master data.
 */
export function PartyBranchCategoryFields({
    fl,
    categoryUrl,
    categoryPlaceholder,
}: Readonly<PartyBranchCategoryFieldsProps>) {
    return (
        <>
            <AsyncSelectField
                name="branch_id"
                label={fl('branch', 'Branch')}
                url="/api/branches"
                placeholder={fl('branch_placeholder', 'Select a branch')}
            />
            <AsyncSelectField
                name="category_id"
                label={fl('category', 'Category')}
                url={categoryUrl}
                placeholder={fl('category_placeholder', categoryPlaceholder)}
            />
        </>
    );
}
