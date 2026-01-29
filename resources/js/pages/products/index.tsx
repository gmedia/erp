'use client';

import { createEntityCrudPage } from '@/components/common/EntityCrudPage';
import { productConfig } from '@/utils/entityConfigs';

const ProductsPage = createEntityCrudPage(productConfig);

export default function Page() {
    return <ProductsPage />;
}
