# Where Is What

Use this guide to find the file that owns a common change.

| If I want to change... | Open... |
| --- | --- |
| Navbar pinning while scrolling | `public/css/app.css` (`.site-header`) |
| Add or change a shared icon shape or name | `resources/views/components/icon.blade.php` and `docs/ICONS.md`; keep the workspace wrapper compatible |
| Change icon sizing, stroke, status colors, or shared UI tokens | `public/css/app.css` and `resources/views/components/icon.blade.php` |
| Change feed reveal, count-up, notification pop, or article reading-progress behavior | `resources/js/motion.js`; opt-in view markers live in the corresponding Blade views/components |
| Change session flash toast enhancement or dismissal | `resources/js/flash-toasts.js`, `public/css/app.css`, and views marked with `data-flash-toast`; unenhanced messages stay inline |
| Change which flash messages become toasts | Search for `data-flash-toast` in `resources/views/`; login, validation, and async feedback have separate behavior |
| Change Motion dependency or Vite entry wiring | `package.json`, `resources/js/app.js`, and `vite.config.js` |
| Review icon/motion batch status, bundle sizes, and verification limits | `docs/ICONS_MOTION_PROGRESS.md` |
| Header suggestions, keyboard behavior, or dropdown limits | `resources/views/layouts/partials/search-scripts.blade.php` and `public/css/app.css` |
| Search query normalization, searchable fields, excerpts, and result relevance | `app/Support/SiteSearch.php` |
| Full search results, filtering, and pagination | `app/Http/Controllers/SearchController.php` and `resources/views/search/index.blade.php` |
| Add a searchable type | `app/Support/SiteSearch.php` (type method, visibility, fields, route, and metadata) and `app/Http/Controllers/SearchController.php` (type label) |
| Change searched columns | `app/Support/SiteSearch.php` (per-type method) |
| Change feed sorting or filters | The matching `app/Http/Controllers/<PostType>Controller.php`, `resources/views/<feature>/index.blade.php`, and shared `resources/views/components/posts/sort-row.blade.php` |
| Change shared sort/filter controls or their styling | `resources/views/components/posts/sort-row.blade.php` and `public/css/post-feed.css` |
| Change view counting, session deduplication, or bot exclusions | `app/Support/PostEngagement.php` and `config/school.php` (`enable_view_counts`, `view_dedupe_hours`, `view_bot_user_agents`) |
| Change like toggling or the shared like route | `app/Http/Controllers/PostLikeController.php`, `app/Models/PostLike.php`, and `routes/features.php` (`posts.like`) |
| Change engagement feature availability | `config/school.php` (`enable_view_counts`, `enable_likes`); both default on, with runtime checks for each module's counter column and required engagement table |
| Change minimum query length or word/character limits | `app/Support/SiteSearch.php` (`normalize`) |
| Change guest search access | `config/school.php` (`search_include_documents_for_guests`, `search_include_liquidation_for_guests`) |
| Change the dropdown result caps | `app/Http/Controllers/SearchController.php` (`suggest`) and `resources/views/layouts/partials/search-scripts.blade.php` |
| Change the relevance order | `app/Support/SiteSearch.php` (`applyTitleOrder`, `relevanceRank`, and result sorting) |
| Request limits for login, likes, content writes, and uploads | `app/Providers/AppServiceProvider.php` (named limits) plus `routes/auth.php`, `routes/features.php`, and `routes/profile.php` (route attachments) |
| Search routes and throttling | `routes/features.php` (`search.index`, `search.suggest`) |
| The measured header height used for anchor offsets | `resources/views/layouts/app.blade.php` and `public/css/app.css` (`--site-header-height`) |
| The navbar shadow after scrolling | `resources/views/layouts/app.blade.php` and `public/css/app.css` (`.site-header.is-scrolled`) |
| The desktop/mobile navigation or role-gated links | `resources/views/layouts/partials/navigation.blade.php` |
| The avatar dropdown, role groups, or keyboard navigation | `resources/views/layouts/app.blade.php`, `resources/views/layouts/partials/account-scripts.blade.php`, and `public/css/app.css` |
| Workflow tabs or the More menu | `resources/views/components/ui/workflow-tabs.blade.php` and the matching feature `index.blade.php`; capability whitelists stay in the feature controllers |
| Feature row menus or adviser bulk selection bars | The matching `resources/views/<feature>/index.blade.php`, `resources/views/components/ui/workflow-tabs.blade.php`, and `public/css/app.css` |
| Dashboard stat cards | `resources/views/components/ui/stat-card.blade.php` and the matching adviser/officer dashboard view |
| Content card zoom amount, speed, or easing | `public/css/app.css` (`--hover-scale`, `--hover-duration`, `--hover-card-duration`, `--hover-easing`) |
| Content card lift or shadow | `public/css/app.css` (`--hover-lift`, `--hover-card-shadow`) |
| Add or turn off content card motion | `public/css/app.css` (`.card-hover`, `.card-media`) and the matching public content view |
| The account menus, bell dropdown, unread badge, polling, or tab dots | `resources/views/layouts/partials/account-scripts.blade.php` and `public/css/app.css` |
| Notification list page or All/Unread filter | `resources/views/notifications/index.blade.php` and `app/Http/Controllers/NotificationController.php` |
| Notification summary JSON, owned read/dismiss actions, or stale-link handling | `app/Http/Controllers/NotificationController.php` and `routes/features.php` |
| Notification messages and stored payload fields | `app/Notifications/` |
| Who gets review or publication notifications and recipient chunking | `app/Support/PostNotificationRecipients.php` |
| Which post actions send notifications | The matching post controller's `store`, `update`, `approve`, and `reject` methods |
| Notification endpoints, polling summary, and read/delete security | `routes/features.php` and `app/Http/Controllers/NotificationController.php` |
| Notification actor profile pictures | `app/Http/Controllers/NotificationController.php` (`avatar`) and `resources/views/layouts/partials/account-scripts.blade.php` |
| How unread tab dots are counted | `app/Providers/AppServiceProvider.php` (one grouped query for the shared layout) |
| The month when the academic year starts | `config/school.php` (`academic_year_start_month`) |
| Application date and time zone | `config/app.php` (`APP_TIMEZONE`, defaults to `Asia/Manila`) and `.env.example` |
| Maximum PDF upload size | `config/school.php` (`max_pdf_size_kb`) and PHP `php.ini` upload limits |
| Whether document or liquidation lists are public | `config/school.php` (`documents_list_public`, `liquidation_list_public`) |
| Who can view PDFs | `app/Policies/DocumentPolicy.php`, `app/Policies/LiquidationPolicy.php`, and each module controller's file handler |
| PDF viewer controls, lazy loading, print behavior, and interaction deterrents | `resources/js/document-pdf-viewer.js`, `resources/css/document-pdf-viewer.css`, and the Documents/Liquidation show views |
| Documents tabs, sort, category filter, or public query | `app/Http/Controllers/DocumentController.php` and `resources/views/documents/index.blade.php` |
| Document form fields, PDF validation, or file delivery | `app/Http/Controllers/DocumentController.php` and `resources/views/documents/` |
| Liquidation tabs, sort, or public query | `app/Http/Controllers/LiquidationController.php` and `resources/views/liquidation/index.blade.php` |
| Liquidation form fields, PDF validation, or file delivery | `app/Http/Controllers/LiquidationController.php` and `resources/views/liquidation/` |
| Where private document and liquidation PDFs are stored | `config/filesystems.php` (`local` disk root) and each module controller |
| Officer position choices and their About page order | `config/school.php` (`officer_positions`) |
| Assignable officer positions, module permissions, and President auto-approval | `config/school.php` (`positions`, `single_holder_positions`, `president_posts_auto_approved`) and `app/Models/User.php` |
| Assign officer/adviser roles and canonical officer positions | `/adviser/users`; `app/Http/Controllers/Adviser/UserController.php` and `resources/views/adviser/users/index.blade.php` |
| President review queue and review buttons | `app/Http/Controllers/Adviser/ReviewController.php`, `resources/views/adviser/reviews/index.blade.php`, and the five feature controllers |
| Adding next year's officers or copying the previous roster | Adviser page at `/adviser/officers`; controller in `app/Http/Controllers/Adviser/OfficerTermController.php` |
| About page gear options, edit mode, or officer card controls | `resources/views/pages/about.blade.php`, `public/css/about.css`, and `app/Http/Controllers/Adviser/OfficerTermController.php` |
| Fixing a typo in a past year's officer | Adviser page at `/adviser/officers`, then select that academic year and edit the row |
| About page officer card and history layout | `resources/views/pages/about.blade.php` and `public/css/about.css` |
| The navbar avatar and initials fallback | `resources/views/components/ui/avatar.blade.php` and `app/Models/User.php` |
| How fallback initials are calculated | `app/Models/User.php` (`initials()`) |
| Picture upload size and type rules | `app/Http/Requests/Profile/UpdateProfilePictureRequest.php` |
| Where uploaded pictures are stored | `app/Http/Controllers/Profile/ProfileController.php` (`profile-pictures/` on the public disk) |
| Profile picture upload/remove controls | `resources/views/profile/partials/picture.blade.php` |
| The sign-in and Microsoft callback URLs | `routes/auth.php` |
| Password login/logout behavior | `app/Http/Controllers/Auth/AuthController.php` |
| Microsoft login and post-login redirect | `app/Http/Controllers/Auth/MicrosoftAuthController.php` |
| Which roles an Article action allows | `app/Policies/ArticlePolicy.php` and `routes/features.php` |
| Article tabs, sort, or visibility queries | `app/Http/Controllers/ArticleController.php` and `resources/views/articles/index.blade.php` |
| Article approve, reject, archive, or restore behavior | `app/Http/Controllers/ArticleController.php` |
| Article action buttons and confirmation dialogs | `resources/views/articles/show.blade.php` |
| Article list cards, tabs, or bulk archive selection | `resources/views/articles/index.blade.php` |
| Article create/edit fields and image preview | `resources/views/articles/_form.blade.php` |
| Article input validation | `app/Http/Controllers/ArticleController.php` (`store`, `update`, `reject`) |
| Article table mapping, status scopes, or image URLs | `app/Models/Article.php` |
| Activity roles and allowed actions | `app/Policies/ActivityPolicy.php` and `routes/features.php` |
| Activity tabs, sort, timeframe filter, or visibility queries | `app/Http/Controllers/ActivityController.php` and `resources/views/activities/index.blade.php` |
| Activity approve, reject, archive, or restore behavior | `app/Http/Controllers/ActivityController.php` |
| Activity action buttons and confirmation dialogs | `resources/views/activities/show.blade.php` |
| Activity list cards, tabs, or bulk archive selection | `resources/views/activities/index.blade.php` |
| Activity create/edit fields and image preview | `resources/views/activities/_form.blade.php` |
| Activity input validation | `app/Http/Controllers/ActivityController.php` (`store`, `update`, `reject`) |
| Activity table mapping, status scopes, or image URLs | `app/Models/Activity.php` |
| Achievement roles and allowed actions | `app/Policies/AchievementPolicy.php` and `routes/features.php` |
| Achievement tabs, sort, category filter, or visibility queries | `app/Http/Controllers/AchievementController.php` and `resources/views/achievements/index.blade.php` |
| Achievement approve, reject, archive, or restore behavior | `app/Http/Controllers/AchievementController.php` |
| Achievement action buttons and confirmation dialogs | `resources/views/achievements/show.blade.php` |
| Achievement list cards, tabs, or bulk archive selection | `resources/views/achievements/index.blade.php` |
| Achievement create/edit fields and image preview | `resources/views/achievements/_form.blade.php` |
| Achievement input validation | `app/Http/Controllers/AchievementController.php` (`store`, `update`, `reject`) |
| Achievement table mapping, status scopes, or image URLs | `app/Models/Achievement.php` |
| Required profile fields or profile validation | `app/Http/Requests/Profile/` and `resources/views/profile/partials/fields.blade.php` |
| Profile completion/edit page flow | `app/Http/Controllers/Profile/ProfileController.php` and `routes/profile.php` |
| Required profile fields, configured options, and student-number rules | `app/Http/Requests/Profile/` |
| Officer Term validation and custom academic-year checks | `app/Http/Requests/StoreOfficerTermRequest.php` and `UpdateOfficerTermRequest.php` |
| Role checks and profile-completion middleware | `app/Http/Middleware/` and `bootstrap/app.php` |
| Officer dashboard | `app/Http/Controllers/Officer/DashboardController.php` and `resources/views/officer/dashboard.blade.php` |
| Adviser/superadmin dashboards, pending counts, or role history | `app/Http/Controllers/Adviser/DashboardController.php` and `resources/views/adviser/dashboard.blade.php` |
| Position-based officer dashboard and own post counts | `app/Http/Controllers/Officer/DashboardController.php` and `resources/views/officer/dashboard.blade.php` |
| Adviser/superadmin user assignment manager | `app/Http/Controllers/Adviser/UserController.php` and `resources/views/adviser/users/` |
| Public home or about page content | `resources/views/pages/` |
| Activity, achievement, document, or liquidation page content | The matching `resources/views/<feature>/` directory |
| Shared page headers, badges, tables, statistic cards, or reject modal | `resources/views/components/ui/` |
| Which feature route file owns a URL | `routes/features.php`, `routes/auth.php`, `routes/profile.php`, `routes/officer.php`, or `routes/adviser.php` |
| Add or change a URL, name, method, or middleware | The matching `routes/*.php` file; `routes/features.php` uses resources for matching CRUD actions and explicit routes for special actions |
| Home page feature queries and public visibility | `app/Http/Controllers/HomeController.php`; each section uses the model's `publiclyVisible()` scope |
| Direct `/login` redirect message | `app/Http/Controllers/Auth/AuthController.php` (`redirectToHome`) and `routes/auth.php` |
| Profile program-to-institute option mapping | `app/Http/Controllers/Profile/ProfileController.php` (`formData`) and `resources/views/profile/partials/fields.blade.php` |
| About page current officers and history ordering | `app/Http/Controllers/AboutController.php` and `resources/views/pages/about.blade.php` |
| School profile options or shared Article, Activity, and Achievement post settings | `config/school.php` |
| Database schema migrations | `database/migrations/` (fresh `users.user_id` type and officer-term uniqueness; never run migrations against the live database during review) |
| Authorization regression checks and isolated test database | `tests/Unit/ContentAuthorizationTest.php` and `phpunit.xml` (forced SQLite `:memory:`; application MySQL settings are unchanged) |
| Production deployment, backup, restore, and health checks | `docs/DEPLOYMENT_RUNBOOK.md` (host-neutral; rehearsal requires an isolated non-production destination) |
| Branded 403, 404, 419, and 500 error responses | `resources/views/errors/` and `public/css/errors.css` (standalone layout, home recovery link) |

Route URLs, route names, middleware, and role checks are defined separately from the Blade pages. Moving a view changes its `view(...)` path, while the URL is controlled by its route file.

## Simplified CRUD pattern

For a content feature, start in its controller and follow the same method order: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, then `approve`, `reject`, `archive`, and `restore`. Keep short create/update/reject validation rules and messages inline in those controller methods. Officer Term and Profile requests remain separate because they contain custom or shared logic.

Before changing a feature, check that public and student-facing lists, details, search, home sections, notification links, and PDF view routes use `publiclyVisible()`; write routes retain role and `profile.complete` middleware; policies and officer ownership checks still use `user_id`; custom model keys and limited `$fillable` lists remain; review only changes pending items and restore preserves approval; private PDFs are validated, stored, and served through authorized controller actions; notifications and search retain their scopes and limits; and forms keep CSRF, correct HTTP methods, and escaped Blade output.

Search behavior: `/search` and `/search/suggest` share `SiteSearch::normalize()`. Control characters become spaces, whitespace is collapsed, input is capped at 100 characters and five words, and fewer than two characters returns no results without querying feature models. Each word must match at least one allowed field, with bound and escaped LIKE patterns. Results rank title prefix matches first, title substring matches second, then other-field matches; newer records break ties. The suggestion endpoint keeps at most three matches per type and ten overall. The results page filters by type and paginates ten per page. Users and profile fields are never searched; only approved, active posts use `publiclyVisible()`. Guest Documents and Liquidation visibility follows the search toggles, which default to the corresponding list visibility settings. Those results link to metadata show pages, never PDFs. LIKE matching may need FULLTEXT indexes if the data grows large.

Notification behavior: notification endpoints require authentication and completed profiles. Every list, summary, read, read-all, dismiss, and avatar lookup is scoped to the signed-in user's notification relation. The summary returns unread total, unread `published` counts by post type for navigation dots, and the latest ten notifications. The list paginates fifteen and supports `all` or `unread`. Opening a notice marks only that notice read; missing, archived, or no-longer-published content gets a friendly fallback. Read-all and dismiss never affect another user's records. Publication notices are sent only for approved active content; archiving and restoring do not send them.

Sticky navbar gotcha: an ancestor with `overflow: auto`, `overflow: hidden`, or `overflow: scroll` can block or change sticky positioning. Check the shared header's full parent chain before adding overflow rules.

Content card gotcha: transforms create stacking contexts that can trap row menus below adjacent content. Keep the image clipping frame separate from the card, leave list overflow visible, and check card menus against the sticky header, navigation pill, and About gear menu.

## Design tokens

- **Colors:** SSITE blue `#8AD5F4`, pale blue `#C7ECF9`, navy `#17324D`, soft navy `#3E5972`, and line blue `#3DAFEA` are in `public/css/app.css` under `:root`.
- **Fonts:** Poppins is the global sans in `public/css/app.css`; the matching Tailwind sans token is in `resources/css/app.css`; the Poppins stylesheet link is in `resources/views/layouts/app.blade.php`.
- **Spacing:** Shared spacing uses repeated 4/8/12/16/24px values in `public/css/app.css`; page-specific spacing is in `public/css/home.css` and `public/css/about.css`.
- **Radii:** Shared card/control radii and pill/circle shapes are in `public/css/app.css`; About's alternating 24px corners are in `public/css/about.css`.
- **Shadows:** Shared hover shadow variables are in `public/css/app.css`; static About card shadows are in `public/css/about.css`.
- **Review record:** See `docs/DESIGN_REVIEW.md` for the source review, finding IDs, approved changes, and verification limits.

If I want to change a color, open `public/css/app.css` and update the `:root` token. If I want to change the font, open `resources/views/layouts/app.blade.php`, `public/css/app.css`, and `resources/css/app.css`. If I want to change spacing, open `public/css/app.css` for shared components or the matching page stylesheet. If I want to add a component, open `resources/views/components/ui/` and add or update its styles in `public/css/app.css`.

## Officer history

The current academic year is calculated from the date and the configured start month. Advisers maintain a separate snapshot for each year; role changes affect only the current year's row. New-year rows are not created automatically. Photos uploaded for officer terms are stored in `storage/app/public/officer-photos`, served through `storage:link`, and old uploaded term photos are removed only when their term is replaced or deleted.
