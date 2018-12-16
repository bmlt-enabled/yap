---
layout: default
title: Multiple Twilio Accounts
parent: General
nav_order: 11
---

# Multiple Twilio Accounts

---


If using a single yap instance for multiple service bodies that have seperate twilio accounts, you can specify alternative twilio credentials by adding the following to your webhook.



```php
alt_twilio_acct=N;
```

You will then need to add the additional twilio credentials in your `config.php` for example.

```php
static $twilio_account_sid_N = "";
static $twilio_auth_token_N = "";
```
