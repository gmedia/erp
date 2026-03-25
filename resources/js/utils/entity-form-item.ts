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
    T extends { product_label?: string; unit_label?: string },
>(
    item: T,
) => {
    const nextItem = { ...item };
    delete nextItem.product_label;
    delete nextItem.unit_label;

    return nextItem;
};
