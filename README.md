# Federwiegen Verleih Plugin

This WordPress plugin enables the rental of baby swings ("Federwiegen").

## Sample Data

By default this plugin **does not** insert any demonstration data when it is
activated. If you would like to load the example records for a quick start you
can enable them by defining the following constant **before** activation (e.g.
in your `wp-config.php`):

```php
define('FEDERWIEGEN_LOAD_DEFAULT_DATA', true);
```

You may also control this behaviour programmatically using the
`federwiegen_load_default_data` filter.