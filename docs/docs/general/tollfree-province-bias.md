# Tollfree Province Bias
---

Tollfree is independent of any state/province bias. To enable a specific bias, add static `$toll_free_province_bias` to your `config.php`, and set to the two letter state or province bias. This example: will bias to Texas.

```php
$toll_free_province_bias = "TX"
```

To enable a toll number bias (meaning to override the location of the number itself), add `$toll_province_bias` to your `config.php`, and set to the two letter state or province bias.
