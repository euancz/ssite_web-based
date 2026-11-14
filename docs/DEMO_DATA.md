# Demo data

Run explicitly against the database and storage configured in `.env`:

```sh
php artisan db:seed --class=DemoDataSeeder --force
```

This adds eight `[DEMO]` items to each content module (articles, activities,
achievements, documents, and liquidations): five approved and active, one pending,
one rejected, and one archived. Documents and liquidations include valid private
PDF attachments. Normal mobile publication observers remain enabled.

It also adds five fictional users, sponsors, and officer roster entries, plus
one draft per content module in the existing President's workspace, if present,
or in the corresponding demo officer's workspace otherwise.
Users have reserved `example.test` email addresses and no password or Microsoft
identity. They are sample records, not login accounts. No President account is
created and existing account permissions are preserved.

The seeder records initial content versions. It does not fabricate human review
events or send notifications. Sample approved posts have no human reviewer.

Rerunning skips existing sample titles, email addresses, roster entries, and
draft IDs, preserving edits. Renaming a sample title or roster entry allows a
later run to recreate the original sample. This seeder is deliberately separate
from `DatabaseSeeder` so normal database setup does not publish fictional data.
