# Where Is What

Use this guide to find the file that owns a common change.

| If I want to change... | Open... |
| --- | --- |
| Navbar pinning while scrolling | `public/css/app.css` (`.site-header`) |
| The measured header height used for anchor offsets | `resources/views/layouts/app.blade.php` and `public/css/app.css` (`--site-header-height`) |
| The navbar shadow after scrolling | `resources/views/layouts/app.blade.php` and `public/css/app.css` (`.site-header.is-scrolled`) |
| The desktop/mobile navigation or account menus | `resources/views/layouts/app.blade.php` |
| The month when the academic year starts | `config/school.php` (`academic_year_start_month`) |
| Maximum PDF upload size | `config/school.php` (`max_pdf_size_kb`) and PHP `php.ini` upload limits |
| Whether document or liquidation lists are public | `config/school.php` (`documents_list_public`, `liquidation_list_public`) |
| Who can view and download PDFs | `app/Policies/DocumentPolicy.php`, `app/Policies/LiquidationPolicy.php`, and each module controller's file handlers |
| Documents tabs, search, category filter, or public query | `app/Http/Controllers/DocumentController.php` and `resources/views/documents/index.blade.php` |
| Document form fields, PDF validation, or file delivery | `app/Http/Requests/Document/`, `app/Http/Controllers/DocumentController.php`, and `resources/views/documents/` |
| Liquidation tabs, search, or public query | `app/Http/Controllers/LiquidationController.php` and `resources/views/liquidation/index.blade.php` |
| Liquidation form fields, PDF validation, or file delivery | `app/Http/Requests/Liquidation/`, `app/Http/Controllers/LiquidationController.php`, and `resources/views/liquidation/` |
| Where private document and liquidation PDFs are stored | `config/filesystems.php` (`local` disk root) and each module controller |
| Officer position choices and their About page order | `config/school.php` (`officer_positions`) |
| Adding next year's officers or copying the previous roster | Adviser page at `/adviser/officers`; controller in `app/Http/Controllers/Adviser/OfficerTermController.php` |
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
| Article tabs, filters, or visibility queries | `app/Http/Controllers/ArticleController.php` |
| Article approve, reject, archive, or restore behavior | `app/Http/Controllers/ArticleController.php` |
| Article action buttons and confirmation dialogs | `resources/views/articles/show.blade.php` |
| Article list cards, tabs, or bulk archive selection | `resources/views/articles/index.blade.php` |
| Article create/edit fields and image preview | `resources/views/articles/_form.blade.php` |
| Article input validation | `app/Http/Requests/Article/` |
| Article table mapping, status scopes, or image URLs | `app/Models/Article.php` |
| Activity roles and allowed actions | `app/Policies/ActivityPolicy.php` and `routes/features.php` |
| Activity tabs, search, or visibility queries | `app/Http/Controllers/ActivityController.php` |
| Activity approve, reject, archive, or restore behavior | `app/Http/Controllers/ActivityController.php` |
| Activity action buttons and confirmation dialogs | `resources/views/activities/show.blade.php` |
| Activity list cards, tabs, or bulk archive selection | `resources/views/activities/index.blade.php` |
| Activity create/edit fields and image preview | `resources/views/activities/_form.blade.php` |
| Activity input validation | `app/Http/Requests/Activity/` |
| Activity table mapping, status scopes, or image URLs | `app/Models/Activity.php` |
| Achievement roles and allowed actions | `app/Policies/AchievementPolicy.php` and `routes/features.php` |
| Achievement tabs, search, or visibility queries | `app/Http/Controllers/AchievementController.php` |
| Achievement approve, reject, archive, or restore behavior | `app/Http/Controllers/AchievementController.php` |
| Achievement action buttons and confirmation dialogs | `resources/views/achievements/show.blade.php` |
| Achievement list cards, tabs, or bulk archive selection | `resources/views/achievements/index.blade.php` |
| Achievement create/edit fields and image preview | `resources/views/achievements/_form.blade.php` |
| Achievement input validation | `app/Http/Requests/Achievement/` |
| Achievement table mapping, status scopes, or image URLs | `app/Models/Achievement.php` |
| Required profile fields or profile validation | `app/Http/Requests/Profile/` and `resources/views/profile/partials/fields.blade.php` |
| Profile completion/edit page flow | `app/Http/Controllers/Profile/ProfileController.php` and `routes/profile.php` |
| Role checks and profile-completion middleware | `app/Http/Middleware/` and `bootstrap/app.php` |
| Officer dashboard | `app/Http/Controllers/Officer/DashboardController.php` and `resources/views/officer/dashboard.blade.php` |
| Adviser dashboard, review placeholder, or user management | `app/Http/Controllers/Adviser/` and `resources/views/adviser/` |
| Public home or about page content | `resources/views/pages/` |
| Activity, achievement, document, or liquidation page content | The matching `resources/views/<feature>/` directory |
| Shared page headers, badges, tables, statistic cards, or reject modal | `resources/views/components/ui/` |
| Which feature route file owns a URL | `routes/features.php`, `routes/auth.php`, `routes/profile.php`, `routes/officer.php`, or `routes/adviser.php` |
| School profile options or shared Article, Activity, and Achievement post settings | `config/school.php` |
| Database schema migrations | `database/migrations/` (keep filenames already run unchanged) |

Route URLs, route names, middleware, and role checks are defined separately from the Blade pages. Moving a view changes its `view(...)` path, while the URL is controlled by its route file.

Sticky navbar gotcha: an ancestor with `overflow: auto`, `overflow: hidden`, or `overflow: scroll` can block or change sticky positioning. Check the shared header's full parent chain before adding overflow rules.

## Officer history

The current academic year is calculated from the date and the configured start month. Advisers maintain a separate snapshot for each year; role changes affect only the current year's row. New-year rows are not created automatically. Photos uploaded for officer terms are stored in `storage/app/public/officer-photos`, served through `storage:link`, and old uploaded term photos are removed only when their term is replaced or deleted.
