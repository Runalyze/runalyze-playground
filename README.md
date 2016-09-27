# Playground for Runalyze
Do you have any new ideas for [Runalyze](https://github.com/Runalyze/Runalyze) features?
A new tool or statistic? Some useful queries, nice plots or new UI components? Just give them a try. You can add whatever you want within a new directory and play around - without having to care for clean code and performance.

We'll have a look at all ideas and hopefully someday they'll become a real feature.

## 
Enable the bundle:

```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
            new \Runalyze\Bundle\PlaygroundBundle\PlaygroundBundle(),
        // ...
    );
}
```

Mount routing file in AppKernel:

```
    /**
     * @param \Symfony\Component\Routing\RouteCollectionBuilder $routes
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        ...

        $routes->import('@PlaygroundBundle/Resources/config/routing.yml', 'playground');
    }
   ```

## Index
 - [example/distribution](https://github.com/Runalyze/runalyze-playground/tree/master/example/distribution)
  - `statistics.php` calculate min/mean/max/var for trackdata arrays
 - [example/hrv](https://github.com/Runalyze/runalyze-playground/tree/master/example/hrv)
  - `hrv-table.php` show history of hrv values
 - [example/moving-average](https://github.com/Runalyze/runalyze-playground/tree/master/example/moving-average)
  - `moving-average.php` smooth pace data based on moving average with different kernels
 - [example/sunrise](https://github.com/Runalyze/runalyze-playground/tree/master/example/sunrise)
  - `sunrise-sunset-plot.php` calculate and visualize sunset/sunrise
 - [feature/d3js](https://github.com/Runalyze/runalyze-playground/tree/master/feature/d3js)
  - `sparklines.php` create small sparklines for pace/hr/...
  - `small-routes.php` create small graphics of your routes
  - `punchcard.php` punchcard of date/datetime for all activities
  - `radar-heatmap.php` radar heatmap of weekday/hours for all activities
 - [feature/activitiy_compare](https://github.com/Runalyze/runalyze-playground/tree/master/feature/activity_compare)
  - `activity-compare.php` compare one or more running activities to constant pace virtual racer.
 - [feature/hrmax](https://github.com/Runalyze/runalyze-playground/tree/master/feature/hrmax)
  - `find-hrmax.php` find max hr (last 3 months)
 - [ui/css](https://github.com/Runalyze/runalyze-playground/tree/master/ui/css)
  - `circle-progress.php` half circle as progress bar
