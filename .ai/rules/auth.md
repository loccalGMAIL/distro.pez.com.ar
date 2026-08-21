---
paths:
  - 'app/Http/Controllers/Auth/**,.env'
---

# Auth

## Access the app via localhost, not 127.0.0.1, when testing Google login
APP_URL / GOOGLE_REDIRECT_URI are pinned to `http://localhost:8000`. Browsers treat `127.0.0.1` and `localhost` as different origins for cookies, so if you start the OAuth flow (`/auth/google/redirect`) from `127.0.0.1:8000`, the session cookie holding Socialite's `state` never reaches the `localhost:8000` callback — you get `Laravel\Socialite\Two\InvalidStateException`. Always browse the app at `http://localhost:8000` during local dev, especially when testing Google login.
