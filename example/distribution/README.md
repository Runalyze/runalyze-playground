Simple example to calculate statistics for trackdata as `Calculation\Distribution\TimeSeries`
supports calculating min/mean/max/variance of any time series.

`statistics.php` calculates these values for all given arrays of your latest activity with trackdata.
You can request any activity via `statistics.php?id=...`.

Examplary output:
```
heartrate:
min:        73.000
mean:      144.307
max:       163.000
var:       100.032
std:        10.002
 
cadence:
min:        43.000
mean:       91.629
max:       122.000
var:        12.793
std:         3.577

...
```