---
paths:
  - 'app/Models/*.php'
---

# Models

## DB-level column defaults don't populate in-memory model instances
A migration's `->default(...)` only applies at the database layer. A model instance built via a factory or `Model::create()` with that column mass-assignment-excluded (not in `#[Fillable]`) stays `null` in memory until the model is re-fetched from the DB — e.g. `$user->strictness_level->label()` throws on a freshly-created User because the enum cast sees `null`.

Fix at the model level with a native Eloquent default so every creation path gets it immediately, not just re-fetched ones:

```php
protected $attributes = [
    'strictness_level' => 'gentle',
];
```

Also set it explicitly in the model's factory `definition()` (matching this codebase's convention of always setting enum-backed status columns explicitly rather than relying on DB defaults — see GoalFactory/TaskFactory).
