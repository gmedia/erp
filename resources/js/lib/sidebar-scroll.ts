let savedScrollTop = 0;

export function rememberSidebarScroll(el: HTMLElement): void {
    if (el.scrollTop > 0) savedScrollTop = el.scrollTop;
}

export function restoreSidebarScroll(el: HTMLElement): void {
    requestAnimationFrame(() => {
        el.scrollTop = savedScrollTop;
    });
}
