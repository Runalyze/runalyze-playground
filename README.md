# Playground for Runalyze
Do you have any new ideas for [Runalyze](https://github.com/Runalyze/Runalyze) features?
A new tool or statistic? Some useful queries, nice plots or new UI components? Just give them a try. You can add whatever you want within a new directory and play around - without having to care for clean code and performance.

We'll have a look at all ideas and hopefully someday they'll become a real feature.

## Usage
You probably have to create your own `config.php` to apply your own directory structure, e.g.
```php
$URL_BASE_TO_RUNALYZE	= 'http://localhost/runalyze/';
```

To start a new example just create your file, e.g. `feature/my-feature/example.php` and include bootstrap:
```php
require_once '../../bootstrap.php';
```

For every idea you can define what to load (before you bootstrap):
```php
$LOAD_FRONTEND = true;
$LOAD_HTML = true;
$LOAD_CSS = false;
$LOAD_JS = false;
```

## Index
 - [example/distribution](https://github.com/Runalyze/runalyze-playground/tree/master/example/distribution)
  - `statistics.php` calculate min/mean/max/var for trackdata arrays
 - [example/moving-average](https://github.com/Runalyze/runalyze-playground/tree/master/example/moving-average)
  - `moving-average.php` smooth pace data based on moving average with different kernels
 - [example/sunrise](https://github.com/Runalyze/runalyze-playground/tree/master/example/sunrise)
  - `sunrise-sunset-plot.php` calculate and visualize sunset/sunrise
 - [feature/d3js](https://github.com/Runalyze/runalyze-playground/tree/master/feature/d3js)
  - `sparklines.php` create small sparklines for pace/hr/...
  - `small-routes.php` create small graphics of your routes
  - `punchcard.php` punchcard of date/datetime for all activities
 - [ui/css](https://github.com/Runalyze/runalyze-playground/tree/master/ui/css)
  - `circle-progress.php` half circle as progress bar