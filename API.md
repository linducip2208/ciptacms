# API

Base `/api/v1/`. Sanctum tokens. Generic resources: `GET/POST /api/v1/{resource}`, `GET/PUT/DELETE /api/v1/{resource}/{id}`. Filtering `?search=&filter[x]=&sort=&per_page=`. Version-ready for `/api/v2/` (duplicate controllers + prefix). Webhooks in/out with HMAC + retry job.
