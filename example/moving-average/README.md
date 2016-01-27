Simple example to smooth pace data via moving averages with different kernels, see https://en.wikipedia.org/wiki/Moving_average#Cumulative_moving_average.

`moving-average.php?activityID=...[&accountID=0&smoothing=1&precision=1000points]`

You can change the following part to use other kernel widths:
```php
$widths = [0.05, 0.1, 0.2, 0.5, 1.0, 2.0];
```