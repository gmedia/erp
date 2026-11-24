export const formatItemReference = (label?: string, id?: string) => {
    if (label) {
        return label;
    }

    if (id) {
        return `#${id}`;
    }

    return '-';
};

export const omitItemDisplayLabels = <
    T extends {
        product_label?: string;
        unit_label?: string;
        purchase_order_item_label?: string;
        goods_receipt_item_label?: string;
    },
>(
    item: T,
) => {
    const nextItem = { ...item };
    delete nextItem.product_label;
    delete nextItem.unit_label;
    delete nextItem.purchase_order_item_label;
    delete nextItem.goods_receipt_item_label;

    return nextItem;
};
