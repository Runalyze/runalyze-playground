# Playing around with D3.js

[D3.js](https://github.com/mbostock/d3) is an extremely powerful visualization library,
GitHub is using it for all these sexy plots as well.

**Examples:**
 - [small-routes.php](https://github.com/Runalyze/runalyze-playground/tree/master/feature/d3js/small-routes.php)
  - `feature/d3js/small-routes.php?stepsize=20&size=100&limit=20`
  - draw small graphics of your routes
 - [sparklines.php](https://github.com/Runalyze/runalyze-playground/tree/master/feature/d3js/sparklines.php)
  - `feature/d3js/sparklines.php?limit=20&w=100&h=25&points=150`
  - small sparklines for pace/hr/cadence/groundcontact/vertical oscillation
 - [punchcard.php](https://github.com/Runalyze/runalyze-playground/tree/master/feature/d3js/punchcard.php)
  - `feature/d3js/punchcard.php`
  - draw a punchcard of date/datetime for all activities
 - [pointmap.html](https://github.com/Runalyze/runalyze-playground/blob/master/feature/d3js/pointmap.html)
  - Create CSV Data (SELECT startpoint_lat, startpoint_lng INTO OUTFILE '~datapoints.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'  LINES TERMINATED BY '\n' FROM `runalyze_route` WHERE startpoint_lng != '';)
  - Add "latitude,longitude" to the first line of the file
