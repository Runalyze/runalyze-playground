# Playground for Runalyze
Do you have any new ideas for [RUNALYZE](https://github.com/Runalyze/Runalyze) features?
A new tool or statistic? Some useful queries, nice plots or new UI components? Just give them a try. You can add whatever you want within a new directory and play around - without having to care for clean code and performance.

We'll have a look at all ideas and hopefully someday they'll become a real feature.

## Using the bundle
The PlaygroundBundle is installed as dev-dependency of RUNALYZE and registered in dev mode only.
In general, it will be available as `runalyze/app_dev.php/_playground` and will show an index of all available tools.

To be able to edit playground files, use in your RUNALYZE directory the following:
```
composer update runalyze/playground --prefer-source
```
