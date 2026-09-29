# UI / Livewire Conventions

- UI stack: Blade + Livewire + Alpine.js + Tailwind CSS v4.
- Livewire components orchestrate UI only; call Application Services for mutations.
- Use the app shell layout (`layouts/app`) with sidebar, business switcher, and financial year selector.
- Do not introduce React, Vue SPA, or separate frontend frameworks.
