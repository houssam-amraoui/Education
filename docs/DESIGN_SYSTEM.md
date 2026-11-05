# Design System

Reusable visual and interaction rules for the English Learning Platform, based on [the PRD](docs/PRD.md). Applies to public, student, and administration interfaces. This document defines foundations and components; it does not specify individual screens or authorize feature implementation.

## 1. Design principles

- Put learning content and the next useful action first.
- Use a calm, welcoming style: neutral surfaces, blue actions, restrained rewards.
- Share component styling across roles; adapt information density to the task.
- Support Arabic, French, and English with equal care.
- Use explicit labels and predictable interactions. Never communicate meaning through color alone.

## 2. Colors

Use semantic tokens rather than choosing colors per component. The initial theme is light.

| Token | Value | Use |
| --- | --- | --- |
| `canvas` | `#F8FAFC` | Page background |
| `surface` | `#FFFFFF` | Cards, forms, menus |
| `surface-muted` | `#F1F5F9` | Subtle sections, table headers, disabled backgrounds |
| `text` | `#0F172A` | Headings and main text |
| `text-muted` | `#475569` | Supporting text, labels, placeholders |
| `border` | `#CBD5E1` | Decorative dividers and card edges |
| `border-control` | `#64748B` | Input outlines and meaningful control boundaries |
| `primary` | `#1D4ED8` | Primary buttons, links, selected controls, focus |
| `primary-hover` | `#1E40AF` | Primary hover |
| `primary-active` | `#1E3A8A` | Primary pressed |
| `primary-soft` | `#EFF6FF` | Selected navigation and subtle emphasis |
| `on-primary` | `#FFFFFF` | Text and icons on primary fills |
| `success` / `success-soft` | `#166534` / `#F0FDF4` | Completion, correct answers, saved state |
| `warning` / `warning-soft` | `#854D0E` / `#FEFCE8` | Caution, time warnings, reward accents |
| `danger` / `danger-soft` | `#B91C1C` / `#FEF2F2` | Errors, incorrect answers, destructive actions |
| `danger-hover` / `danger-active` | `#991B1B` / `#7F1D1D` | Destructive button interactions |
| `info` / `info-soft` | `#075985` / `#F0F9FF` | Guidance and neutral notices |

- Status text uses its strong color on the matching soft surface. Filled status buttons use white text.
- Links are underlined within prose; navigation identifies the active item through weight, background, and an indicator.
- Target contrast: at least 4.5:1 for normal text, 3:1 for large text and meaningful UI boundaries. Verify final rendered combinations, including hover and focus states.
- Pair status colors with a label and, where useful, an icon. Paid, locked, and prerequisite states are access information, not errors.
- Avoid gradients and decorative colors behind long learning content. Do not assign arbitrary colors to each content category.

## 3. Typography

- Latin stack: `"Instrument Sans", ui-sans-serif, system-ui, sans-serif`, matching the existing project theme.
- Arabic stack: `"Noto Sans Arabic", Tahoma, Arial, sans-serif`. Load the named Arabic family when implementing localized typography; retain fallbacks.
- Use regular 400 for body text, medium 500 for labels, and semibold 600 for headings and controls. Avoid light weights and uppercase body labels.
- Sizes below are expressed as pixels for reference; implement with `rem` so user font preferences are respected.

| Role | Size | Line height | Weight |
| --- | --- | --- | --- |
| Page title | 32; 28 on small viewports | 1.3 | 600 |
| Section heading | 24 | 1.4 | 600 |
| Component heading | 20 | 1.4 | 600 |
| Reading content | 18 | 1.75 | 400 |
| Body / form controls | 16 | 1.5 | 400 |
| Labels / buttons | 16 | 1.5 | 500–600 |
| Supporting text / table metadata | 14 | 1.5 | 400–500 |

- Keep prose to approximately 60–75 characters per line. Use real heading levels in logical order.
- Give Arabic text a minimum 1.7 body line height; let controls grow to fit its glyphs. Do not apply Latin letter spacing to Arabic.
- Allow long translations to wrap; avoid fixed-height text containers and truncation of instructions or errors.
- Use tabular numerals for scores, timers, and numeric columns. Always show units and score denominators where applicable.

## 4. Spacing and shape

Use a 4px base scale. Select from these tokens instead of introducing one-off values.

| Token | Value | Typical use |
| --- | --- | --- |
| `space-1` | 4px | Icon detail, tight metadata |
| `space-2` | 8px | Label to control, icon to label |
| `space-3` | 12px | Compact internal gaps |
| `space-4` | 16px | Field gaps, mobile card padding |
| `space-6` | 24px | Card padding, component gaps |
| `space-8` | 32px | Related sections |
| `space-12` | 48px | Major section separation |
| `space-16` | 64px | Large viewport outer sections |

- Radii: 6px for controls, 12px for cards and dialogs, full radius for badges and avatars.
- Borders: 1px by default; 2px for selected choice controls without changing dimensions.
- Elevation: default surfaces use borders; floating menus use `0 4px 12px rgb(15 23 42 / 0.10)`, dialogs use `0 16px 48px rgb(15 23 42 / 0.18)`.
- Use one consistent outline icon family. Default icons are 20px; decorative icons are hidden from assistive technology.

## 5. Layout rules

- Use a centered container with a maximum width of 1280px and responsive inline padding: 16px small, 24px medium, 32px large.
- Use flexible CSS grid or flex layouts. Reference grid: 4 columns small, 8 medium, 12 large, with 16px / 24px gaps.
- Reading regions use a separate `70ch` maximum width. Wide data regions may use the full container.
- Keep one clear page heading and a consistent order: context, heading and actions, primary content, supporting content.
- Use 32px between related sections and 48px between major sections. Avoid wrapping every text block in a card.
- Reserve space for media to prevent layout shifts. Video defaults to 16:9, fits its container, and has an accessible title; documents retain readable proportions.
- Sticky elements must not cover focused controls, content, or mobile keyboards. Account for device safe areas.
- Reuse tokens and components in Blade/Tailwind when implementation is requested. Existing application behavior remains unchanged by this specification.

## 6. Buttons and actions

| Variant | Appearance | Use |
| --- | --- | --- |
| Primary | Primary fill, white label | Main action in an action group |
| Secondary | Surface fill, control border, main text | Supporting action |
| Tertiary | Transparent, primary label | Low-emphasis action |
| Destructive | Danger fill, white label | Confirmed destructive action |

- Default minimum height is 44px with 16px inline padding and an 8px icon gap. Large buttons use 48px height and 24px padding.
- Secondary hover uses `surface-muted`; tertiary hover uses `primary-soft`. Pressed secondary/tertiary states add an inset primary outline; destructive states use their dedicated color tokens.
- Focus uses a 2px primary outline with a 2px white separation. Keep it visible on all backgrounds.
- Disabled: muted background, muted label, no hover effect; explain prerequisites nearby when the reason is not obvious.
- Loading: preserve button width, show a spinner and an action label, expose busy state, and prevent duplicate submission.
- Use action-oriented labels. Icon-only controls require an accessible name and a tooltip for unfamiliar actions.
- Use links for navigation and buttons for actions. Destructive confirmation names the affected item and consequence.

## 7. Forms and choice controls

- Place persistent labels above inputs; placeholders provide examples and never replace labels.
- Inputs, selects, and textareas share surface fill, `border-control`, 6px radius, 12px inline padding, and a 44px minimum height. Textareas start at three lines and resize vertically.
- Stack fields by default with 24px between field groups. Pair fields only when closely related and enough width exists.
- Place helper text below the control with an 8px gap. State required/optional conventions clearly and do not rely on an asterisk alone.
- Define default, hover, focus, filled, invalid, disabled, and read-only states. Hover strengthens the outline; focus uses the shared ring; invalid adds a danger border and linked error text. Read-only content stays legible and selectable.
- Show validation after meaningful interaction or submission. Preserve entered values; on failed submission, provide an error summary linked to fields and move focus to it.
- Associate labels, helper text, and errors programmatically. Do not show success styling on every completed field.
- Use native checkbox/radio semantics with a minimum 44px clickable label area. Group related choices with a legend; selection must include a check or dot.
- Exercise answer controls reuse these rules. Keep selection distinct from correct/incorrect feedback; reveal grading only at the appropriate evaluation step.
- Matching and ordering interactions must have keyboard and touch alternatives to dragging.

## 8. Cards and badges

- Base card: surface background, subtle border, 12px radius, 16px small / 24px larger padding, and 16px internal gaps.
- Maintain a predictable hierarchy: title, supporting content, metadata, actions. Use content-driven height; align action rows when cards share a grid.
- Static cards have no interactive hover. Clickable cards expose a real link and visible focus; never nest buttons inside a wrapping link.
- Selection uses a primary border, soft background, and explicit selection indicator. Disabled or restricted content keeps its title and access explanation readable.
- Badges use 14px medium text, 4px block / 8px inline padding, and a text label. Use neutral badges for content type and semantic badges for state.
- Show applicable access conditions explicitly: free, sign-in required, points required, premium, or prerequisite required. Display actual costs and requirements when available.
- Progress uses a primary fill over a muted track plus a visible numeric or text label. Reserve success styling for confirmed completion.

## 9. Tables

- Use semantic tables for comparable records, with a caption or accessible name and scoped column headers.
- Header: muted surface, 14px semibold text. Cells: 14–16px text, 12px block / 16px inline padding, subtle row dividers, and a minimum 48px row height.
- Align text to inline start and numbers to inline end. Keep actions in a consistent final column; action controls retain 44px targets.
- Sorting uses labeled buttons and exposes sort direction. Selected rows show both a checkbox and a soft background.
- Keep hover subtle and do not imply the whole row is clickable unless it has a clear navigation action.
- Pagination states the visible range and total when known. Filters and pagination preserve understandable context.
- On small viewports, use a labeled, keyboard-accessible horizontal scroll region for dense comparisons. A stacked record layout is acceptable when field labels and all actions remain available.
- Never silently hide essential columns. Empty, loading, and error states belong within the table region.

## 10. Navigation

- Use shared naming, iconography, active states, and ordering across navigation patterns. Show destinations appropriate to the current role and access rules.
- Group the PRD destinations by learning content, activity/communication, and account settings. Keep search easy to find and administrative navigation clearly identified.
- Use a persistent side navigation on large viewports and a labeled menu drawer on smaller ones. Keep every applicable destination reachable.
- Indicate current location with `aria-current`, a soft primary background, semibold text, and an inline-start indicator.
- Use breadcrumbs for hierarchical learning content. On narrow widths, condense intermediate ancestors while keeping parent navigation available.
- Tabs switch related sections within a context; use links for route navigation and tab semantics only for in-place panels.
- Drawers and dialogs receive focus when opened, contain keyboard focus while modal, support Escape, and return focus to the trigger. Provide an explicit close control.

## 11. Feedback states

| State | Treatment | Interaction rule |
| --- | --- | --- |
| Loading | Stable skeleton or labeled spinner | Preserve layout; expose busy status |
| Empty | Plain explanation and relevant next action | Distinguish no content from no search matches |
| Success | Success icon and concise message | Confirm the completed action; announce politely |
| Error | Danger icon, explanation, recovery action | Preserve work; keep actionable errors visible |
| Warning | Warning icon and explicit consequence | Explain what requires attention and what to do |
| Information | Info styling and useful guidance | Keep it close to the related content |
| Restricted | Lock icon, access label, requirement | Explain the configured route to access |

- Use inline feedback for local issues and banners for persistent or page-wide issues. Toasts are supplementary confirmations and never the sole source of essential information.
- Correct/incorrect answers include text and explanation alongside color. Scores display achieved and possible values; thresholds come from product configuration.
- Timers show readable remaining time and announced warnings at meaningful thresholds, not every second.
- Use encouraging, specific language without blame. Rewards remain secondary to learning and do not obscure content.
- Transitions use 150–200ms for color, opacity, and small movements. Respect reduced-motion preferences; avoid flashing and automatic celebratory motion.

## 12. Responsive and language behavior

| Viewport | Layout behavior |
| --- | --- |
| Small: below 640px | Single-column forms and reading content; stacked action groups when needed; menu drawer; 16px gutters |
| Medium: 640–1023px | Two-column card groups where content fits; paired fields when readable; menu drawer; 24px gutters |
| Large: 1024px and above | Persistent navigation; flexible multi-column content; 32px gutters within the maximum container |

- Breakpoints are defaults: collapse earlier when content or translations require it. Card grids use a 280px minimum card width where space allows, capped at container width.
- Support a 320px viewport and text zoom without lost actions, clipped text, or page-wide horizontal scrolling; isolate necessary table/document scrolling.
- Keep body and input text at least 16px on small devices. Preserve 44px minimum interactive targets and comfortable gaps between adjacent targets.
- Arabic UI uses `lang="ar"` and `dir="rtl"`; French and English use their appropriate language and LTR direction. Use logical spacing and alignment properties.
- Mirror directional navigation icons and layout flow for RTL. Do not mirror media, logos, checkmarks, or numerical data.
- English teaching passages stay explicitly LTR inside Arabic interfaces. Isolate mixed-language names, scores, and identifiers using appropriate direction markup; user-entered messages may use `dir="auto"`.
- Format dates, numbers, and plural labels for the selected locale. Never build translated sentences by joining fragments.

## 13. Consistency checks

When these rules are implemented, verify reusable components with keyboard navigation, visible focus, long translations, Arabic RTL mixed with English, small viewports, text zoom, contrast, and reduced motion. Cover default, loading, empty, error, selected, disabled, and restricted states where applicable. Add variants only when a requested task needs them; reuse existing tokens first.
