# TODO

The October 2026 quality pass is complete. Remaining follow-up:

- Move the sales-order status timeline (`resources/views/sales-orders/show.blade.php`, mixed date-only and
  date-time values) to `Format::dateTime()` where the value is a timestamp (see ADR 0009).
