# What does it do

* Crawls a website and generates a csv with: status code, url, where the url was found, a status message
* optionally parses the robots.txt to get all sitemap.xmls and scans these urls too

# How to use:
* clone this repo
* run composer install
* run the crawler

## Crawling

```
./crawler crawl https://www.example.org  crawlresult.csv
```

To parse the robots.txt and use the sitemap.xml, add the according option

```
./crawler crawl https://www.example.org  crawlresult.csv --check-sitemap-xml=true 
```

If you want to further work with the created csv file, have a look at https://csvkit.readthedocs.io/
To e.g. only show results with status code 200, sorted by url, run this command. 
You can navigate with the error keys in the resulting less view.

```
csvgrep -c status -m 200 crawlresult.csv | csvsort -c url | csvlook | less -S
```

To show all results with other status codes than 200:

```
csvgrep -c status -m 200 -i crawlresult.csv | csvsort -c url | csvlook | less -S
```

Create a new csv file with only the urls of the results with status code 200:

```
csvgrep -c status -m 200 crawlresult.csv | csvsort -c url | csvcut -c url | tail -n +2 > urls.csv
```

## Validating

First argument is the domain to use for relative urls or when feeding live domains, then path to the list of urls to check,
then where to store the results. Optionally provide credentials for auth basic
TODO: parse result CSV instead of using plain list, run requests async.

```
./crawler validate dev.example.org live-urls.txt results.csv --user user --password password
```
