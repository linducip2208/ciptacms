# Database

~90 tables. Conventions: `id` PK (tenants use string UUID PK), `tenant_id` nullable string index, `uuid` where external, soft deletes for content/commerce, timestamps everywhere, pivot tables for roles/permissions/tags.

Migrations `2026_09_26_10000x_*`. DataBuilder can create physical `cb_*` tables per content type.
