import {
    Children,
    Fragment,
    isValidElement,
    type ReactNode,
    useEffect,
    useId,
} from 'react';

type HeadTagName = 'base' | 'link' | 'meta' | 'noscript' | 'script' | 'style';

type HeadElementSpec = {
    tagName: HeadTagName;
    attributes: Record<string, string>;
    content?: string;
    key: string;
};

type HelmetState = {
    title?: string;
    elements: HeadElementSpec[];
};

type HelmetProps = {
    children?: ReactNode;
};

const MANAGED_ATTRIBUTE = 'data-local-helmet';
const headState = new Map<string, HelmetState>();
let defaultDocumentTitle: string | undefined;

const ATTRIBUTE_ALIASES: Record<string, string> = {
    acceptCharset: 'accept-charset',
    charSet: 'charset',
    className: 'class',
    crossOrigin: 'crossorigin',
    httpEquiv: 'http-equiv',
    itemProp: 'itemprop',
    referrerPolicy: 'referrerpolicy',
};

function toAttributeName(name: string): string {
    return ATTRIBUTE_ALIASES[name] ?? name.replace(/[A-Z]/g, (char) => `-${char.toLowerCase()}`);
}

function getTextContent(node: ReactNode): string {
    if (node == null || typeof node === 'boolean') {
        return '';
    }

    if (typeof node === 'string' || typeof node === 'number') {
        return String(node);
    }

    if (Array.isArray(node)) {
        return node.map((child) => getTextContent(child)).join('');
    }

    if (isValidElement<{ children?: ReactNode }>(node)) {
        return getTextContent(node.props.children);
    }

    return '';
}

function getElementContent(props: Record<string, unknown>): string | undefined {
    if (typeof props.dangerouslySetInnerHTML === 'object' && props.dangerouslySetInnerHTML !== null) {
        const html = (props.dangerouslySetInnerHTML as { __html?: unknown }).__html;
        return typeof html === 'string' ? html : undefined;
    }

    const content = getTextContent(props.children as ReactNode);
    return content === '' ? undefined : content;
}

function computeElementKey(
    tagName: HeadTagName,
    props: Record<string, unknown>,
    index: number,
    content?: string,
): string {
    const explicitKey = props.key;

    if (typeof explicitKey === 'string' || typeof explicitKey === 'number') {
        return `${tagName}:key:${explicitKey}`;
    }

    if (tagName === 'meta') {
        const discriminator =
            props.name ?? props.property ?? props.httpEquiv ?? props.charSet ?? props.itemProp;
        if (typeof discriminator === 'string') {
            return `meta:${discriminator}`;
        }
    }

    if (tagName === 'link') {
        const rel = typeof props.rel === 'string' ? props.rel : 'link';
        const href = typeof props.href === 'string' ? props.href : index.toString();
        return `link:${rel}:${href}`;
    }

    if (tagName === 'base') {
        const href = typeof props.href === 'string' ? props.href : '';
        const target = typeof props.target === 'string' ? props.target : '';
        return `base:${href}:${target}`;
    }

    if (tagName === 'script') {
        const src = typeof props.src === 'string' ? props.src : '';
        const type = typeof props.type === 'string' ? props.type : '';
        return `script:${src}:${type}:${content ?? index}`;
    }

    return `${tagName}:${content ?? index}`;
}

function parseAttributes(props: Record<string, unknown>): Record<string, string> {
    const attributes: Record<string, string> = {};

    for (const [name, value] of Object.entries(props)) {
        if (
            name === 'children' ||
            name === 'dangerouslySetInnerHTML' ||
            name === 'suppressHydrationWarning' ||
            name === 'key'
        ) {
            continue;
        }

        if (value == null || value === false) {
            continue;
        }

        attributes[toAttributeName(name)] = value === true ? '' : String(value);
    }

    return attributes;
}

function collectHelmetState(children: ReactNode): HelmetState {
    const elements: HeadElementSpec[] = [];
    let title: string | undefined;

    Children.forEach(children, (child, index) => {
        if (!isValidElement(child)) {
            return;
        }

        if (child.type === Fragment) {
            const fragmentProps = child.props as { children?: ReactNode };
            const nestedState = collectHelmetState(fragmentProps.children);
            if (nestedState.title !== undefined) {
                title = nestedState.title;
            }
            elements.push(...nestedState.elements);
            return;
        }

        if (typeof child.type !== 'string') {
            return;
        }

        const tagName = child.type.toLowerCase();
        const props = child.props as Record<string, unknown>;

        if (tagName === 'title') {
            const nextTitle = getTextContent(props.children as ReactNode).trim();
            if (nextTitle !== '') {
                title = nextTitle;
            }
            return;
        }

        if (!['base', 'link', 'meta', 'noscript', 'script', 'style'].includes(tagName)) {
            return;
        }

        const content = getElementContent(props);
        elements.push({
            tagName: tagName as HeadTagName,
            attributes: parseAttributes(props),
            content,
            key: computeElementKey(tagName as HeadTagName, props, index, content),
        });
    });

    return { title, elements };
}

function createHeadElement(spec: HeadElementSpec): HTMLElement {
    const element = document.createElement(spec.tagName);
    element.setAttribute(MANAGED_ATTRIBUTE, 'true');

    for (const [name, value] of Object.entries(spec.attributes)) {
        element.setAttribute(name, value);
    }

    if (spec.content !== undefined) {
        element.textContent = spec.content;
    }

    return element;
}

function applyHeadState(): void {
    if (typeof document === 'undefined') {
        return;
    }

    defaultDocumentTitle ??= document.title;

    const activeStates = Array.from(headState.values());
    const nextTitle = [...activeStates].reverse().find((state) => state.title !== undefined)?.title;
    document.title = nextTitle ?? defaultDocumentTitle ?? '';

    document.querySelectorAll(`[${MANAGED_ATTRIBUTE}="true"]`).forEach((element) => {
        element.remove();
    });

    const dedupedElements = new Map<string, HeadElementSpec>();
    for (const state of activeStates) {
        for (const element of state.elements) {
            if (dedupedElements.has(element.key)) {
                dedupedElements.delete(element.key);
            }
            dedupedElements.set(element.key, element);
        }
    }

    for (const element of dedupedElements.values()) {
        document.head.appendChild(createHeadElement(element));
    }
}

export function HelmetProvider({ children }: HelmetProps) {
    return <>{children}</>;
}

export function Helmet({ children }: HelmetProps) {
    const id = useId();

    useEffect(() => {
        headState.set(id, collectHelmetState(children));
        applyHeadState();

        return () => {
            headState.delete(id);
            applyHeadState();
        };
    }, [children, id]);

    return null;
}

export default Helmet;