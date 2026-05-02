# resources/js/ — Frontend SPA Knowledge Base

## OVERVIEW

React 19 + TypeScript full SPA with Shadcn UI, TanStack Query, react-router-dom, Zod validation. NO Inertia.js.

## STRUCTURE

```
resources/js/
├── app-routes.tsx           # All routes (lazy-loaded), THE routing source of truth
├── pages/{module-slug}/     # Page components (1 file for simple, index.tsx for complex)
├── components/
│   ├── common/              # 39 shared components (EntityCrudPage, ReportDataTablePage, etc.)
│   ├── ui/                  # 34 Shadcn UI primitives
│   └── {module-slug}/       # Module-specific (Columns, Filters, Form, ViewModal)
├── hooks/                   # 26 custom hooks (useEntityForm, useCrudQuery, useCrudMutations)
├── types/                   # 36 TypeScript type definition files
├── utils/                   # entityConfigs.ts, schemas, helpers
├── contexts/                # React contexts (auth, i18n)
├── layouts/                 # App/Auth/Settings/Admin layouts
├── lib/                     # Utility wrappers (react-helmet-async shim, utils.ts)
├── constants/               # App constants
├── actions/                 # Server action helpers
├── routes/                  # Route helpers
└── wayfinder/               # Laravel Wayfinder generated routes
```

## WHERE TO LOOK

| Task              | Location                                                                             |
| ----------------- | ------------------------------------------------------------------------------------ |
| Add page route    | `app-routes.tsx` — lazy `<Route>`                                                    |
| Simple CRUD page  | `pages/{slug}/index.tsx` — 6 lines with `createEntityCrudPage`                       |
| Complex CRUD page | `pages/{slug}/index.tsx` + `components/{slug}/` (4 sibling files)                    |
| Report page       | `pages/reports/{slug}/index.tsx` — `ReportDataTablePage`                             |
| Entity config     | `utils/entityConfigs.ts` — `createSimpleEntityConfig` or `createComplexEntityConfig` |
| Form schema       | `utils/schemas.ts` or inline — Zod                                                   |
| Shared hooks      | `hooks/useEntityForm.ts`, `hooks/useCrudQuery.ts`, `hooks/useCrudMutations.ts`       |
| UI primitives     | `components/ui/` — Shadcn (button, dialog, table, etc.)                              |
| Common components | `components/common/` — EntityForm, ViewField, ViewModalShell, DataTable              |
| Type defs         | `types/{module}.ts`                                                                  |

## CONVENTIONS

- **Page factory** — simple CRUD = `createEntityCrudPage(config)` (6 lines). Complex = same factory + sibling files.
- **Sibling file pattern** — `{Module}Columns.tsx`, `{Module}Filters.tsx`, `{Module}Form.tsx`, `{Module}ViewModal.tsx`.
- **Form prop naming** — always `entity` (never `product`, `customer`, etc.).
- **`useEntityForm` hook** — eliminates form setup boilerplate. Takes `schema`, `getDefaults`, `entity`.
- **ViewModal** — always `memo()` wrapped, uses `ViewModalShell` + `ViewField`.
- **Data fetching** — TanStack Query via `useCrudQuery`/`useCrudMutations`. No raw axios in components.
- **Auth** — `auth-context.tsx` + Sanctum Bearer Token in localStorage.
- **Meta tags** — import from `react-helmet-async` (aliased to local shim, NOT upstream package).
- **Lazy loading** — all page routes use `React.lazy()` in `app-routes.tsx`.
- **Tailwind 4** — utility-first, `tailwind-merge` for class composition.

## ANTI-PATTERNS

- ❌ Importing from `@inertiajs/react`
- ❌ Using `usePage()`, `Head`, `Link` from Inertia
- ❌ Direct axios calls in components (use hooks)
- ❌ Module-specific form prop names (always `entity`)
- ❌ Adding `react-helmet-async` to package.json dependencies
- ❌ Non-lazy route imports in app-routes.tsx
