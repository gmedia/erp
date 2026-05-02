# .github/ — DevOps & Agent Skills Knowledge Base

## OVERVIEW

CI/CD workflows, reusable agent prompts, scaffolding skills with templates, and agent configuration.

## STRUCTURE

```
.github/
├── agents/                  # Agent role definitions (context7-research, refactor-safe)
├── prompts/                 # Reusable prompt templates for common tasks
│   ├── checkpoint-progress.prompt.md
│   ├── continue-progress.prompt.md
│   ├── create-feature.prompt.md
│   ├── create-import.prompt.md
│   ├── create-tests.prompt.md
│   ├── refactor-module.prompt.md
│   ├── refactor-safe-depwire.prompt.md
│   └── refactor-sonar.prompt.md
├── skills/                  # Scaffolding skills with templates + scripts
│   ├── database-migration/  # Migration, factory, seeder templates
│   ├── feature-crud-complex/ # Complex CRUD scaffolding
│   ├── feature-crud-simple/  # Simple CRUD scaffolding
│   ├── feature-import/       # Import feature skill
│   ├── feature-non-crud/     # Non-CRUD feature templates
│   ├── refactor-backend/     # Backend refactor templates
│   ├── refactor-e2e/         # E2E test refactor templates
│   ├── refactor-frontend/    # Frontend refactor templates
│   ├── session-handoff/      # Handoff template
│   └── testing-strategy/     # Test templates (Feature, Unit, E2E)
├── workflows/
│   ├── ci-image.yml         # Docker CI image build
│   ├── release.yml          # Release workflow
│   └── tests.yml            # Test pipeline (Pest + Playwright)
└── copilot-instructions.md  # Master agent rules (THE source of truth)
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Agent rules | `copilot-instructions.md` — canonical rules |
| New feature scaffold | `skills/feature-crud-complex/` or `skills/feature-crud-simple/` |
| Session handoff | `prompts/continue-progress.prompt.md` |
| Checkpoint | `prompts/checkpoint-progress.prompt.md` |
| Test creation | `skills/testing-strategy/` + `prompts/create-tests.prompt.md` |
| Safe refactor | `skills/refactor-backend/` + `prompts/refactor-safe-depwire.prompt.md` |
| CI pipeline | `workflows/tests.yml` |

## CONVENTIONS

- **Skills** have `SKILL.md` (instructions) + `resources/` (templates) + `scripts/` (generators)
- **Prompts** are reusable markdown files invoked by agents
- **`copilot-instructions.md`** is the single source of truth for all agent behavior
- **`DECISION.md`** documents skill selection logic
